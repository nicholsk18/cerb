<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2019, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
 ***********************************************************************/

class PageSection_ProfilesWorkflow extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // workflow 
		@$context_id = intval(array_shift($stack)); // 123
		
		$context = CerberusContexts::CONTEXT_WORKFLOW;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('profileAction' == $scope) {
			switch($action) {
				case 'savePeekJson':
					return $this->_profileAction_savePeekJson();
				case 'showTemplateUpdatePopup':
					return $this->_profileAction_showTemplateUpdatePopup();
				case 'showWorkflowDeletePopup':
					return $this->_profileAction_showWorkflowDeletePopup();
				case 'saveTemplateJson':
					return $this->_profileAction_saveTemplateJson();
				case 'saveConfigJson':
					return $this->_profileAction_saveConfigJson();
				case 'saveChangesJson':
					return $this->_profileAction_saveChangesJson();
				case 'viewExplore':
					return $this->_profileAction_viewExplore();
			}
		}
		return false;
	}
	
	private function _profileAction_savePeekJson() {
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');
		
		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer', 0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		try {
			$package_uri = DevblocksPlatform::importGPC($_POST['package'] ?? null, 'string', '');
			
			$mode = 'build';
			
			if(!$id && $package_uri)
				$mode = 'library';
			
			switch($mode) {
				case 'library':
					$prompts = DevblocksPlatform::importGPC($_POST['prompts'] ?? null, 'array', []);
					
					if (empty($package_uri))
						throw new Exception_DevblocksAjaxValidationError("You must select a package from the library.");
					
					if (!($package = DAO_PackageLibrary::getByUri($package_uri)))
						throw new Exception_DevblocksAjaxValidationError("You selected an invalid package.");
					
					if ($package->point != 'workflow')
						throw new Exception_DevblocksAjaxValidationError("The selected package is not for this extension point.");
					
					$package_json = $package->getPackageJson();
					$records_created = [];
					
					$prompts['current_worker_id'] = $active_worker->id;
					
					try {
						CerberusApplication::packages()->import($package_json, $prompts, $records_created);
						
					} catch (Exception_DevblocksValidationError $e) {
						throw new Exception_DevblocksAjaxValidationError($e->getMessage());
						
					} catch (Exception) {
						throw new Exception_DevblocksAjaxValidationError("An unexpected error occurred.");
					}
					
					if (!array_key_exists(CerberusContexts::CONTEXT_WORKFLOW, $records_created))
						throw new Exception_DevblocksAjaxValidationError("There was an issue creating the record.");
					
					$new_workflow = reset($records_created[CerberusContexts::CONTEXT_WORKFLOW]);
					
					if ($view_id)
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_WORKFLOW, $new_workflow['id']);
					
					echo json_encode([
						'status' => true,
						'context' => CerberusContexts::CONTEXT_WORKFLOW,
						'id' => $new_workflow['id'],
						'label' => $new_workflow['label'],
						'view_id' => $view_id,
					]);
					return;
					
				case 'build':
					$error = null;
					
					if (empty($id)) { // New
						$name = DevblocksPlatform::strAlphaNum(
							DevblocksPlatform::importGPC($_POST['_selection'] ?? null, 'string', 'workflow.empty'),
							'._'
						);
						
						$available_templates = [
							'cerb.auto_dispatcher',
							'cerb.auto_responder',
							'cerb.quickstart',
							'cerb.surveys.csat',
							'cerb.tutorial',
							'workflow.empty',
						];
						
						if(!in_array($name, $available_templates)) {
							$error = sprintf("Unknown workflow template `%s`", $name);
							throw new Exception_DevblocksAjaxValidationError($error);
						}
						
						// Check if this workflow is already installed
						if(DAO_Workflow::getByName($name)) {
							$error = sprintf("This workflow `%s` is already created.", $name);
							throw new Exception_DevblocksAjaxValidationError($error);
						}
						
						$fields = [
							DAO_Workflow::NAME => $name,
							DAO_Workflow::CREATED_AT => time(),
							DAO_Workflow::UPDATED_AT => time(),
						];
						
						if('workflow.empty' == $name) {
							$name = uniqid('new_workflow.');
							$fields[DAO_Workflow::NAME] = $name;
							$fields[DAO_Workflow::WORKFLOW_KATA] = sprintf("workflow:\n  name: %s\n  version: %s\n  description: A description of the workflow\n  requirements:\n    cerb_version: >=10.5 <11.0\n    cerb_plugins: cerberusweb.core, \n\nrecords:\n", $name, gmdate('Y-m-d\T00:00:00\Z'));
							
							if (!DAO_Workflow::validate($fields, $error))
								throw new Exception_DevblocksAjaxValidationError($error);
							
							if (!DAO_Workflow::onBeforeUpdateByActor($active_worker, $fields, null, $error))
								throw new Exception_DevblocksAjaxValidationError($error);
							
							$id = DAO_Workflow::create($fields);
							
						} else {
							$new_workflow = new Model_Workflow();
							$new_workflow->name = $name;
							$new_workflow->workflow_kata = match($name) {
								'cerb.auto_dispatcher' => file_get_contents(APP_PATH . '/features/cerberusweb.core/workflows/cerb.auto_dispatcher.kata'),
								'cerb.auto_responder' => file_get_contents(APP_PATH . '/features/cerberusweb.core/workflows/cerb.auto_responder.kata'),
								'cerb.quickstart' => file_get_contents(APP_PATH . '/features/cerberusweb.core/workflows/cerb.quickstart.kata'),
								'cerb.surveys.csat' => file_get_contents(APP_PATH . '/features/cerberusweb.core/workflows/cerb.surveys.csat.kata'),
								'cerb.tutorial' => file_get_contents(APP_PATH . '/features/cerberusweb.core/workflows/cerb.tutorial.kata'),
							};
							
							// Use the default config values until an admin configures it
							if(($config_options = $new_workflow->getConfigOptions()) && is_array($config_options)) {
								$new_workflow->setConfigValues(array_column($config_options, 'value', 'key'));
							}
							
							if(false === ($new_workflow = DevblocksPlatform::services()->workflow()->import($new_workflow, null, $error)))
								throw new Exception_DevblocksAjaxValidationError($error);
							
							$id = $new_workflow->id;
						}
						
						DAO_Workflow::onUpdateByActor($active_worker, $fields, $id);
						
						if (!empty($view_id) && !empty($id))
							C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_WORKFLOW, $id);
						
					} else { // Edit
						$fields = [
							DAO_Workflow::UPDATED_AT => time(),
						];
						
						if(!($model = DAO_Workflow::get($id)))
							DevblocksPlatform::dieWithHttpError(null, 404);
						
						$name = $model->name;
						
						if (!DAO_Workflow::validate($fields, $error, $id))
							throw new Exception_DevblocksAjaxValidationError($error);
						
						if (!DAO_Workflow::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
							throw new Exception_DevblocksAjaxValidationError($error);
						
						DAO_Workflow::update($id, $fields);
						DAO_Workflow::onUpdateByActor($active_worker, $fields, $id);
					}
					
					if ($id) {
						// Custom field saves
						$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'] ?? null, 'array', []);
						if (!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_WORKFLOW, $id, $field_ids, $error))
							throw new Exception_DevblocksAjaxValidationError($error);
					}
					
					echo json_encode([
						'status' => true,
						'context' => CerberusContexts::CONTEXT_WORKFLOW,
						'id' => $id,
						'label' => $name,
						'view_id' => $view_id,
					]);
					return;
			}
			
		} catch (Exception_DevblocksAjaxValidationError $e) {
			echo json_encode([
				'status' => false,
				'error' => $e->getMessage(),
				'field' => $e->getFieldName(),
			]);
			return;
			
		} catch (Exception) {
			echo json_encode([
				'status' => false,
				'error' => 'An error occurred.',
			]);
			return;
			
		}
	}
	
	private function _profileAction_showTemplateUpdatePopup() {
		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer', 0);
		
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		if(!$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		try {
			if(!($workflow = DAO_Workflow::get($id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
			// Autocomplete
			
			$autocomplete_suggestions = [
				'' => [
					'extensions:',
					'records:',
					'workflow:',
				],
				'extensions:' => [
					'activity:',
					'permission:',
					'translation:',
				],
				'extensions:activity:' => [
					'id: example.activity.id',
					'label: Custom Record Event',
					'message: {{actor}} performed custom event on {{target}}',
				],
				'extensions:permission:' => [
					'id: example.permission.id',
					'label: Custom Permission',
				],
				'extensions:translation:' => [
					'id: example.translation.id',
					'langs:',
				],
				'extensions:translation:langs:' => [
					'en_US: This is a translated string',
					'en_US@raw: This is a translated string with {{placeholders}}',
				],
				'records:' => [],
				'workflow:' => [
					'config:',
					'description: A description of the workflow',
					'name: example.workflow.id',
					'requirements:',
					'version: ' . gmdate('Y-m-d\T00:00:00\Z'),
				],
				'workflow:config:' => [
					'chooser/key:',
					'text/key:',
				],
				'workflow:config:chooser:' => [
					'default:',
					'label:',
					'record_query:',
					'record_type:',
					'multiple@bool: no',
				],
				'workflow:config:text:' => [
					'default:',
					'label:',
					'multiple@bool: yes',
				],
				'workflow:requirements:' => [
					'cerb_version: >=10.5 <11.0',
					'cerb_plugins: cerberusweb.core, ',
				],
				'workflow:version:' => [
					'2025-12-31T00:00:00Z',
				]
			];
			
			$record_types = Extension_DevblocksContext::getAll(true, ['records']);
			
			$autocomplete_suggestions['workflow:config:chooser:record_type:'] = array_values(array_map(
				fn($record_type) => $record_type->manifest->params['alias'],
				$record_types
			));
			
			foreach($record_types as $record_type) {
				$autocomplete_suggestions['records:'][] = [
					'caption' => $record_type->manifest->params['alias'] . ':',
					'snippet' => $record_type->manifest->params['alias'] . '/${1:resourceName}:',
				];
				
				$key = 'records:' . $record_type->manifest->params['alias'] . ':';
				$autocomplete_suggestions[$key][] = 'fields:';
				$autocomplete_suggestions[$key][] = 'deletionPolicy:';
				$autocomplete_suggestions[$key . 'deletionPolicy:'] = ['delete', 'retain'];
				$autocomplete_suggestions[$key][] = 'updatePolicy:';
				$autocomplete_suggestions[$key . 'updatePolicy:'] = ['field_name, other_field_name'];
				
				// [TODO] Is this cached for autocompletion already?

//					$dao_class = $record_type->getDaoClass();
//					$dao_fields = $dao_class::getFields();
				
				// [TODO] Required fields with higher score
				// [TODO] Field values autocompletion
				$fields_map = $record_type->getKeyToDaoFieldMap();

//					foreach($fields_map as $dict_key => $dao_key) {
//						DevblocksPlatform::logError(json_encode($dao_fields[$dao_key]));
//					}
				
				$key = 'records:' . $record_type->manifest->params['alias'] . ':fields:';
				$autocomplete_suggestions[$key] = array_map(fn($key) => $key . ':', array_keys($fields_map));
			}
			
			$tpl->assign('autocomplete_suggestions', $autocomplete_suggestions);
			
			$tpl->assign('model', $workflow);
			$tpl->display('devblocks:cerberusweb.core::records/types/workflow/update_template/popup.tpl');
			
		} catch (Exception) {
			DevblocksPlatform::dieWithHttpError(null, 500);
		}
	}
	
	private function _profileAction_showWorkflowDeletePopup() {
		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer', 0);
		
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$error = null;
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		if(!$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		try {
			if(!($workflow = DAO_Workflow::get($id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
			$resource_keys = [];
			
			$new_workflow = clone $workflow;
			$new_workflow->workflow_kata = '';
			$new_workflow->config_kata = '';
			
			$resources = $workflow->getResourceRecordDictionaries($error);
			
			$workflow->getChangesAutomation($new_workflow, $resource_keys);
			
			$sheet_kata = <<< EOD
              layout:
                headings@bool: yes
                paging@bool: no
              limit: 1000
              columns:
                text/__action:
                  label: Action
                text/__key:
                  label: Key
                card/_label:
                  label: Record
                  params:
                    bold@bool: yes
              EOD;
			
			$sheets = DevblocksPlatform::services()->sheet()->withDefaultTypes();
			
			if(!($sheet = $sheets->parse($sheet_kata, $error)))
				throw new Exception_DevblocksAjaxValidationError('Error: ' . $error);
			
			$record_dicts = array_values(array_map(
				function($rk) use ($resource_keys, $resources) {
					$action = $resource_keys['records'][$rk]['action'];
					
					$dict = $resources[$rk] ?? DevblocksDictionaryDelegate::instance([]);
					
					$dict->set('__action', $action);
					$dict->set('__key', $rk);
					
					return $dict;
				},
				array_keys($resource_keys['records'] ?? []),
			));
			
			$layout = $sheets->getLayout($sheet);
			$columns = $sheets->getColumns($sheet);
			$rows = $sheets->getRows($sheet, $record_dicts);
			
			$tpl->assign('layout', $layout);
			$tpl->assign('columns', $columns);
			$tpl->assign('rows', $rows);
			
			$tpl->assign('model', $workflow);
			$tpl->display('devblocks:cerberusweb.core::records/types/workflow/delete_workflow/popup.tpl');
			
		} catch (Exception) {
			DevblocksPlatform::dieWithHttpError(null, 500);
		}
	}
	
	private function _profileAction_saveTemplateJson() {
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$tpl = DevblocksPlatform::services()->template();
		$kata = DevblocksPlatform::services()->kata();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer', 0);
		$template = DevblocksPlatform::importGPC($_POST['template']['kata'] ?? null, 'string', '');
		$config_values_form = DevblocksPlatform::importGPC($_POST['config_values'] ?? [], 'array', []);
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		$error = null;
		
		try {
			if(!($active_worker = CerberusApplication::getActiveWorker()))
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			if('POST' != DevblocksPlatform::getHttpMethod())
				DevblocksPlatform::dieWithHttpError(null, 405);
			
			if(!$active_worker->is_superuser)
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			if(!($workflow = DAO_Workflow::get($id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
			$template_validate = $template;

			// Replace placeholders before schema validation
			if(str_contains($template_validate, '$${')) {
				$lexer = [
					'tag_comment'   => ['$$#', '#'],
					'tag_block'     => ['$$%', '%'],
					'tag_variable'  => ['$${', '}'],
					'interpolation' => ['#$${', '}'],
				];

				if(false === ($template_validate = $tpl_builder->build($template, [], $lexer)))
					throw new Exception_DevblocksValidationError($tpl_builder->getLastError());
			}

			if(false === $kata->validate($template_validate, CerberusApplication::kataSchemas()->workflow(), $error))
				throw new Exception_DevblocksValidationError($error);
			
			// Update template changes
			$workflow->workflow_kata = $template;
			
			if(false === ($new_template = $workflow->getParsedTemplate($error)))
				throw new Exception_DevblocksValidationError($error);
			
			// Check requirements or error out
			if(is_array($new_template['workflow']['requirements'] ?? null))
				if(!$workflow->checkRequirements($new_template['workflow']['requirements'], $error))
					throw new Exception_DevblocksValidationError($error);
			
			$update_fields = [];
			
			if(($new_template['workflow']['name'] ?? null) && $new_template['workflow']['name'] != $workflow->name) {
				$workflow->name = $new_template['workflow']['name'] ?? uniqid('workflow_');
				$update_fields[DAO_Workflow::NAME] = $workflow->name;
			}
			
			if(($new_template['workflow']['description'] ?? null) && $new_template['workflow']['description'] != $workflow->description) {
				$workflow->description = $new_template['workflow']['description'] ?? '';
				$update_fields[DAO_Workflow::DESCRIPTION] = $workflow->description;
			}
			
			if($workflow->id && $update_fields) {
				if(!DAO_Workflow::validate($update_fields, $error, $workflow->id)) {
					throw new Exception_DevblocksValidationError($error);
				}
				
				DAO_Workflow::update($workflow->id, $update_fields);
			}

			// Load config options
			if(false === ($workflow_config = $workflow->getConfig($error)))
				throw new Exception_DevblocksValidationError($error);

			// Merge saved values and current form state
			$config_values = array_merge($workflow_config, $config_values_form);

			// Possible config options from workflow
			$config_options = $workflow->getConfigOptions($config_values) ?: [];
			$tpl->assign('config_options', $config_options);
			
			$tpl->assign('model', $workflow);
			$html = $tpl->fetch('devblocks:cerberusweb.core::records/types/workflow/update_template/params.tpl');
			
			echo json_encode([
				'workflow_name' => $workflow->name,
				'html' => $html,
			]);
			
		} catch (Exception_DevblocksValidationError $e) {
			echo json_encode([
				'error' => $e->getMessage(),
			]);
			
		} catch (Exception $e) {
			DevblocksPlatform::logException($e);
			
			echo json_encode([
				'error' => 'An unexpected error occurred.',
			]);
			return;
		}
	}
	
	private function _profileAction_saveConfigJson() {
		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer', 0);
		$workflow_kata = DevblocksPlatform::importGPC($_POST['template']['kata'] ?? null, 'string', '');
		$config_values = DevblocksPlatform::importGPC($_POST['config_values'] ?? [], 'array', []);

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		$tpl = DevblocksPlatform::services()->template();
		$kata = DevblocksPlatform::services()->kata();
		
		try {
			if(!($active_worker = CerberusApplication::getActiveWorker()))
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			if('POST' != DevblocksPlatform::getHttpMethod())
				DevblocksPlatform::dieWithHttpError(null, 405);
			
			if(!$active_worker->is_superuser)
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			if(!($was_workflow = DAO_Workflow::get($id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
			$new_workflow = clone $was_workflow;
			$new_workflow->workflow_kata = $workflow_kata;
			$new_workflow->setConfigValues($config_values);
			
			$resource_keys = [];
			
			
			$changes_automation = $was_workflow->getChangesAutomation($new_workflow, $resource_keys);
			
			// [TODO] Display that no changes will be made and disable 'next'
			
			// Summarize the changes that will be completed as a sheet
			
			// [TODO] Use the full sheet width on 'changes'; titleColumn action+record
			$sheet_kata = <<< EOD
              layout:
                headings@bool: yes
                paging@bool: no
              limit: 1000
              columns:
                text/action:
                  label: Action
                text/key:
                  label: Key
                markdown/changes:
                  label: Changes
              EOD;
			
			$sheets = DevblocksPlatform::services()->sheet()->withDefaultTypes();
			
			if(!($sheet = $sheets->parse($sheet_kata, $error)))
				throw new Exception_DevblocksAjaxValidationError('Sheet Parse Error: ' . $error);
			
			if(false === ($changes_kata = $kata->parse($changes_automation->script, $error)))
				throw new Exception_DevblocksAjaxValidationError('KATA Parse Error: ' . $error);
			
			if(false === ($changes_kata = $kata->formatTree($changes_kata, null, $error, true)))
				throw new Exception_DevblocksAjaxValidationError('KATA Format Error: ' . $error);
			
			$record_dicts = array_values(array_map(
				function($rk) use ($resource_keys, $changes_kata, $kata) {
					$action = $resource_keys['records'][$rk]['action'];
					$resource_name = DevblocksPlatform::services()->string()->strAfter($rk, '/');
					$changes = '';
					
					if('update' == $action) {
						$changes = "```\n";
						$changes .= $kata->emit($changes_kata['start']['record.update/'.$resource_name]['inputs']['fields'] ?? []);
						$changes .= "\n```";
					}
					
					return DevblocksDictionaryDelegate::instance([
						'key' => $rk,
						'action' => $action,
						'changes' => $changes,
					]);
				},
				array_keys($resource_keys['records'] ?? []),
			));
			
			// Include `extensions` changes
			$extension_dicts = array_values(array_map(
				function($rk) use ($resource_keys, $changes_kata, $kata) {
					$action = $resource_keys['extensions'][$rk]['action'];
					$changes = '';
					
					if('update' == $action && ($resource_keys['extensions'][$rk]['delta'] ?? null)) {
						$changes = "```\n";
						$changes .= $kata->emit($resource_keys['extensions'][$rk]['delta']);
						$changes .= "\n```";
					}
					
					return DevblocksDictionaryDelegate::instance([
						'key' => $rk,
						'action' => $action,
						'changes' => $changes,
					]);
				},
				array_keys($resource_keys['extensions'] ?? []),
			));
			
			$dicts = array_merge($record_dicts, $extension_dicts);
			
			$layout = $sheets->getLayout($sheet);
			$columns = $sheets->getColumns($sheet);
			$rows = $sheets->getRows($sheet, $dicts);
			
			$tpl->assign('model', $new_workflow);
			
			$tpl->assign('layout', $layout);
			$tpl->assign('columns', $columns);
			$tpl->assign('rows', $rows);
			
			$html = $tpl->fetch('devblocks:cerberusweb.core::records/types/workflow/update_template/changes.tpl');
			
			echo json_encode([
				'html' => $html,
			]);
			
		} catch (Exception_DevblocksAjaxValidationError $e) {
			echo json_encode([
				'error' => $e->getMessage(),
			]);
			return;
		}
	}
	
	private function _profileAction_saveChangesJson() {
		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer', 0);
		$delete = DevblocksPlatform::importGPC($_POST['delete'] ?? null, 'integer', 0);

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		$workflows = DevblocksPlatform::services()->workflow();
		
		try {
			if(!($active_worker = CerberusApplication::getActiveWorker()))
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			if('POST' != DevblocksPlatform::getHttpMethod())
				DevblocksPlatform::dieWithHttpError(null, 405);
			
			if(!$active_worker->is_superuser)
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			if(!($was_workflow = DAO_Workflow::get($id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
			if($delete) {
				$workflow_kata = '';
				$config_values = [];
			} else {
				$workflow_kata = DevblocksPlatform::importGPC($_POST['template']['kata'] ?? null, 'string', '');
				$config_values = DevblocksPlatform::importGPC($_POST['config_values'] ?? [], 'array', []);
			}
			
			$new_workflow = clone $was_workflow;
			$new_workflow->workflow_kata = $workflow_kata;
			$new_workflow->setConfigValues($config_values);
		
			if($delete) {
				CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_WORKFLOW, $id, $was_workflow->name);
				
				// Bit flags for extension types
				$has_extensions = $was_workflow->getExtensionBits();
				
				if(false === ($workflows->import($new_workflow, $active_worker, $error))) {
					throw new Exception_DevblocksAjaxValidationError($error);
				}
				
				DAO_Workflow::delete($id);
				
				// Conditionally clear cached extensions
				if($has_extensions & Model_Workflow::HAS_PERMISSIONS)
					DevblocksPlatform::clearCache(DevblocksEngine::CACHE_ACL);
				if($has_extensions & Model_Workflow::HAS_ACTIVITIES)
					DevblocksPlatform::clearCache(DevblocksEngine::CACHE_ACTIVITY_POINTS);
				if($has_extensions & Model_Workflow::HAS_TRANSLATIONS)
					DevblocksPlatform::services()->cache()->removeByTags(['translations']);
				
			} else {
				$error = null;
				
				if(false === ($workflows->import($new_workflow, $active_worker, $error))) {
					throw new Exception_DevblocksAjaxValidationError($error);
				}
			}
			
			echo json_encode([
				'success' => true,
			]);
			
		} catch (Exception_DevblocksAjaxValidationError $e) {
			echo json_encode([
				'error' => $e->getMessage(),
			]);
			return;
		}
	}
	
	private function _profileAction_viewExplore() {
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');
		$explore_from = DevblocksPlatform::importGPC($_POST['explore_from'] ?? null, 'int', 0);
		
		$http_response = Cerb_ORMHelper::generateRecordExploreSet($view_id, $explore_from);
		DevblocksPlatform::redirect($http_response);
	}
};
