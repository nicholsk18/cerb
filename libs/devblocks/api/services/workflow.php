<?php
class CerbWorkflowResults {
	const STATE_COMPLETE = 0;
	const STATE_UPDATING = 1;
	const STATE_ERROR = 2;
	
	public int $state = self::STATE_UPDATING;
	public array $resources = [];
	public string $error = '';
}

class _DevblocksWorkflowService {
	private static ?_DevblocksWorkflowService $_instance = null;
	
	static function getInstance() : _DevblocksWorkflowService {
		if(is_null(self::$_instance))
			self::$_instance = new _DevblocksWorkflowService();
		
		return self::$_instance;
	}
	
	private function __construct() {}
	
	private function _syncChanges(Model_Workflow $was_workflow, Model_Workflow $new_workflow) : CerbWorkflowResults {
		$automator = DevblocksPlatform::services()->automation();
		
		$error = null;
		$resource_keys = [];
		
		$initial_state = $new_workflow->getChangesAutomationInitialState();
		
		$results = new CerbWorkflowResults();
		
		if (false === ($results->resources = $was_workflow->getResources($error))) {
			$results->state = CerbWorkflowResults::STATE_ERROR;
			$results->error = '[Resources] ' . $error;
			$results->resources = [];
			return $results;
		}
		
		$automation = $was_workflow->getChangesAutomation($new_workflow, $resource_keys);
		
		if($automation instanceof Model_Automation && $automation->script) {
			$exit_state = null;
			
			if (false === ($automation_results = $automator->executeScript($automation, $initial_state, $error, $exit_state))) {
				$results->state = CerbWorkflowResults::STATE_ERROR;
				$results->error = '[Script] ' . $error;
				$automation_results = $exit_state;
			} else {
				$results->state = CerbWorkflowResults::STATE_COMPLETE;
			}
			
			foreach (($resource_keys['records'] ?? []) as $record_key => $record_changes) {
				if(!($record_action = $record_changes['action'] ?? ''))
					continue;
				
				if(in_array($record_action, ['delete', 'retain'])) {
					unset($results->resources['records'][$record_key]);
					
				} else if('create' == $record_action) {
					$record_name = DevblocksPlatform::services()->string()->strAfter($record_key, '/');
					
					$new_record = $automation_results->getKeyPath('records:' . $record_name, null, ':');
					
					if(!($new_record instanceof DevblocksDictionaryDelegate))
						continue;
					
					$new_record_id = $new_record->get('id', 0);
					
					if($new_record_id) {
						$results->resources['records'][$record_key] = intval($new_record_id);
					}
				}
			}
		}
		
		return $results;
	}
	
	public function import(Model_Workflow $new_workflow, ?Model_Worker $as_worker, string &$error = null) : Model_Workflow|false {
		$kata = DevblocksPlatform::services()->kata();
		
		// Modify a copy of the model, not the original
		$changed_workflow = clone $new_workflow;
		$was_workflow = null;
		
		if(false === ($metadata = $new_workflow->getParsedTemplate($error)))
			return false;
		
		$changed_workflow->name = $metadata['workflow']['name'] ?? $changed_workflow->name ?: uniqid('workflow_');
		$changed_workflow->description = $metadata['workflow']['description'] ?? $changed_workflow->description ?: '';
		
		$version = $metadata['workflow']['version'] ?? $changed_workflow->version ?: 0;
		$changed_workflow->version = is_numeric($version) ? $version : intval(strtotime($version));
		
		if($changed_workflow->id)
			$was_workflow = DAO_Workflow::get($changed_workflow->id);
			
		if(!$was_workflow && $changed_workflow->name)
			$was_workflow = DAO_Workflow::getByName($changed_workflow->name);
		
		if(!$was_workflow) {
			$was_workflow_id = DAO_Workflow::create([
				DAO_Workflow::NAME => $changed_workflow->name,
				DAO_Workflow::DESCRIPTION => $changed_workflow->description,
				DAO_Workflow::VERSION => $changed_workflow->version,
			]);
			
			$changed_workflow->id = $was_workflow_id;
			
			$was_workflow = DAO_Workflow::get($was_workflow_id);
		}
		
		$changed_workflow->id = $was_workflow->id;
		
		$results = $this->_syncChanges($was_workflow, $changed_workflow);
		
		$changed_workflow->resources_kata = $kata->emit($results->resources);
		
		if($results->state == CerbWorkflowResults::STATE_ERROR) {
			// On error, store the partial resource changes
			DAO_Workflow::update($changed_workflow->id, [
				DAO_Workflow::NAME => $changed_workflow->name,
				DAO_Workflow::DESCRIPTION => $changed_workflow->description,
				DAO_Workflow::RESOURCES_KATA => $changed_workflow->resources_kata,
				DAO_Workflow::VERSION => $changed_workflow->version,
			]);
			
			$error = $results->error;
			return false;
		}
		
		$has_extensions = $changed_workflow->getExtensionBits();
		
		$fields = [
			DAO_Workflow::CONFIG_KATA => $changed_workflow->config_kata,
			DAO_Workflow::DESCRIPTION => $changed_workflow->description,
			DAO_Workflow::HAS_EXTENSIONS => $has_extensions,
			DAO_Workflow::NAME => $changed_workflow->name,
			DAO_Workflow::RESOURCES_KATA => $changed_workflow->resources_kata,
			DAO_Workflow::UPDATED_AT => time(),
			DAO_Workflow::WORKFLOW_KATA => $changed_workflow->workflow_kata,
			DAO_Workflow::VERSION => $changed_workflow->version,
		];
		DAO_Workflow::update($changed_workflow->id, $fields);
		
		// Versioning
		try {
			DAO_RecordChangeset::create(
				'workflow',
				$changed_workflow->id,
				[
					'template' => $changed_workflow->workflow_kata,
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
		
		return $changed_workflow;
	}
}