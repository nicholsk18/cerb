<?php

namespace Cerb\Email\Crypto;

use Exception_DevblocksEmailDeliveryError;
use Symfony\Component\Mime\Message;

class PgpEncrypter extends Pgp {
	/*
	 * @throws Exception_DevblocksEmailDeliveryError
	 */
	public function encrypt(Message $message, \Model_DevblocksOutboundEmail $email_model) : Message
	{
		if (!($recipient_keys = $this->getRecipientKeys($email_model)))
			throw new Exception_DevblocksEmailDeliveryError('No recipient PGP public keys for encryption.');
		
		$encrypted_body = $this->encryptWithPGP($message->getBody()->toString(), $recipient_keys);
		
		$boundary_marker = bin2hex(random_bytes(12));
		
		$body = <<< EOD
This is an OpenPGP/MIME encrypted message (RFC 4880 and 3156)

--{$boundary_marker}
Content-Type: application/pgp-encrypted
Content-Description: PGP/MIME version identification

Version: 1

--{$boundary_marker}
Content-Type: application/octet-stream; name="encrypted.asc"
Content-Description: OpenPGP encrypted message
Content-Disposition: inline; filename="encrypted.asc"

$encrypted_body

--{$boundary_marker}--
EOD;

		return new Message(
			$message->getHeaders(),
			new PgpPart($body, 'multipart', 'encrypted', [
				'protocol' => 'application/pgp-encrypted',
				'boundary' => $boundary_marker,
			]),
		);
	}
}