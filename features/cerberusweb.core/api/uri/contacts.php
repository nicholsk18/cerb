<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2015, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerbweb.com	    http://www.webgroupmedia.com/
***********************************************************************/

class ChContactsPage extends CerberusPageExtension {
	function getActivity() {
		return new Model_Activity('activity.address_book');
	}
	
	function isVisible() {
		// The current session must be a logged-in worker to use this page.
		if(null == ($worker = CerberusApplication::getActiveWorker()))
			return false;
		
		return $worker->hasPriv('core.addybook');
	}
	
	function render() {
	}
	
	function viewAddysExploreAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::getUrlService();
		
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
					'worker_id' => $active_worker->id,
					'total' => $total,
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=contacts&tab=addresses', true),
//					'toolbar_extension_id' => '',
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $org_id => $row) {
				if($org_id==$explore_from)
					$orig_pos = $pos;
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_Address::ID],
					'url' => $url_writer->writeNoProxy(sprintf("c=profiles&type=address&id=%d-%s", $row[SearchFields_Address::ID], $row[SearchFields_Address::EMAIL]), true),
				);
				$models[] = $model;
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}
	
	function getTopContactsByOrgJsonAction() {
		@$org_name = DevblocksPlatform::importGPC($_REQUEST['org_name'],'string');
		
		header('Content-type: text/json');
		
		if(empty($org_name) || null == ($org_id = DAO_ContactOrg::lookup($org_name, false))) {
			echo json_encode(array());
			exit;
		}
		
		// Match org, ignore banned
		$results = DAO_Address::getWhere(
			sprintf("%s = %d AND %s = %d AND %s = %d",
				DAO_Address::CONTACT_ORG_ID,
				$org_id,
				DAO_Address::IS_BANNED,
				0,
				DAO_Address::IS_DEFUNCT,
				0
			),
			DAO_Address::NUM_NONSPAM,
			true,
			25
		);
		
		$list = array();
		
		foreach($results as $result) { /* @var $result Model_Address */
			$list[] = array(
				'id' => $result->id,
				'email' => $result->email,
				'name' => DevblocksPlatform::strEscapeHtml($result->getName()),
			);
		}
		
		echo json_encode($list);
		
		exit;
	}
	
	function viewOrgsExploreAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::getUrlService();
		
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
					'worker_id' => $active_worker->id,
					'total' => $total,
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&tab=org', true),
//					'toolbar_extension_id' => '',
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $org_id => $row) {
				if($org_id==$explore_from)
					$orig_pos = $pos;
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_ContactOrg::ID],
					'url' => $url_writer->writeNoProxy(sprintf("c=profiles&type=org&id=%d", $row[SearchFields_ContactOrg::ID]), true),
				);
				$models[] = $model;
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}
	
	// [TODO] This should show members (contacts) and email addresses
	function showTabPeopleAction() {
		@$org = DevblocksPlatform::importGPC($_REQUEST['org']);
		
		$tpl = DevblocksPlatform::getTemplateService();
				
		$contact = DAO_ContactOrg::get($org);
		$tpl->assign('contact', $contact);
		
		$defaults = C4_AbstractViewModel::loadFromClass('View_Address');
		$defaults->id = 'org_contacts';
		$defaults->view_columns = array(
			SearchFields_Address::CONTACT_ID,
			SearchFields_Address::NUM_NONSPAM,
		);
		
		$view = C4_AbstractViewLoader::getView('org_contacts', $defaults);
		$view->name = 'Contacts: ' . (!empty($contact) ? $contact->name : '');
		$view->addParamsRequired(array(
			new DevblocksSearchCriteria(SearchFields_Address::CONTACT_ORG_ID,'=',$org)
		), true);
		$tpl->assign('view', $view);
		
		$tpl->assign('org_id', $org);
		$tpl->assign('search_columns', SearchFields_Address::getFields());
		
		$tpl->display('devblocks:cerberusweb.core::profiles/organization/tab_people.tpl');
		exit;
	}
	
	function saveOrgAddPeoplePopupAction() {
		@$org_id = DevblocksPlatform::importGPC($_REQUEST['org_id'], 'integer');
		@$address_ids = DevblocksPlatform::importGPC($_REQUEST['address_ids'], 'array:integer');
		
		if(empty($org_id) || empty($address_ids))
			return;
		
		if(false == ($org = DAO_ContactOrg::get($org_id)))
			return;
		
		DAO_Address::update($address_ids, array(
			DAO_Address::CONTACT_ORG_ID => $org_id,
		));
	}
	
	function showTabMailHistoryAction() {
		$translate = DevblocksPlatform::getTranslationService();
	
		@$point = DevblocksPlatform::importGPC($_REQUEST['point'],'string','contact.history');
		@$ephemeral = DevblocksPlatform::importGPC($_REQUEST['point'],'integer',0);
		@$address_ids_str = DevblocksPlatform::importGPC($_REQUEST['address_ids'],'string','');
		@$org_id = DevblocksPlatform::importGPC($_REQUEST['org_id'],'integer',0);
	
		$view_id = DevblocksPlatform::strAlphaNum($point, '\_');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$view = C4_AbstractViewLoader::getView($view_id);
		$ids = array();
		
		// Determine the address scope
		
		if(empty($ids) && !empty($address_ids_str)) {
			$ids = DevblocksPlatform::parseCsvString($address_ids_str, false, 'integer');
		}
		
		// Build the view
		
		if(null == $view) {
			$view = new View_Ticket();
			$view->id = $view_id;
			$view->view_columns = array(
				SearchFields_Ticket::TICKET_LAST_ACTION_CODE,
				SearchFields_Ticket::TICKET_CREATED_DATE,
				SearchFields_Ticket::TICKET_GROUP_ID,
				SearchFields_Ticket::TICKET_BUCKET_ID,
			);
			$view->renderLimit = 10;
			$view->renderPage = 0;
			$view->renderSortBy = SearchFields_Ticket::TICKET_CREATED_DATE;
			$view->renderSortAsc = false;
		}
	

		$params_required = array(
			SearchFields_Ticket::TICKET_DELETED => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_DELETED,DevblocksSearchCriteria::OPER_EQ,0)
		);
		
		if(empty($ids)) {
			@$view->name = $translate->_('common.participants') . ": " . $translate->_('common.organization');
			$params_required[SearchFields_Ticket::VIRTUAL_ORG_ID] = new DevblocksSearchCriteria(SearchFields_Ticket::VIRTUAL_ORG_ID,'=',$org_id);
			
		} else {
			@$view->name = $translate->_('common.participants') . ": " . intval(count($ids)) . ' contact(s)';
			$params_required[SearchFields_Ticket::VIRTUAL_PARTICIPANT_ID] = new DevblocksSearchCriteria(SearchFields_Ticket::VIRTUAL_PARTICIPANT_ID,'in', $ids);
		}
		
		$view->addParamsRequired($params_required, true);
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::internal/views/search_and_view.tpl');
		exit;
	}

	function findTicketsAction() {
		@$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string','');
		@$closed = DevblocksPlatform::importGPC($_REQUEST['closed'],'string','');
		
		if(null == ($address = DAO_Address::lookupAddress($email, false)))
			return;
		
		if(null == ($ticket_context = Extension_DevblocksContext::get(CerberusContexts::CONTEXT_TICKET)))
			return;
		
		if(null == ($search_view = $ticket_context->getSearchView()))
			return;
		
		$search_view->removeAllParams();
		
		if(!empty($address))
			$search_view->addParam(new DevblocksSearchCriteria(SearchFields_Ticket::REQUESTER_ADDRESS,'=',$address->email));

		if(0 != strlen($closed))
			$search_view->addParam(new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',$closed));
			
		$search_view->renderPage = 0;
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('search','ticket')));
	}
	
	function showAddressBatchPanelAction() {
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);

		if(!empty($ids)) {
			$tpl->assign('ids', $ids);
		}
		
		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_ADDRESS, false);
		$tpl->assign('custom_fields', $custom_fields);
		
		// Groups
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		// Broadcast
		CerberusContexts::getContext(CerberusContexts::CONTEXT_ADDRESS, null, $token_labels, $token_values);
		$tpl->assign('token_labels', $token_labels);
		
		// HTML templates
		$html_templates = DAO_MailHtmlTemplate::getAll();
		$tpl->assign('html_templates', $html_templates);
		
		// Macros
		
		$macros = DAO_TriggerEvent::getReadableByActor(
			$active_worker,
			'event.macro.address'
		);
		$tpl->assign('macros', $macros);
		
		$tpl->display('devblocks:cerberusweb.core::contacts/addresses/bulk.tpl');
	}
	
	function showOrgBulkPanelAction() {
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);

		if(!empty($ids)) {
			$org_ids = DevblocksPlatform::parseCsvString($ids);
			$tpl->assign('org_ids', implode(',', $org_ids));
		}
		
		// Custom Fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_ORG, false);
		$tpl->assign('custom_fields', $custom_fields);
		
		// Macros
		
		$macros = DAO_TriggerEvent::getReadableByActor(
			$active_worker,
			'event.macro.org'
		);
		$tpl->assign('macros', $macros);
		
		$tpl->display('devblocks:cerberusweb.core::contacts/orgs/bulk.tpl');
	}
	
	function showOrgMergePeekAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$org_ids = DevblocksPlatform::importGPC($_REQUEST['org_ids'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);

		if(!empty($org_ids)) {
			$org_ids = DevblocksPlatform::sanitizeArray(DevblocksPlatform::parseCsvString($org_ids),'integer',array('nonzero','unique'));
			
			if(!empty($org_ids)) {
				$orgs = DAO_ContactOrg::getWhere(sprintf("%s IN (%s)",
					DAO_ContactOrg::ID,
					implode(',', $org_ids)
				));
				
				$tpl->assign('orgs', $orgs);
			}
		}
		
		$tpl->display('devblocks:cerberusweb.core::contacts/orgs/org_merge_peek.tpl');
	}
	
	function showOrgMergeContinuePeekAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$org_ids = DevblocksPlatform::importGPC($_REQUEST['org_id'],'array',array());
		
		$tpl = DevblocksPlatform::getTemplateService();
		$translate = DevblocksPlatform::getTranslationService();
		
		$workers = DAO_Worker::getAll();
		
		$tpl->assign('view_id', $view_id);
		$tpl->assign('org_ids', $org_ids);

		// Sanitize
		if(is_array($org_ids) && !empty($org_ids))
		foreach($org_ids as $idx => $org_id) {
			$org_ids[$idx] = intval($org_id);
			if(empty($org_ids[$idx]))
				unset($org_ids[$idx]);
		}
		
		// Give the implode something to do
		if(empty($org_ids))
			$org_ids = array(-1);

		// Retrieve orgs
		$orgs = DAO_ContactOrg::getWhere(sprintf("%s IN (%s)",
			DAO_ContactOrg::ID,
			implode(',', $org_ids)
		));
		$tpl->assign('orgs', $orgs);
		
		// Index unique values
		$properties = array(
			'name' => 'common.name',
			'street' => 'contact_org.street',
			'city' => 'contact_org.city',
			'province' => 'contact_org.province',
			'postal' => 'contact_org.postal',
			'country' => 'contact_org.country',
			'phone' => 'common.phone',
			'website' => 'common.website',
		);
		
		// Custom fields
		
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_ORG);
		$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_ORG, $org_ids);
		$tpl->assign('custom_fields', $custom_fields);
		
		foreach($custom_fields as $cfield_id => $cfield) { /* @var $cfield Model_CustomField */
			$properties['cf_' . $cfield_id] = $cfield->name;
		}

		// Combinations
		
		$combinations = array();
		foreach($properties as $prop => $label) {
			$combinations[$prop] = array(
				'label' => $label,
				'values' => array(),
			);
			$vals = array();
			$is_multival = false;
			
			if('cf_' == substr($prop,0,3)) { // custom field
				$cfield_id = substr($prop, 3);
				if(!isset($custom_fields[$cfield_id]))
					continue;
				$cfield_type = $custom_fields[$cfield_id]->type;
				
				foreach($custom_field_values as $org_id => $cfield_keyvalues) {
					if(!is_array($cfield_keyvalues))
						continue;
						
					foreach($cfield_keyvalues as $cfield_key => $cfield_value) {
						$val = null;
						
						// We're only interested in a particular field value
						if($cfield_key != $cfield_id)
							continue;
	
						if(empty($cfield_value))
							continue;
							
						if(is_array($cfield_value)) { // multi-value
							$val = $cfield_value;
							
						} else { // single-value
							if('W' == $cfield_type) { // use worker name
								if(isset($workers[$cfield_value]))
									$val = $workers[$cfield_value]->getName();
							} elseif('C' == $cfield_type) { // checkbox
								$val = !empty($cfield_value) ? 'Yes' : 'No';
							} else {
								$val = strtr($cfield_value,"\r\n",' '); // no newlines
							}
							
						}
						
						if(!empty($val))
							$vals[$org_id] = $val;
					}
				}
				
				switch($cfield_type) {
					case 'X':
						// Combine all the values into one array
						$newvals = array();
						foreach($vals as $val) {
							if(is_array($val)) {
								foreach($val as $v)
									$newvals[] = $v;
							} else {
								$newvals[] = $val;
							}
						}
						
						// Only keep unique vlaues
						if(!empty($newvals))
							$vals = array(implode(', ', array_unique($newvals)));
						else
							$vals = array();
							
						break;
				}
				
			} else { // record
				foreach($orgs as $org_id => $org) {
					$val = strtr($org->$prop,"\r\n",' '); // no newlines
					if(empty($val))
						continue;
					$vals[$org_id] = $val;
				}
				
			}
			
			if(!empty($vals))
				$combinations[$prop]['values'] = array_unique($vals);
		}
		$tpl->assign('combinations', $combinations);
		
		$tpl->display('devblocks:cerberusweb.core::contacts/orgs/org_merge_continue_peek.tpl');
	}
	
	function saveOrgMergePeekAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$org_ids = DevblocksPlatform::importGPC($_REQUEST['org_id'],'array',array());
		@$properties = DevblocksPlatform::importGPC($_REQUEST['prop'],'array',array());
		
		$db = DevblocksPlatform::getDatabaseService();
		$eventMgr = DevblocksPlatform::getEventService();
		$date = DevblocksPlatform::getDateService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker->hasPriv('core.addybook.org.actions.merge'))
			return false;
		
		// Sanitize
		if(is_array($org_ids) && !empty($org_ids))
		foreach($org_ids as $idx => $org_id) {
			$org_ids[$idx] = intval($org_id);
			if(empty($org_ids[$idx]))
				unset($org_ids[$idx]);
		}

		// We need at least two records to merge
		if(count($org_ids) < 2)
			return;
		
		// Retrieve orgs
		$orgs = DAO_ContactOrg::getWhere(
			sprintf("%s IN (%s)",
				DAO_ContactOrg::ID,
				(empty($org_ids) ? array(-1) : implode(',', $org_ids)) // handle empty
			),
			DAO_ContactOrg::ID,
			true
		);

		// Create a new destination org
		$oldest_org_id = key($orgs); /* @var $oldest_org Model_ContactOrg */
		$merge_to_id = DAO_ContactOrg::create(array(
			DAO_ContactOrg::NAME => $orgs[$oldest_org_id]->name,
			DAO_ContactOrg::CREATED => $orgs[$oldest_org_id]->created,
		));
		
		// Merge records
		
		$fields = array();
		$custom_fields = array();
		$custom_field_types = DAO_CustomField::getAll();
		$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_ORG, array_keys($orgs));
		
		foreach($properties as $k => $v) {
			$is_cfield = ('cf_' == substr($k,0,3));
			
			// If an empty property field, skip
			if(!$is_cfield && (empty($v) || !isset($orgs[$v])))
				continue;
				
			switch($k) {
				case 'name':
					$fields[DAO_ContactOrg::NAME] = $orgs[$v]->name;
					break;
				case 'street':
					$fields[DAO_ContactOrg::STREET] = $orgs[$v]->street;
					break;
				case 'city':
					$fields[DAO_ContactOrg::CITY] = $orgs[$v]->city;
					break;
				case 'province':
					$fields[DAO_ContactOrg::PROVINCE] = $orgs[$v]->province;
					break;
				case 'postal':
					$fields[DAO_ContactOrg::POSTAL] = $orgs[$v]->postal;
					break;
				case 'country':
					$fields[DAO_ContactOrg::COUNTRY] = $orgs[$v]->country;
					break;
				case 'phone':
					$fields[DAO_ContactOrg::PHONE] = $orgs[$v]->phone;
					break;
				case 'website':
					$fields[DAO_ContactOrg::WEBSITE] = $orgs[$v]->website;
					break;
				// Custom fields
				default:
					if(!$is_cfield)
						break;
						
					$cfield_id = intval(substr($k,3));
					
					if(!isset($custom_field_types[$cfield_id]))
						break;
					
					$is_cfield_multival = $custom_field_types[$cfield_id]->type == Model_CustomField::TYPE_MULTI_CHECKBOX;
					
					if(empty($v)) { // no org_id
						// Handle aggregation of multi-value fields when blank
						if($is_cfield_multival) {
							foreach($orgs as $org_id => $org) {
								if(isset($custom_field_values[$org_id][$cfield_id])) {
									$existing_field_values = isset($custom_fields[$cfield_id]) ? $custom_fields[$cfield_id] : array();
									$new_field_values = is_array($custom_field_values[$org_id][$cfield_id]) ? $custom_field_values[$org_id][$cfield_id] : array();
									
									$custom_fields[$cfield_id] = array_merge(
										$existing_field_values,
										$new_field_values
									);
								}
							}
						}
						
					} else { // org_id exists
						if(isset($orgs[$v]) && isset($custom_field_values[$v][$cfield_id])) {
							$val = $custom_field_values[$v][$cfield_id];
							
							// Overrides
							switch($custom_field_types[$cfield_id]->type) {
								case Model_CustomField::TYPE_CHECKBOX:
									$val = !empty($val) ? 1 : 0;
									break;
								case Model_CustomField::TYPE_DATE:
									$val = $date->formatTime(null, $val, false);
									break;
							}
							$custom_fields[$cfield_id] = $val;
						}
					}
					
					break;
			}
		}
		
		if(!empty($fields))
			DAO_ContactOrg::update($merge_to_id, $fields);
		
		if(!empty($custom_fields))
			DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_ORG, $merge_to_id, $custom_fields, true, true, false);

		// Merge connections
		DAO_ContactOrg::mergeIds($org_ids, $merge_to_id);
		
		foreach($org_ids as $from_org_id) {
			if($from_org_id == $properties['name'])
				continue;
			
			/*
			 * Log activity (org.merge)
			 */
			$entry = array(
				//{{actor}} merged organization {{source}} with organization {{target}}
				'message' => 'activities.org.merge',
				'variables' => array(
					'source' => sprintf("%s", $orgs[$from_org_id]->name),
					'target' => sprintf("%s", $fields[DAO_ContactOrg::NAME]),
					),
				'urls' => array(
					//'source' => sprintf("ctx://%s:%d/%s", CerberusContexts::CONTEXT_ORG, $from_org_id, DevblocksPlatform::strToPermalink($orgs[$from_org_id]->name)),
					'target' => sprintf("ctx://%s:%d/%s", CerberusContexts::CONTEXT_ORG, $merge_to_id, DevblocksPlatform::strToPermalink($fields[DAO_ContactOrg::NAME])),
					)
			);
			CerberusContexts::logActivity('org.merge', CerberusContexts::CONTEXT_ORG, $merge_to_id, $entry);
		}
		
		// Fire a merge event for plugins
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'org.merge',
				array(
					'merge_to_id' => $merge_to_id,
					'merge_from_ids' => $org_ids,
				)
			)
		);
		
		// Nuke the source orgs
		DAO_ContactOrg::delete($org_ids);
		
		// Index immediately
		$search = Extension_DevblocksSearchSchema::get(Search_Org::ID);
		$search->indexIds(array($merge_to_id));
		
		if(!empty($view_id)) {
			if(null != ($view = C4_AbstractViewLoader::getView($view_id)))
				$view->render();
		}
		
		exit;
	}
	
	function saveOrgPeekAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);
		
		$active_worker = CerberusApplication::getActiveWorker();

		header('Content-Type: application/json; charset=' . LANG_CHARSET_CODE);
		
		try {
		
			if(!empty($id) && !empty($delete)) { // delete
				if(!$active_worker->hasPriv('core.addybook.org.actions.delete'))
					throw new Exception_DevblocksAjaxValidationError("You don't have permission to delete this record.");
				
				DAO_ContactOrg::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else { // create/edit
				@$org_name = DevblocksPlatform::importGPC($_REQUEST['org_name'],'string','');
				@$street = DevblocksPlatform::importGPC($_REQUEST['street'],'string','');
				@$city = DevblocksPlatform::importGPC($_REQUEST['city'],'string','');
				@$province = DevblocksPlatform::importGPC($_REQUEST['province'],'string','');
				@$postal = DevblocksPlatform::importGPC($_REQUEST['postal'],'string','');
				@$country = DevblocksPlatform::importGPC($_REQUEST['country'],'string','');
				@$phone = DevblocksPlatform::importGPC($_REQUEST['phone'],'string','');
				@$website = DevblocksPlatform::importGPC($_REQUEST['website'],'string','');
				@$comment = DevblocksPlatform::importGPC($_REQUEST['comment'],'string','');
				
				// Validation
				if(empty($org_name))
					throw new Exception_DevblocksAjaxValidationError("The 'Name' field is required.", 'org_name');
				
				// Privs
				if($active_worker->hasPriv('core.addybook.org.actions.update')) {
					$fields = array(
						DAO_ContactOrg::NAME => $org_name,
						DAO_ContactOrg::STREET => $street,
						DAO_ContactOrg::CITY => $city,
						DAO_ContactOrg::PROVINCE => $province,
						DAO_ContactOrg::POSTAL => $postal,
						DAO_ContactOrg::COUNTRY => $country,
						DAO_ContactOrg::PHONE => $phone,
						DAO_ContactOrg::WEBSITE => $website,
					);
			
					if($id==0) {
						if(false == ($id = DAO_ContactOrg::create($fields)))
							throw new Exception_DevblocksAjaxValidationError("Failed to create a new record.");
						
						// Watchers
						@$add_watcher_ids = DevblocksPlatform::sanitizeArray(DevblocksPlatform::importGPC($_REQUEST['add_watcher_ids'],'array',array()),'integer',array('unique','nonzero'));
						if(!empty($add_watcher_ids))
							CerberusContexts::addWatchers(CerberusContexts::CONTEXT_ORG, $id, $add_watcher_ids);
						
						// Context Link (if given)
						@$link_context = DevblocksPlatform::importGPC($_REQUEST['link_context'],'string','');
						@$link_context_id = DevblocksPlatform::importGPC($_REQUEST['link_context_id'],'integer','');
						if(!empty($id) && !empty($link_context) && !empty($link_context_id)) {
							DAO_ContextLink::setLink(CerberusContexts::CONTEXT_ORG, $id, $link_context, $link_context_id);
						}
						
						// View marquee
						if(!empty($id) && !empty($view_id)) {
							C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_ORG, $id);
						}
						
					}
					else {
						DAO_ContactOrg::update($id, $fields);
					}
					
					if($id) {
						// Custom field saves
						@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', array());
						DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_ORG, $id, $field_ids);
						
						// Avatar image
						@$avatar_image = DevblocksPlatform::importGPC($_REQUEST['avatar_image'], 'string', '');
						DAO_ContextAvatar::upsertWithImage(CerberusContexts::CONTEXT_ORG, $id, $avatar_image);
						
						// Comments
						if(!empty($comment)) {
							$also_notify_worker_ids = array_keys(CerberusApplication::getWorkersByAtMentionsText($comment));
							
							$fields = array(
								DAO_Comment::CREATED => time(),
								DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_ORG,
								DAO_Comment::CONTEXT_ID => $id,
								DAO_Comment::COMMENT => $comment,
								DAO_Comment::OWNER_CONTEXT => CerberusContexts::CONTEXT_WORKER,
								DAO_Comment::OWNER_CONTEXT_ID => $active_worker->id,
							);
							$comment_id = DAO_Comment::create($fields, $also_notify_worker_ids);
						}
						
						// Index immediately
						$search = Extension_DevblocksSearchSchema::get(Search_Org::ID);
						$search->indexIds(array($id));
					}
				}
			}
			
			echo json_encode(array(
				'status' => true,
				'id' => $id,
				'view_id' => $view_id,
			));
			return;
			
		} catch (Exception_DevblocksAjaxValidationError $e) {
				echo json_encode(array(
					'status' => false,
					'id' => $id,
					'error' => $e->getMessage(),
					'field' => $e->getFieldName(),
				));
				return;
			
		} catch (Exception $e) {
				echo json_encode(array(
					'status' => false,
					'id' => $id,
					'error' => 'An error occurred.',
				));
				return;
			
		}
	}
	
	function doAddressBatchUpdateAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
		$ids = array();
		
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);

		@$org_name = trim(DevblocksPlatform::importGPC($_POST['contact_org'],'string',''));
		@$sla = DevblocksPlatform::importGPC($_POST['sla'],'string','');
		@$is_banned = DevblocksPlatform::importGPC($_POST['is_banned'],'integer',0);
		@$is_defunct = DevblocksPlatform::importGPC($_POST['is_defunct'],'integer',0);

		// Scheduled behavior
		@$behavior_id = DevblocksPlatform::importGPC($_POST['behavior_id'],'string','');
		@$behavior_when = DevblocksPlatform::importGPC($_POST['behavior_when'],'string','');
		@$behavior_params = DevblocksPlatform::importGPC($_POST['behavior_params'],'array',array());
		
		$do = array();
		
		// Do: Organization
		if(!empty($org_name)) {
			if(null != ($org_id = DAO_ContactOrg::lookup($org_name, true)))
				$do['org_id'] = $org_id;
		}
		
		// Do: SLA
		if('' != $sla)
			$do['sla'] = $sla;
		
		// Do: Banned
		if(0 != strlen($is_banned))
			$do['banned'] = $is_banned;
		
		// Do: Defunct
		if(0 != strlen($is_defunct))
			$do['defunct'] = $is_defunct;
		
		// Do: Scheduled Behavior
		if(0 != strlen($behavior_id)) {
			$do['behavior'] = array(
				'id' => $behavior_id,
				'when' => $behavior_when,
				'params' => $behavior_params,
			);
		}
		
		// Broadcast: Compose
		if($active_worker->hasPriv('core.addybook.addy.view.actions.broadcast')) {
			@$do_broadcast = DevblocksPlatform::importGPC($_REQUEST['do_broadcast'],'string',null);
			@$broadcast_group_id = DevblocksPlatform::importGPC($_REQUEST['broadcast_group_id'],'integer',0);
			@$broadcast_subject = DevblocksPlatform::importGPC($_REQUEST['broadcast_subject'],'string',null);
			@$broadcast_message = DevblocksPlatform::importGPC($_REQUEST['broadcast_message'],'string',null);
			@$broadcast_format = DevblocksPlatform::importGPC($_REQUEST['broadcast_format'],'string',null);
			@$broadcast_html_template_id = DevblocksPlatform::importGPC($_REQUEST['broadcast_html_template_id'],'integer',0);
			@$broadcast_is_queued = DevblocksPlatform::importGPC($_REQUEST['broadcast_is_queued'],'integer',0);
			@$broadcast_is_closed = DevblocksPlatform::importGPC($_REQUEST['broadcast_next_is_closed'],'integer',0);
			@$broadcast_file_ids = DevblocksPlatform::sanitizeArray(DevblocksPlatform::importGPC($_REQUEST['broadcast_file_ids'],'array',array()), 'integer', array('nonzero','unique'));
			
			if(0 != strlen($do_broadcast) && !empty($broadcast_subject) && !empty($broadcast_message)) {
				$do['broadcast'] = array(
					'subject' => $broadcast_subject,
					'message' => $broadcast_message,
					'format' => $broadcast_format,
					'html_template_id' => $broadcast_html_template_id,
					'is_queued' => $broadcast_is_queued,
					'next_is_closed' => $broadcast_is_closed,
					'group_id' => $broadcast_group_id,
					'worker_id' => $active_worker->id,
					'file_ids' => $broadcast_file_ids,
				);
			}
		}
			
		// Do: Custom fields
		$do = DAO_CustomFieldValue::handleBulkPost($do);
		
		switch($filter) {
			// Checked rows
			case 'checks':
				@$address_id_str = DevblocksPlatform::importGPC($_REQUEST['ids'],'string');
				$ids = DevblocksPlatform::parseCsvString($address_id_str);
				break;
			case 'sample':
				@$sample_size = min(DevblocksPlatform::importGPC($_REQUEST['filter_sample_size'],'integer',0),9999);
				$filter = 'checks';
				$ids = $view->getDataSample($sample_size);
				break;
			default:
				break;
		}
		
		$view->doBulkUpdate($filter, $do, $ids);
		$view->render();
		return;
	}

	function doAddressBulkUpdateBroadcastTestAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);

		$tpl = DevblocksPlatform::getTemplateService();
		
		if($active_worker->hasPriv('core.addybook.addy.view.actions.broadcast')) {
			@$broadcast_subject = DevblocksPlatform::importGPC($_REQUEST['broadcast_subject'],'string',null);
			@$broadcast_message = DevblocksPlatform::importGPC($_REQUEST['broadcast_message'],'string',null);
			@$broadcast_format = DevblocksPlatform::importGPC($_REQUEST['broadcast_format'],'string',null);
			@$broadcast_html_template_id = DevblocksPlatform::importGPC($_REQUEST['broadcast_html_template_id'],'integer',0);
			@$broadcast_group_id = DevblocksPlatform::importGPC($_REQUEST['broadcast_group_id'],'integer',0);
			
			@$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
			@$ids = DevblocksPlatform::importGPC($_REQUEST['address_ids'],'string','');
			
			// Filter to checked
			if('checks' == $filter && !empty($ids)) {
				$view->addParam(new DevblocksSearchCriteria(SearchFields_Address::ID,'in',explode(',', $ids)));
			}
			
			$results = $view->getDataSample(1);
			
			if(empty($results)) {
				$success = false;
				$output = "There aren't any rows in this view!";
				
			} else {
				@$addy = DAO_Address::get(current($results));
				
				// Try to build the template
				CerberusContexts::getContext(CerberusContexts::CONTEXT_ADDRESS, $addy, $token_labels, $token_values);

				if(empty($broadcast_subject)) {
					$success = false;
					$output = "Subject is blank.";
				
				} else {
					$template = "Subject: $broadcast_subject\n\n$broadcast_message";
					
					if(false === ($out = $tpl_builder->build($template, $token_values))) {
						// If we failed, show the compile errors
						$errors = $tpl_builder->getErrors();
						$success= false;
						$output = @array_shift($errors);
						
					} else {
						// If successful, return the parsed template
						$success = true;
						$output = $out;
						
						switch($broadcast_format) {
							case 'parsedown':
								// Markdown
								$output = DevblocksPlatform::parseMarkdown($output);
								
								// HTML Template
								
								$html_template = null;
								
								if($broadcast_html_template_id)
									$html_template = DAO_MailHtmlTemplate::get($broadcast_html_template_id);
								
								if(!$html_template && false != ($group = DAO_Group::get($broadcast_group_id)))
									$html_template = $group->getReplyHtmlTemplate(0);
								
								if(!$html_template && false != ($replyto = DAO_AddressOutgoing::getDefault()))
									$html_template = $replyto->getReplyHtmlTemplate();
								
								if($html_template)
									$output = $tpl_builder->build($html_template->content, array('message_body' => $output));
								
								// HTML Purify
								$output = DevblocksPlatform::purifyHTML($output, true);
								break;
								
							default:
								$output = nl2br(DevblocksPlatform::strEscapeHtml($output));
								break;
						}
					}
				}
			}
			
			if($success) {
				header("Content-Type: text/html; charset=" . LANG_CHARSET_CODE);
				echo sprintf('<html><head><meta http-equiv="content-type" content="text/html; charset=%s"></head><body>',
					LANG_CHARSET_CODE
				);
				echo $output;
				echo '</body></html>';
				
			} else {
				echo $output;
			}
		}
	}
	
	function doOrgBulkUpdateAction() {
		// Filter: whole list or check
		@$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
		$ids = array();
		
		// View
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);
		
		// Org fields
		@$country = trim(DevblocksPlatform::importGPC($_POST['country'],'string',''));

		// Scheduled behavior
		@$behavior_id = DevblocksPlatform::importGPC($_POST['behavior_id'],'string','');
		@$behavior_when = DevblocksPlatform::importGPC($_POST['behavior_when'],'string','');
		@$behavior_params = DevblocksPlatform::importGPC($_POST['behavior_params'],'array',array());
		
		$do = array();
		
		// Do: Country
		if(0 != strlen($country))
			$do['country'] = $country;
			
		// Do: Scheduled Behavior
		if(0 != strlen($behavior_id)) {
			$do['behavior'] = array(
				'id' => $behavior_id,
				'when' => $behavior_when,
				'params' => $behavior_params,
			);
		}
		
		// Watchers
		$watcher_params = array();
		
		@$watcher_add_ids = DevblocksPlatform::importGPC($_REQUEST['do_watcher_add_ids'],'array',array());
		if(!empty($watcher_add_ids))
			$watcher_params['add'] = $watcher_add_ids;
			
		@$watcher_remove_ids = DevblocksPlatform::importGPC($_REQUEST['do_watcher_remove_ids'],'array',array());
		if(!empty($watcher_remove_ids))
			$watcher_params['remove'] = $watcher_remove_ids;
		
		if(!empty($watcher_params))
			$do['watchers'] = $watcher_params;
			
		// Do: Custom fields
		$do = DAO_CustomFieldValue::handleBulkPost($do);
		
		switch($filter) {
			// Checked rows
			case 'checks':
				@$org_ids_str = DevblocksPlatform::importGPC($_REQUEST['org_ids'],'string');
				$ids = DevblocksPlatform::parseCsvString($org_ids_str);
				break;
			case 'sample':
				@$sample_size = min(DevblocksPlatform::importGPC($_REQUEST['filter_sample_size'],'integer',0),9999);
				$filter = 'checks';
				$ids = $view->getDataSample($sample_size);
				break;
			default:
				break;
		}
		
		$view->doBulkUpdate($filter, $do, $ids);
		$view->render();
		return;
	}
	
	function getOrgsAutoCompletionsAction() {
		@$starts_with = DevblocksPlatform::importGPC($_REQUEST['term'],'string','');
		@$callback = DevblocksPlatform::importGPC($_REQUEST['callback'],'string','');
		
		list($orgs,$null) = DAO_ContactOrg::search(
			array(),
			array(
				new DevblocksSearchCriteria(SearchFields_ContactOrg::NAME,DevblocksSearchCriteria::OPER_LIKE, $starts_with. '*'),
			),
			25,
			0,
			SearchFields_ContactOrg::NAME,
			true,
			false
		);
		
		$list = array();
		
		foreach($orgs AS $val){
			$list[] = $val[SearchFields_ContactOrg::NAME];
		}
		
		echo sprintf("%s%s%s",
			!empty($callback) ? ($callback.'(') : '',
			json_encode($list),
			!empty($callback) ? (')') : ''
		);
		exit;
	}
	
	function getCountryAutoCompletionsAction() {
		@$starts_with = DevblocksPlatform::importGPC($_REQUEST['term'],'string','');
		@$callback = DevblocksPlatform::importGPC($_REQUEST['callback'],'string','');
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT DISTINCT country AS country ".
			"FROM contact_org ".
			"WHERE country != '' ".
			"AND country LIKE %s ".
			"ORDER BY country ASC ".
			"LIMIT 0,25",
			$db->qstr($starts_with.'%')
		);
		$rs = $db->ExecuteSlave($sql);
		
		$list = array();
		
		while($row = mysqli_fetch_assoc($rs)) {
			$list[] = $row['country'];
		}
		
		mysqli_free_result($rs);
		
		echo sprintf("%s%s%s",
			!empty($callback) ? ($callback.'(') : '',
			json_encode($list),
			!empty($callback) ? (')') : ''
		);
		exit;
	}
	
};
