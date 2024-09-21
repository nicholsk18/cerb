<?php

namespace Cerb\Email\Crypto;

use DevblocksPlatform;
use Exception_DevblocksEmailDeliveryError;
use Model_DevblocksOutboundEmail;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Message;

class PgpSigner extends Pgp {
	/*
	 * @throws Exception_DevblocksEmailDeliveryError
	 */
	public function sign(Email $message, Model_DevblocksOutboundEmail $email_model) : Message
	{
		$micalg = 'SHA256';
		
		if (!($sign_key = $this->getSignKey($email_model))) {
			throw new Exception_DevblocksEmailDeliveryError(sprintf("No signing key"));
		}
		
		// Tamper headers ['Content-Type', 'Content-Transfer-Encoding', 'Content-Disposition', 'Content-Description'];
		
		$original_message = new Email(clone $message->getHeaders(), $message->getBody());
		$original_message->getHeaders()->remove('Message-Id');
		$original_message->getHeaders()->remove('Date');
		$original_message->getHeaders()->remove('Subject');
		$original_message->getHeaders()->remove('MIME-Version');
		$original_message->getHeaders()->remove('To');
		$original_message->getHeaders()->remove('From');
		
		$signed_message = $original_message->getHeaders()->toString() . $original_message->getBody()->toString();
		
		$signature = $this->signWithPGP($signed_message, $sign_key);
		
		$boundary_marker = bin2hex(random_bytes(12));
		
		$body = <<< EOD
This is an OpenPGP/MIME signed message (RFC 4880 and 3156)

--{$boundary_marker}
{$signed_message}
--{$boundary_marker}
Content-Type: application/pgp-signature; name="signature.asc"
Content-Description: OpenPGP digital signature
Content-Disposition: attachment; filename="signature.asc"

$signature

--{$boundary_marker}--
EOD;
			
		return new Message(
			$message->getHeaders(),
			new PgpPart($body, 'multipart', 'signed', [
				'micalg' => sprintf('pgp-%s', DevblocksPlatform::strLower($micalg)),
				'protocol' => 'application/pgp-signature',
				'boundary' => $boundary_marker,
			]),
		);
	}
}