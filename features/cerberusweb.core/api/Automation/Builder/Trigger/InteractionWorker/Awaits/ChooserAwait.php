<?php
namespace Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits;

use _DevblocksValidationService;
use DevblocksPlatform;
use Model_AutomationContinuation;

class ChooserAwait extends AbstractAwait {
	function invoke(string $prompt_key, string $action, Model_AutomationContinuation $continuation) {
		return false;
	}

	function formatValue() {
		return $this->_value;
	}
	
	function validate(_DevblocksValidationService $validation) {
		$prompt_label = $this->_data['label'] ?? null;
		$is_required = array_key_exists('required', $this->_data) && $this->_data['required'];
		$multiple = array_key_exists('multiple', $this->_data) && $this->_data['multiple'];
		$record_type = $this->_data['record_type'] ?? null;
		
		$field = $validation->addField($this->_key, $prompt_label);
		
		if($multiple) {
			$field
				->idArray()
				->setRequired($is_required)
				->addValidator($validation->validators()->contextIds($record_type, !$is_required))
			;
		} else {
			$field
				->id()
				->setRequired($is_required)
				->addValidator($validation->validators()->contextId($record_type, !$is_required))
			;
		}
	}
	
	function render(Model_AutomationContinuation $continuation) {
		$tpl = DevblocksPlatform::services()->template();
		
		$label = $this->_data['label'] ?? null;
		
		$record_type = $this->_data['record_type'] ?? null;
		$query = $this->_data['query'] ?? null;
		$multiple = array_key_exists('multiple', $this->_data) && $this->_data['multiple'];
		$default = $this->_data['default'] ?? null;
		$autocomplete = array_key_exists('autocomplete', $this->_data) && $this->_data['autocomplete'];
		
		$tpl->assign('var', $this->_key);
		$tpl->assign('label', $label);
		$tpl->assign('record_type', $record_type);
		$tpl->assign('query', $query);
		$tpl->assign('multiple', $multiple);
		$tpl->assign('default', $default);
		$tpl->assign('autocomplete', $autocomplete);
		$tpl->assign('continuation_token', $continuation->token);
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/interaction.worker/await/chooser.tpl');
	}
}