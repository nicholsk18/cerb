<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2023, Webgroup Media LLC
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

class PageSection_ProfilesMailRoutingRule extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // mail_routing_rule
		@$context_id = intval(array_shift($stack)); // 123
		
		$context = Context_MailRoutingRule::ID;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('profileAction' == $scope) {
			switch($action) {
				case 'refreshRules':
					return $this->_profileAction_refreshRules();
				case 'savePeekJson':
					return $this->_profileAction_savePeekJson();
				case 'viewExplore':
					return $this->_profileAction_viewExplore();
			}
		}
		return false;
	}
	
	private function _profileAction_refreshRules() {
		$tpl = DevblocksPlatform::services()->template();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'text/html; charset=utf-8');
		
		try {
			$routing_rules = DAO_MailRoutingRule::getAll();
			$tpl->assign('routing_rules', $routing_rules);
			
			$tpl->display('devblocks:cerberusweb.core::records/types/mail_routing_rule/rules.tpl');
			
		} catch(Exception) {
			DevblocksPlatform::dieWithHttpError(500);
		}
	}
	
	private function _profileAction_savePeekJson() {
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');
		
		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer', 0);
		$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'] ?? null, 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", Context_MailRoutingRule::ID)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				if(!($model = DAO_MailRoutingRule::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));
				
				if(!Context_MailRoutingRule::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				CerberusContexts::logActivityRecordDelete(Context_MailRoutingRule::ID, $model->id, $model->name);
				
				DAO_MailRoutingRule::delete($id);
				
				echo json_encode([
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				]);
				return;
				
			} else {
				$is_disabled = DevblocksPlatform::importGPC($_POST['is_disabled'] ?? null, 'int', 0);
				$name = DevblocksPlatform::importGPC($_POST['name'] ?? null, 'string', '');
				$priority = DevblocksPlatform::importGPC($_POST['priority'] ?? null, 'integer', 0);
				$routing_kata = DevblocksPlatform::importGPC($_POST['routing_kata'] ?? null, 'string', '');
				
				$error = null;
				
				if(empty($id)) { // New
					$fields = [
						DAO_MailRoutingRule::CREATED_AT => time(),
						DAO_MailRoutingRule::IS_DISABLED => $is_disabled,
						DAO_MailRoutingRule::NAME => $name,
						DAO_MailRoutingRule::PRIORITY => $priority,
						DAO_MailRoutingRule::ROUTING_KATA => $routing_kata,
						DAO_MailRoutingRule::UPDATED_AT => time(),
					];
					
					if(!DAO_MailRoutingRule::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_MailRoutingRule::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$id = DAO_MailRoutingRule::create($fields);
					DAO_MailRoutingRule::onUpdateByActor($active_worker, $fields, $id);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, Context_MailRoutingRule::ID, $id);
					
				} else { // Edit
					$fields = [
						DAO_MailRoutingRule::IS_DISABLED => $is_disabled,
						DAO_MailRoutingRule::NAME => $name,
						DAO_MailRoutingRule::PRIORITY => $priority,
						DAO_MailRoutingRule::ROUTING_KATA => $routing_kata,
						DAO_MailRoutingRule::UPDATED_AT => time(),
					];
					
					if(!DAO_MailRoutingRule::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_MailRoutingRule::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_MailRoutingRule::update($id, $fields);
					DAO_MailRoutingRule::onUpdateByActor($active_worker, $fields, $id);
				}
				
				if($id) {
					// Versioning
					try {
						DAO_RecordChangeset::create(
							'mail_routing_rule',
							$id,
							[
								'routing_kata' => $fields[DAO_MailRoutingRule::ROUTING_KATA] ?? '',
							],
							$active_worker->id ?? 0
						);
						
					} catch (Exception $e) {
						DevblocksPlatform::logError('Error saving changeset: ' . $e->getMessage());
					}
					
					// Custom field saves
					$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'] ?? null, 'array', []);
					if(!DAO_CustomFieldValue::handleFormPost(Context_MailRoutingRule::ID, $id, $field_ids, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
				}
				
				echo json_encode([
					'status' => true,
					'context' => Context_MailRoutingRule::ID,
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
			
		} catch (Exception $e) {
			echo json_encode([
				'status' => false,
				'error' => 'An error occurred.',
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
