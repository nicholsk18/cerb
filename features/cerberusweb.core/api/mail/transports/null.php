<?php
class CerbMailTransport_Null extends Extension_MailTransport {
	const ID = 'core.mail.transport.null';
	
	private ?string $_lastErrorMessage = null;
	private $_logger = null;
	
	function renderConfig(Model_MailTransport $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('model', $model);
		$tpl->assign('extension', $this);
		$tpl->display('devblocks:cerberusweb.core::internal/mail_transport/null/config.tpl');
	}
	
	function testConfig(array $params, &$error=null) : bool {
		return true;
	}
	
	function send(Model_DevblocksOutboundEmail $email_model, Model_MailTransport $model) : bool {
		try {
			$swift_message = CerberusMail::getSwiftMessageFromModel($email_model);
			
		} catch(Throwable $e) {
			DevblocksPlatform::logException($e);
			$this->_lastErrorMessage = "An unexpected error occurred.";
			return false;
		}
		
		if(($outgoing_message_id = $email_model->getProperty('outgoing_message_id'))) {
			$swift_message->getHeaders()->removeAll('Message-ID');
			$swift_message->getHeaders()->addIdHeader('Message-ID', $outgoing_message_id);
			unset($outgoing_message_id);
		}
		
		// X-Mailer
		$swift_message->getHeaders()->addTextHeader('X-Mailer', 'Cerb ' . APP_VERSION . ' (Build ' . APP_BUILD . ')');
		
		$to = $swift_message->getTo();
		$from = array_keys($swift_message->getFrom());
		$sender = reset($from);

		if(empty($to)) {
			$this->_lastErrorMessage = "At least one 'To:' recipient address is required.";
			return false;
		}
		
		if(empty($sender)) {
			$this->_lastErrorMessage = "A 'From:' sender address is required.";
			return false;
		}
		
		if(!($mailer = $this->_getMailer()))
			return false;
		
		$result = $mailer->send($swift_message);
		
		if(!$result) {
			$this->_lastErrorMessage = $this->_logger->getLastError();
		}
		
		$this->_logger->clear();
		
		return $result;
	}
	
	function getLastError() : ?string {
		return $this->_lastErrorMessage;
	}
	
	private function _getMailer() : Swift_Mailer {
		static $mailer = null;
		
		if(is_null($mailer)) {
			$null = new Swift_NullTransport();
			$mailer = new Swift_Mailer($null);
			
			$this->_logger = new Cerb_SwiftPlugin_TransportExceptionLogger();
			$mailer->registerPlugin($this->_logger);
		}
		
		return $mailer;
	}
}