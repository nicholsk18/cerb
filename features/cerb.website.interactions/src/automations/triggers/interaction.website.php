<?php
class AutomationTrigger_InteractionWebsite extends Extension_AutomationTrigger {
	const ID = 'cerb.trigger.interaction.website';
	
	public static function getFormComponentMeta(): array {
		return [
			'end' => 'Cerb\Automation\Builder\Trigger\InteractionWebsite\Awaits\EndAwait',
			'fileUpload' => 'Cerb\Automation\Builder\Trigger\InteractionWebsite\Awaits\FileUploadAwait',
			'say' => 'Cerb\Automation\Builder\Trigger\InteractionWebsite\Awaits\SayAwait',
			'sheet' => 'Cerb\Automation\Builder\Trigger\InteractionWebsite\Awaits\SheetAwait',
			'submit' => 'Cerb\Automation\Builder\Trigger\InteractionWebsite\Awaits\SubmitAwait',
			'text' => 'Cerb\Automation\Builder\Trigger\InteractionWebsite\Awaits\TextAwait',
			'textarea' => 'Cerb\Automation\Builder\Trigger\InteractionWebsite\Awaits\TextareaAwait',
		];
	}
	
	function renderConfig(Model_Automation $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('inputs', $this->getInputsMeta());
		$tpl->assign('outputs', $this->getOutputsMeta());
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/config_inputs_outputs.tpl');
	}
	
	function validateConfig(array &$params, &$error=null) : bool {
		return true;
	}
	
	function getInputsMeta() : array {
		return [
			[
				'key' => 'interaction',
				'notes' => 'The name of the interaction.',
			],
			[
				'key' => 'interaction_params',
				'notes' => 'Arbitrary interaction parameters.',
			],
			[
				'key' => 'inputs',
				'notes' => 'Custom inputs from the caller.',
			],
			[
				'key' => 'portal_*',
				'notes' => 'The portal record.',
			],
			[
				'key' => 'client_ip',
				'notes' => 'The client IP address.',
			],
			[
				'key' => 'client_browser_name',
				'notes' => 'The client browser name (e.g. Safari).',
			],
			[
				'key' => 'client_browser_platform',
				'notes' => 'The client browser platform (e.g. Macintosh).',
			],
			[
				'key' => 'client_browser_version',
				'notes' => 'The client browser version.',
			],
		];
	}
	
	function getOutputsMeta() : array {
		return [
			'return' => [
				[
					'key' => 'redirect_url',
					'notes' => 'Redirect the visitor to a URL'
				]
			],
		];
	}
	
	function getUsageMeta(string $automation_name): array {
		$db = DevblocksPlatform::services()->database();
		
		$results = [];
		
		// Automations
		if(($linked_automations = DAO_Automation::getWhere(sprintf("%s LIKE %s",
			Cerb_ORMHelper::escape(DAO_Automation::SCRIPT),
			Cerb_ORMHelper::qstr('%' . $automation_name . '%')
		)))) {
			$linked_automations = array_filter($linked_automations, function($w) use ($automation_name) {
				$tokens = DevblocksPlatform::services()->string()->tokenize($w->script, false);
				return in_array($automation_name, $tokens);
			});
			
			if($linked_automations)
				$results['automation'] = array_column($linked_automations, 'id');
		}
		
		// Portals
		if(($linked_portals = $db->GetArrayReader(sprintf("select id, property_value from community_tool inner join community_tool_property on (community_tool_property.tool_code=community_tool.code) where extension_id in ('cerb.website.interactions') and property_key in ('automations_kata') and property_value like %s",
			Cerb_ORMHelper::qstr('%' . $automation_name . '%')
		)))) {
			$linked_portals = array_filter($linked_portals, function($w) use ($automation_name) {
				$tokens = DevblocksPlatform::services()->string()->tokenize($w['property_value'], false);
				return in_array($automation_name, $tokens);
			});
			
			if($linked_portals)
				$results['portal'] = array_column($linked_portals, 'id');
		}
		
		return $results;
	}
	
	public function getEditorToolbarItems(array $toolbar): array {
		return $toolbar;
	}
	
