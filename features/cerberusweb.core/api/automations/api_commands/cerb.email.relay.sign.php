<?php
class ApiCommand_CerbEmailRelaySign extends Extension_AutomationApiCommand {
	const ID = 'cerb.commands.email.relay.sign';
	
	function run(array $params=[], &$error=null) : array|false {
		$message_id = intval($params['message_id'] ?? null);
		$worker_id = intval($params['worker_id'] ?? null);
		
		$signature = CerberusMail::relaySign($message_id, $worker_id);
		
		return [
			'status' => true,
			'header_message_id' => $signature,
		];
	}
	
	public function getAutocompleteSuggestions($key_path, $prefix, $key_fullpath, $script) : array {
		if('' == $key_path) {
			return [
				'message_id@int:',
				'worker_id@int:',
			];
		}
		
		return [];
	}
}