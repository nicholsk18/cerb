<?php
class CerbMailTransport_Smtp extends Extension_MailTransport {
	const ID = 'core.mail.transport.smtp';
	
	private $_lastErrorMessage = null;
	private $_logger = null;
	
	function renderConfig(Model_MailTransport $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('model', $model);
		$tpl->assign('extension', $this);
		$tpl->display('devblocks:cerberusweb.core::internal/mail_transport/smtp/config.tpl');
	}
	
	function testConfig(array $params, &$error=null) {
		$host = $params['host'] ?? null;
		$port = $params['port'] ?? null;
		$encryption = $params['encryption'] ?? null;
		$auth_enabled = $params['auth_enabled'] ?? null;
		$auth_user = $params['auth_user'] ?? null;
		$auth_pass = $params['auth_pass'] ?? null;
		$connected_account_id = $params['connected_account_id'] ?? 0;
		
		if(empty($host)) {
			$error = 'The SMTP "host" parameter is required.';
			return false;
		}
		
		if(empty($port)) {
			$error = 'The SMTP "port" parameter is required.';
			return false;
		}
		
		// Try connecting
		
		$options = [
			'host' => $host,
			'port' => $port,
			'enc' => $encryption,
			'timeout' => 10,
		];
		
		if($auth_enabled) {
			$options['auth_user'] = $auth_user;
			$options['auth_pass'] = $auth_pass;
		}
		
		if($connected_account_id && $auth_enabled) {
			$options['connected_account_id'] = $connected_account_id;
		}
		
		try {
			if(null == ($mailer = $this->_getMailer($options)))
				throw new Exception("Failed to start mailer");
			
			if(null == ($transport = $mailer->getTransport()))
				throw new Exception("Failed to start mailer transport");
			
			$transport->start();
			$transport->stop();
			return true;
			
		} catch(Exception $e) {
			$error = $e->getMessage();
			return false;
		}
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
		
		$options = [
			'host' => $model->params['host'] ?? null,
			'port' => $model->params['port'] ?? null,
			'auth_user' => $model->params['auth_user'] ?? null,
			'auth_pass' => $model->params['auth_pass'] ?? null,
			'enc' => $model->params['encryption'] ?? null,
			'max_sends' => $model->params['max_sends'] ?? null,
			'timeout' => $model->params['timeout'] ?? null,
			'connected_account_id' => $model->params['connected_account_id'] ?? null,
		];
		
		// Error messages are inherited
		if(!($mailer = $this->_getMailer($options)))
			return false;
		
		if(!($result = $mailer->send($swift_message)))
			$this->_lastErrorMessage = $this->_logger->getLastError();
		
		$this->_logger->clear();
		
		return $result;
	}
	
	function getLastError() : ?string {
		return $this->_lastErrorMessage;
	}
	
	private function _getMailer(array $options) : ?Swift_Mailer {
		static $connections = [];
		
		// Options
		$smtp_host = $options['host'] ?? '127.0.0.1';
		$smtp_port = $options['port'] ?? '25';
		$smtp_user = $options['auth_user'] ?? null;
		$smtp_pass = $options['auth_pass'] ?? null;
		$smtp_enc = $options['enc'] ?? 'None';
		$smtp_max_sends = intval($options['max_sends'] ?? 20);
		$smtp_timeout = intval($options['timeout']) ?? 30;
		$smtp_connected_account_id = intval($options['connected_account_id'] ?? 0);
		
		/*
		 * [JAS]: We'll cache connection info hashed by params and hold a persistent
		 * connection for the request cycle.  If we ask for the same params again
		 * we'll get the existing connection if it exists.
		 */

		$hash = sha1(json_encode([
			$smtp_host,
			$smtp_user,
			$smtp_pass,
			$smtp_port,
			$smtp_enc,
			$smtp_max_sends,
			$smtp_timeout,
			$smtp_connected_account_id
		]));
		
		if(!isset($connections[$hash])) {
			// Encryption
			$smtp_enc = match ($smtp_enc) {
				'TLS' => 'tls',
				'SSL' => 'ssl',
				default => null,
			};
			
			$smtp = new Swift_SmtpTransport($smtp_host, $smtp_port, $smtp_enc);
			$smtp->setTimeout($smtp_timeout);
			
			// Is XOAUTH2 enabled?
			if($smtp_user && $smtp_connected_account_id) {
				$connected_account = DAO_ConnectedAccount::get($smtp_connected_account_id);
				
				if(!($service_extension = $connected_account->getServiceExtension())) {
					$this->_lastErrorMessage = "Failed to load the connected service extension";
					return null;
				}
				
				if(!($service_extension instanceof ServiceProvider_OAuth2)) {
					$this->_lastErrorMessage = "The connected account is not an OAuth2 provider";
					return null;
				}
				
				/** @var $service_extension ServiceProvider_OAuth2 */
				if(!($access_token = $service_extension->getAccessToken($connected_account))) {
					$this->_lastErrorMessage = "Failed to load the access token";
					return null;
				}
				
				$smtp->setAuthMode('XOAUTH2');
				$smtp->setUsername($smtp_user);
				$smtp->setPassword($access_token->getToken());
				
			} else if($smtp_user) {
				$smtp->setUsername($smtp_user);
				$smtp->setPassword($smtp_pass);
			}
			
			$mailer = new Swift_Mailer($smtp);
			$mailer->registerPlugin(new Swift_Plugins_AntiFloodPlugin($smtp_max_sends, 1));
			
			$this->_logger = new Cerb_SwiftPlugin_TransportExceptionLogger();
			$mailer->registerPlugin($this->_logger);
			
			$connections[$hash] = $mailer;
		}
		
		if($connections[$hash])
			return $connections[$hash];
		
		return null;
	}
}