	public function getAutocompleteSuggestions() : array {
		return [
			'*' => [
				'(.*):await:' => [
					[
						'caption' => 'form:',
						'snippet' => "form:\n\ttitle: \${1:Form Title}\n\telements:\n\t\t",
						'score' => 2000,
						'description' => "Display a form and wait for valid user input",
					],
					[
						'caption' => 'interaction:',
						'snippet' => "interaction:\n\t",
						'score' => 1999,
						'description' => "Run an interaction and wait for completion",
					],
				],
				
				'(.*):await:form:' => [
					[
						'caption' => 'title:',
						'snippet' => "title:",
						'score' => 2000,
					],
					[
						'caption' => 'elements:',
						'snippet' => "elements:",
						'score' => 1999,
					],
				],
				
				'(.*):await:form:elements:' => [
					[
						'caption' => 'fileUpload:',
						'snippet' => "fileUpload/\${1:prompt_file}:\n\t\${2:}",
						'description' => "Prompt for one or more file uploads",
					],
					[
						'caption' => 'say:',
						'snippet' => "say/\${1:prompt_say}:\n\t\${2:}",
						'description' => "Display arbitrary plaintext or Markdown",
					],
					[
						'caption' => 'sheet:',
						'snippet' => "sheet/\${1:prompt_sheet}:\n\t\${2:}",
						'description' => "Prompt using a table with single/multiple selection, filtering, and paging",
					],
					[
						'caption' => 'submit:',
						'snippet' => "submit:\n\t\${1:}",
						'description' => "Prompt for one or more submit actions",
					],
					[
						'caption' => 'text:',
						'snippet' => "text/\${1:prompt_text}:\n\t\${2:}",
						'description' => "Prompt for a line of text",
					],
					[
						'caption' => 'textarea:',
						'snippet' => "textarea/\${1:prompt_textarea}:\n\t\${2:}",
						'description' => "Prompt for multiple lines of text",
					]
				],
				
				'(.*):await:form:elements:fileUpload:' => [
					[
						'caption' => 'label:',
						'snippet' => "label: \${1:Label:}",
						'score' => 2000,
					],
					'accept: .png,image/png,.jpg,image/jpeg',
					'multiple@bool: yes',
					'required@bool: yes',
					'validation@raw:',
					[
						'caption' => 'validation: (image/png)',
						'snippet' => "validation@raw:\n\t{% if prompt_file_mime_type != 'image/png' %}\n\tThe file must be a PNG image ({{prompt_file_mime_type}})\n\t{% elseif prompt_file_size > 1024000 %}\n\tThe file ({{prompt_file_size|bytes_pretty}}) must be smaller than 1MB.\n\t{% endif %}",
					]
				],
				
				'(.*):await:form:elements:say:' => [
					[
						'caption' => 'content:',
						'snippet' => "content@text:\n\t\${1:}",
						'score' => 2000,
						'description' => "Display Markdown formatted text",
					],
					[
						'caption' => 'message:',
						'snippet' => "message@text:\n\t\${1:}",
						'score' => 1999,
						'description' => "Display plaintext without formatting",
					],
					"references:",
				],
				'(.*):await:form:elements:say:references:' => [
					'resource:',
				],
				'(.*):await:form:elements:say:references:resource:uri:' => [
					'type' => 'cerb-uri',
					'params' => [
						'resource' => [
							'types' => [
								ResourceType_PortalImage::ID,
							]
						]
					]
				],				
				'(.*):await:form:elements:sheet:' => [
					[
						'caption' => 'label:',
						'snippet' => "label: \${1:Label:}",
						'score' => 2000,
					],
					[
						'caption' => 'data:',
						'snippet' => "data:\n\t\${1:}",
						'score' => 1999,
					],
					[
						'caption' => 'schema:',
						'snippet' => "schema:\n\tlayout:\n\t\t\${1:}\n\tcolumns:\n\t\t\${2:}",
						'score' => 1998,
					],
					'default:',
					'limit:',
					'page:',
					'required@bool: yes',
				],
				'(.*):await:form:elements:sheet:data:' => [
					[
						'caption' => 'automation:',
						'snippet' => "automation:\n  uri: cerb:automation:\${1:cerb.data.records}\n  inputs:\n    record_type: ticket\n    query_required: status:o\n",
					],
					[
						'caption' => '(manual)',
						'snippet' => "0:\n  key: key1\n  value: value1\n1:\n  key: key2\n  value: value2\n",
					]
				],
				'(.*):await:form:elements:sheet:data:automation:' => [
					[
						'caption' => 'uri:',
						'snippet' => "uri:",
						'score' => 2000,
					],
					[
						'caption' => 'inputs:',
						'snippet' => "inputs:\n\t\${1:}",
						'score' => 1999,
					],
				],
				'(.*):await:form:elements:sheet:data:automation:inputs:' => [
					'type' => 'automation-inputs',
				],
				'(.*):await:form:elements:sheet:data:automation:uri:' => [
					'type' => 'cerb-uri',
					'params' => [
						'automation' => [
							'triggers' => [
								'cerb.trigger.ui.sheet.data',
							]
						]
					]
				],
				'(.*):await:form:elements:sheet:schema:' => [
					'columns:',
					'layout:',
				],
				'(.*):await:form:elements:sheet:schema:columns:' => [
					'date/key:',
					'link/key:',
					'markdown/key:',
					'selection/key:',
					'slider/key:',
					'text/key:',
					'time_elapsed/key:',
				],
				'(.*):await:form:elements:sheet:schema:columns:date:' => [
					'label:',
					'params:',
				],
				'(.*):await:form:elements:sheet:schema:columns:date:params:' => [
					'format: d-M-Y H:i:s T',
					'value: 1577836800',
					'value_key: updated',
				],
				'(.*):await:form:elements:sheet:schema:columns:link:' => [
					'label:',
					'params:',
				],
				'(.*):await:form:elements:sheet:schema:columns:link:params:' => [
					'href: https://example.com/',
					'href_key: record_url',
					'href_template@raw:',
					'text: Link title',
					'text_key: _label',
					'text_template@raw:',
				],
				'(.*):await:form:elements:sheet:schema:columns:markdown:' => [
					'label:',
					'params:',
				],
				'(.*):await:form:elements:sheet:schema:columns:markdown:params:' => [
					'value: **Markdown**',
					'value_key: key',
					'value_template@raw: {{key}}',
				],
				'(.*):await:form:elements:sheet:schema:columns:selection:' => [
					'label:',
					'params:',
				],
				'(.*):await:form:elements:sheet:schema:columns:selection:params:' => [
					'mode: single',
					'mode: multiple',
					'label: Description',
					'label_key: description',
					'label_template@raw: {{description}}',
					'value: 123',
					'value_key: key',
					'value_template@raw: {{key}}',
				],
				'(.*):await:form:elements:sheet:schema:columns:slider:' => [
					'label:',
					'params:',
				],
				'(.*):await:form:elements:sheet:schema:columns:slider:params:' => [
					'min: 0',
					'max: 100',
					'value: 50',
					'value_key: importance',
					'value_template@raw: {{importance}}',
				],
				'(.*):await:form:elements:sheet:schema:columns:text:' => [
					'label:',
					'params:',
				],
				'(.*):await:form:elements:sheet:schema:columns:text:params:' => [
					'bold@bool: yes',
					'value: Text',
					'value_key: key',
					'value_template@raw: {{key}}',
					'value_map:',
					'icon:',
				],
				'(.*):await:form:elements:sheet:schema:columns:text:params:icon:' => [
					'image:',
				],
				'(.*):await:form:elements:sheet:schema:columns:time_elapsed:' => [
					'label:',
					'params:',
				],
				'(.*):await:form:elements:sheet:schema:columns:time_elapsed:params:' => [
					'precision: 2',
				],
				'(.*):await:form:elements:sheet:schema:layout:' => [
					'filtering@bool: yes',
					'headings@bool: yes',
					'paging@bool: yes',
					'style: table',
					'title_column:',
				],
				'(.*):await:form:elements:sheet:schema:layout:style:' => [
					'buttons',
					'scale',
					'table',
				],
				
				'(.*):await:form:elements:submit:' => [
					'continue@bool: no',
					'reset@bool: no',
				],
				
				'(.*):await:form:elements:text:' => [
					'default:',
					'label:',
					'max_length@int:',
					'min_length@int:',
					'placeholder:',
					'required@bool:',
					'truncate@bool: yes',
					'type:',
				],
				
				'(.*):await:form:elements:text:type:' => [
					'date',
					'decimal',
					'email',
					'freeform',
					'geopoint',
					'ip',
					'ipv4',
					'ipv6',
					'number',
					'uri',
					'url',
				],
				
				'(.*):await:form:elements:textarea:' => [
					'default:',
					'label:',
					'max_length@int:',
					'min_length@int:',
					'placeholder:',
					'required@bool:',
					'truncate@bool: yes',
				],
				
				'(.*):await:interaction:' => [
					[
						'caption' => 'output:',
						'snippet' => "output: \${1:results}",
					],
					'uri:',
				],
				'(.*):await:interaction:uri:' => [
					'type' => 'cerb-uri',
					'params' => [
						'automation' => [
							'triggers' => [
								'cerb.trigger.interaction.worker',
							]
						]
					]
				],
			],
		];
	}
}