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

class PageSection_ProfilesClassifierEntity extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // classifier_entity 
		@$context_id = intval(array_shift($stack)); // 123

		$context = CerberusContexts::CONTEXT_CLASSIFIER_ENTITY;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function savePeekJsonAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_CLASSIFIER_ENTITY)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				DAO_ClassifierEntity::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				@$name = DevblocksPlatform::importGPC($_REQUEST['name'], 'string', '');
				@$description = DevblocksPlatform::importGPC($_REQUEST['description'], 'string', '');
				@$type = DevblocksPlatform::importGPC($_REQUEST['type'], 'string', '');
				@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', []);
				
				// Validate types
				switch($type) {
					case 'list':
						@$labels = DevblocksPlatform::importVar($params[$type]['labels'], 'string', '');
						$lines = DevblocksPlatform::parseCrlfString($labels);
						$map = [];
						
						foreach($lines as $line) {
							$data = DevblocksPlatform::parseCsvString($line, false, null, 2);
							
							if(empty($data) || !is_array($data))
								continue;
							
							$key = $data[0];
							
							// If we only had a key, use the same ref for the value
							if(1 == count($data))
								$data[] = $key;
							
							if(!isset($map[$key]))
								$map[$key] = [];
							
							$map[$key][] = $data[1];
						}
						
						$params[$type]['map'] = $map;
						$params = $params[$type];
						break;
					
					case 'regexp':
						@$pattern = DevblocksPlatform::importVar($params[$type]['pattern'], 'string', '');
						
						if(empty($pattern))
							throw new Exception_DevblocksAjaxValidationError("A regular expression pattern is required.");
						
						if(false === @preg_match($pattern, null))
							throw new Exception_DevblocksAjaxValidationError("Invalid regular expression pattern.");
						
						$params = $params[$type];
						break;
					
					case 'text':
						$params = $params[$type];
						break;
				}
				
				if(empty($id)) { // New
					$fields = array(
						DAO_ClassifierEntity::DESCRIPTION => $description,
						DAO_ClassifierEntity::NAME => $name,
						DAO_ClassifierEntity::PARAMS_JSON => json_encode($params),
						DAO_ClassifierEntity::TYPE => $type,
						DAO_ClassifierEntity::UPDATED_AT => time(),
					);
					
					if(!DAO_ClassifierEntity::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_ClassifierEntity::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$id = DAO_ClassifierEntity::create($fields);
					DAO_ClassifierEntity::onUpdateByActor($active_worker, $fields, $id);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_CLASSIFIER_ENTITY, $id);
					
				} else { // Edit
					$fields = array(
						DAO_ClassifierEntity::DESCRIPTION => $description,
						DAO_ClassifierEntity::NAME => $name,
						DAO_ClassifierEntity::PARAMS_JSON => json_encode($params),
						DAO_ClassifierEntity::TYPE => $type,
						DAO_ClassifierEntity::UPDATED_AT => time(),
					);
					
					if(!DAO_ClassifierEntity::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_ClassifierEntity::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_ClassifierEntity::update($id, $fields);
					DAO_ClassifierEntity::onUpdateByActor($active_worker, $fields, $id);
				}
				
				// Custom field saves
				@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', []);
				if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_CLASSIFIER_ENTITY, $id, $field_ids, $error))
					throw new Exception_DevblocksAjaxValidationError($error);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'label' => $name,
					'view_id' => $view_id,
				));
				return;
			}
			
		} catch (Exception_DevblocksAjaxValidationError $e) {
			echo json_encode(array(
				'status' => false,
				'error' => $e->getMessage(),
				'field' => $e->getFieldName(),
			));
			return;
			
		} catch (Exception $e) {
			echo json_encode(array(
				'status' => false,
				'error' => 'An error occurred.',
			));
			return;
			
		}
	
	}
	
	function viewExploreAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::services()->url();
		
		// Generate hash
		$hash = md5($view_id.$active_worker->id.time());
		
		// Loop through view and get IDs
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);

		// Page start
		@$explore_from = DevblocksPlatform::importGPC($_REQUEST['explore_from'],'integer',0);
		if(empty($explore_from)) {
			$orig_pos = 1+($view->renderPage * $view->renderLimit);
		} else {
			$orig_pos = 1;
		}

		$view->renderPage = 0;
		$view->renderLimit = 250;
		$pos = 0;
		
		do {
			$models = array();
			list($results, $total) = $view->getData();

			// Summary row
			if(0==$view->renderPage) {
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'title' => $view->name,
					'created' => time(),
//					'worker_id' => $active_worker->id,
					'total' => $total,
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=classifier_entity', true),
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=classifier_entity&id=%d-%s", $row[SearchFields_ClassifierEntity::ID], DevblocksPlatform::strToPermalink($row[SearchFields_ClassifierEntity::NAME])), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_ClassifierEntity::ID],
					'url' => $url,
				);
				$models[] = $model;
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}
};
