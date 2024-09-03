<?php
class Exception_CerbWorkflowError extends Exception_Devblocks {}

class _DevblocksWorkflowService {
	private static ?_DevblocksWorkflowService $_instance = null;
	
	static function getInstance() : _DevblocksWorkflowService {
		if(is_null(self::$_instance))
			self::$_instance = new _DevblocksWorkflowService();
		
		return self::$_instance;
	}
	
	private function __construct() {}
	
	private function _syncChanges(Model_Workflow $was_workflow, Model_Workflow $new_workflow) : array {
		$automator = DevblocksPlatform::services()->automation();
		
		$error = null;
		$resource_keys = [];
		
		$initial_state = $new_workflow->getChangesAutomationInitialState();
		
		if (false === ($resources = $was_workflow->getResources($error)))
			throw new Exception_CerbWorkflowError('[Resources] ' . $error);
		
		$automation = $was_workflow->getChangesAutomation($new_workflow, $resource_keys);
		
		if($automation instanceof Model_Automation && $automation->script) {
			if (false === ($automation_results = $automator->executeScript($automation, $initial_state, $error)))
				throw new Exception_CerbWorkflowError('[ERROR] ' . $error);
			
			foreach (($resource_keys['records'] ?? []) as $record_key => $record_changes) {
				if(!($record_action = $record_changes['action'] ?? ''))
					continue;
				
				if(in_array($record_action, ['delete', 'retain'])) {
					if (is_array($resources))
						unset($resources['records'][$record_key]);
					
				} else if('create' == $record_action) {
					$record_name = DevblocksPlatform::services()->string()->strAfter($record_key, '/');
					
					$new_record = $automation_results->getKeyPath('records:' . $record_name, null, ':');
					
					if(!($new_record instanceof DevblocksDictionaryDelegate))
						continue;
					
					$new_record_id = $new_record->get('id', 0);
					
					if(is_array($resources) && $new_record_id) {
						$resources['records'][$record_key] = intval($new_record_id);
					}
				}
			}
		}
		
		return $resources;
	}
	
	public function import(Model_Workflow $new_workflow, ?Model_Worker $as_worker, string &$error = null) : bool {
		$kata = DevblocksPlatform::services()->kata();
		
		if(null == ($was_workflow = DAO_Workflow::getByName($new_workflow->name))) {
			$was_workflow_id = DAO_Workflow::create([
				DAO_Workflow::NAME => $new_workflow->name,
			]);
			
			$was_workflow = DAO_Workflow::get($was_workflow_id);
		}
		
		$new_workflow->id = $was_workflow->id;
		
		try {
			$resources = $this->_syncChanges($was_workflow, $new_workflow);
		} catch (Exception_CerbWorkflowError $e) {
			$error = $e->getMessage();
			return false;
		}
		
		$new_workflow->resources_kata = $kata->emit($resources);
		
		$has_extensions = $new_workflow->getExtensionBits();
		
		$fields = [
			DAO_Workflow::CONFIG_KATA => $new_workflow->config_kata,
			DAO_Workflow::DESCRIPTION => $new_workflow->description,
			DAO_Workflow::HAS_EXTENSIONS => $has_extensions,
			DAO_Workflow::RESOURCES_KATA => $new_workflow->resources_kata,
			DAO_Workflow::UPDATED_AT => time(),
			DAO_Workflow::WORKFLOW_KATA => $new_workflow->workflow_kata,
		];
		DAO_Workflow::update($new_workflow->id, $fields);
		
		// Versioning
		try {
			DAO_RecordChangeset::create(
				'workflow',
				$new_workflow->id,
				[
					'template' => $fields[DAO_Workflow::WORKFLOW_KATA] ?? '',
				],
				$as_worker->id ?? 0
			);
			
		} catch (Exception $e) {
			DevblocksPlatform::logError('Error saving workflow changeset: ' . $e->getMessage());
		}
		
		// Conditionally clear cached extensions
		if($has_extensions & Model_Workflow::HAS_PERMISSIONS)
			DevblocksPlatform::clearCache(DevblocksEngine::CACHE_ACL);
		if($has_extensions & Model_Workflow::HAS_ACTIVITIES)
			DevblocksPlatform::clearCache(DevblocksEngine::CACHE_ACTIVITY_POINTS);
		if($has_extensions & Model_Workflow::HAS_TRANSLATIONS)
			DevblocksPlatform::services()->cache()->removeByTags(['translations']);
		
		return true;
	}
}