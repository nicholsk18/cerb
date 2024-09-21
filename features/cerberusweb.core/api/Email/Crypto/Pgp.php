<?php

namespace Cerb\Email\Crypto;

use DevblocksPlatform;
use Exception_DevblocksEmailDeliveryError;
use Model_DevblocksOutboundEmail;

abstract class Pgp {
	protected function signWithPGP($plaintext, $key_fingerprint) {
		$gpg = DevblocksPlatform::services()->gpg();

		if(($signed = $gpg->sign($plaintext, $key_fingerprint)))
			return $signed;

		throw new Exception_DevblocksEmailDeliveryError('Failed to sign message (passphrase on the secret key?)');
	}
	
	protected function encryptWithPGP($plaintext, $key_fingerprints) {
		$gpg = DevblocksPlatform::services()->gpg();
		
		if(($encrypted = $gpg->encrypt($plaintext, $key_fingerprints)))
			return $encrypted;
		
		throw new Exception_DevblocksEmailDeliveryError('Error: Failed to encrypt message');
	}
	
	protected function getSignKey(\Model_DevblocksOutboundEmail $email_model) {
		$gpg = DevblocksPlatform::services()->gpg();
		
		// Check for group/bucket overrides

		if(($bucket = $email_model->getBucket())) {
			if(($reply_signing_key = $bucket->getReplySigningKey())) {
				return $reply_signing_key->fingerprint;
			}
		}

		// Check for private keys that cover the 'From:' address

		if(!($from = $email_model->getFromAddressModel()))
			return false;

		if(($keys = $gpg->keyinfoPrivate(sprintf("<%s>", $from->email))) && is_array($keys)) {
			foreach($keys as $key) {
				if($this->isValidKey($key, 'sign'))
				foreach($key['subkeys'] as $subkey) {
					if($this->isValidKey($subkey, 'sign')) {
						return $subkey['fingerprint'];
					}
				}
			}
		}

		return false;
	}
	
	/* @throw Exception_DevblocksEmailDeliveryError */
	protected function getRecipientKeys(Model_DevblocksOutboundEmail $email_model) : array {
		$gpg = DevblocksPlatform::services()->gpg();
		
		$to = $email_model->getTo() ?: [];
		$cc = $email_model->getCc() ?: [];
		$bcc = $email_model->getBcc() ?: [];

		$recipients = array_unique(array_keys(array_merge($to, $cc, $bcc)));

		if(!is_array($recipients) || empty($recipients))
			throw new Exception_DevblocksEmailDeliveryError(sprintf('No valid recipients for PGP encryption'));

		$fingerprints = [];

		foreach($recipients as $recipient) {
			$found = false;

			if(($keys = $gpg->keyinfoPublic(sprintf("<%s>", $recipient))) && is_array($keys)) {
				foreach($keys as $key) {
					if($this->isValidKey($key, 'encrypt'))
					foreach($key['subkeys'] as $subkey) {
						if($this->isValidKey($subkey, 'encrypt')) {
							$fingerprints[] = $subkey['fingerprint'];
							$found = true;
						}
					}
				}
			}

			if(!$found)
				throw new Exception_DevblocksEmailDeliveryError(sprintf('No recipient PGP public key for: %s', $recipient));
		}

		return $fingerprints;
	}

	protected function isValidKey($key, $purpose) : bool {
		return !(
			$key['disabled']
			|| $key['expired']
			|| $key['revoked']
			|| (
				$purpose == 'sign'
				&& !$key['can_sign']
				)
			|| (
				$purpose == 'encrypt'
				&& !$key['can_encrypt']
			)
		);
	}
}