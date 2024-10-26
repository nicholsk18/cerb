<?php
class AutomationTrigger_InteractionWorker extends Extension_AutomationTrigger {
	const ID = 'cerb.trigger.interaction.worker';
	
	public static function getFormComponentMeta() {
		return [
			'chart' => 'Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits\ChartAwait',
			'chooser' => 'Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits\ChooserAwait',
			'editor' => 'Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits\EditorAwait',
			'end' => 'Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits\EndAwait',
			'fileDownload' => 'Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits\FileDownloadAwait',
			'fileUpload' => 'Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits\FileUploadAwait',
			'map' => 'Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits\MapAwait',
			'query' => 'Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits\QueryAwait',
			'say' => 'Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits\SayAwait',
			'audio' => 'Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits\AudioAwait',
			'sheet' => 'Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits\SheetAwait',
			'submit' => 'Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits\SubmitAwait',
			'text' => 'Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits\TextAwait',
			'textarea' => 'Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits\TextareaAwait',
		];
	}
	
	function renderConfig(Model_Automation $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('inputs', $this->getInputsMeta());
		$tpl->assign('outputs', $this->getOutputsMeta());
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/config_inputs_outputs.tpl');
	}
	
	function validateConfig(array &$params, &$error=null) {
		return true;
	}
	
	function getInputsMeta() {
		return [
			[
				'key' => 'caller_name',
				'notes' => 'The caller which started the interaction.',
			],
			[
				'key' => 'caller_params',
				'notes' => 'Built-in parameters based on the caller type.',
			],
			[
				'key' => 'client_ip',
				'notes' => 'The client IP address.',
			],
			[
				'key' => 'client_url',
				'notes' => 'The client current URL.',
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
			[
				'key' => 'inputs',
				'notes' => 'Custom inputs from the caller.',
			],
			[
				'key' => 'worker_*',
				'notes' => 'The active [worker](https://cerb.ai/docs/records/types/worker/#dictionary-placeholders) record. Supports key expansion.',
			],
		];
	}
	
	function getOutputsMeta() : array {
		return [
			'return' => [
				[
					'key' => 'alert',
					'notes' => 'Display the given message at the top of the browser',
				],
				[
					'key' => 'callout',
					'notes' => 'Show a floating tooltip relative to a DOM element selector',
				],
				[
					'key' => 'clipboard',
					'notes' => 'Copy the given text to the browser clipboard',
				],
				[
					'key' => 'explore_page',
					'notes' => 'The next page in an explore interaction (if applicable)',
				],
				[
					'key' => 'open_link',
					'notes' => 'Open a new browser tab with the given URL',
				],
				[
					'key' => 'open_url',
					'notes' => 'Open the given URL in the current browser tab',
				],
				[
					'key' => 'snippet',
					'notes' => 'Insert the given text at the cursor in the current editor (if applicable)',
				],
				[
					'key' => 'timer',
					'notes' => 'Start time tracking with the given time entry record ID',
				]
			]
		];
	}
	
	function getUsageMeta(string $automation_name): array {
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
		
		// Toolbars
		if(($linked_toolbar_sections = DAO_ToolbarSection::getWhere(sprintf("%s LIKE %s",
			Cerb_ORMHelper::escape(DAO_ToolbarSection::TOOLBAR_KATA),
			Cerb_ORMHelper::qstr('%' . $automation_name . '%')
		)))) {
			$linked_toolbar_sections = array_filter($linked_toolbar_sections, function($w) use ($automation_name) {
				$tokens = DevblocksPlatform::services()->string()->tokenize($w->toolbar_kata, false);
				return in_array($automation_name, $tokens);
			});
			
			if($linked_toolbar_sections)
				$results['toolbar_section'] = array_column($linked_toolbar_sections, 'id');
		}
		
		// Card widgets
		if(($linked_card_widgets = DAO_CardWidget::getWhere(sprintf("%s IN (%s) AND %s LIKE %s",
			Cerb_ORMHelper::escape(DAO_CardWidget::EXTENSION_ID),
			implode(',', Cerb_ORMHelper::qstrArray([
				'cerb.card.widget.automation',
				'cerb.card.widget.chart.timeblocks',
				'cerb.card.widget.fields',
				'cerb.card.widget.form_interaction',
				'cerb.card.widget.sheet',
			])),
			Cerb_ORMHelper::escape(DAO_CardWidget::EXTENSION_PARAMS_JSON),
			Cerb_ORMHelper::qstr('%' . $automation_name . '%')
		)))) {
			$linked_card_widgets = array_filter($linked_card_widgets, function($w) use ($automation_name) {
				$content = match($w->extension_id) {
					'cerb.card.widget.automation' => $w->extension_params['automation_kata'] ?? '',
					'cerb.card.widget.chart.timeblocks' => implode(' ', [$w->extension_params['datasets_kata'] ?? '', $w->extension_params['timeblocks_kata'] ?? '']),
					'cerb.card.widget.fields' => $w->extension_params['toolbar_kata'] ?? '',
					'cerb.card.widget.form_interaction' => $w->extension_params['interactions_kata'] ?? '',
					'cerb.card.widget.sheet' => implode(' ', [$w->extension_params['data_query'] ?? '', $w->extension_params['sheet_kata'] ?? '', $w->extension_params['toolbar_kata'] ?? '']),
				};
				
				$tokens = DevblocksPlatform::services()->string()->tokenize($content, false);
				
				return in_array($automation_name, $tokens);
			});
			
			if($linked_card_widgets)
				$results['card_widget'] = array_column($linked_card_widgets, 'id');
		}
		
		// Profile widgets
		if(($linked_profile_widgets = DAO_ProfileWidget::getWhere(sprintf("%s IN (%s) AND %s LIKE %s",
			Cerb_ORMHelper::escape(DAO_ProfileWidget::EXTENSION_ID),
			implode(',', Cerb_ORMHelper::qstrArray([
				'cerb.profile.tab.widget.automation',
				'cerb.profile.tab.widget.chart.timeblocks',
				'cerb.profile.tab.widget.fields',
				'cerb.profile.tab.widget.form_interaction',
				'cerb.profile.tab.widget.map.geopoints',
				'cerb.profile.tab.widget.sheet',
			])),
			Cerb_ORMHelper::escape(DAO_ProfileWidget::EXTENSION_PARAMS_JSON),
			Cerb_ORMHelper::qstr('%' . $automation_name . '%')
		)))) {
			$linked_profile_widgets = array_filter($linked_profile_widgets, function($w) use ($automation_name) {
				$content = match($w->extension_id) {
					'cerb.profile.tab.widget.automation' => $w->extension_params['automations_kata'] ?? '',
					'cerb.profile.tab.widget.chart.timeblocks' => implode(' ', [$w->extension_params['datasets_kata'] ?? '', $w->extension_params['timeblocks_kata'] ?? '']),
					'cerb.profile.tab.widget.fields' => $w->extension_params['toolbar_kata'] ?? '',
					'cerb.profile.tab.widget.form_interaction' => $w->extension_params['interactions_kata'] ?? '',
					'cerb.profile.tab.widget.geopoints' => $w->extension_params['map_kata'] ?? '',
					'cerb.profile.tab.widget.sheet' => implode(' ', [$w->extension_params['data_query'] ?? '', $w->extension_params['sheet_kata'] ?? '', $w->extension_params['toolbar_kata'] ?? '']),
					default => '',
				};
				
				$tokens = DevblocksPlatform::services()->string()->tokenize($content, false);
				
				return in_array($automation_name, $tokens);
			});
			
			if($linked_profile_widgets)
				$results['profile_widget'] = array_column($linked_profile_widgets, 'id');
		}
		
		// Project board column
		if(($linked_project_board_columns = DAO_ProjectBoardColumn::getWhere(sprintf("%s LIKE %s",
			Cerb_ORMHelper::escape(DAO_ProjectBoardColumn::TOOLBAR_KATA),
			Cerb_ORMHelper::qstr('%' . $automation_name . '%')
		)))) {
			$linked_project_board_columns = array_filter($linked_project_board_columns, function($w) use ($automation_name) {
				$content = implode(' ', [$w->toolbar_kata, $w->functions_kata]);
				$tokens = DevblocksPlatform::services()->string()->tokenize($content, false);
				return in_array($automation_name, $tokens);
			});
			
			if($linked_project_board_columns)
				$results['project_board_column'] = array_column($linked_project_board_columns, 'id');
		}
		
		// Workspace widgets
		if(($linked_workspace_widgets = DAO_WorkspaceWidget::getWhere(sprintf("%s IN (%s) AND %s LIKE %s",
			Cerb_ORMHelper::escape(DAO_WorkspaceWidget::EXTENSION_ID),
			implode(',', Cerb_ORMHelper::qstrArray([
				'core.workspace.widget.automation',
				'cerb.workspace.widget.chart.kata',
				'cerb.workspace.widget.chart.timeblocks',
				'core.workspace.widget.form_interaction',
				'cerb.workspace.widget.map.geopoints',
				'core.workspace.widget.record.fields',
				'core.workspace.widget.sheet',
			])),
			Cerb_ORMHelper::escape(DAO_WorkspaceWidget::PARAMS_JSON),
			Cerb_ORMHelper::qstr('%' . $automation_name . '%')
		)))) {
			$linked_workspace_widgets = array_filter($linked_workspace_widgets, function($w) use ($automation_name) {
				$content = match($w->extension_id) {
					'core.workspace.widget.automation' => $w->params['automations_kata'] ?? '',
					'cerb.workspace.widget.chart.kata' => implode(' ', [$w->params['datasets_kata'] ?? '', $w->params['chart_kata'] ?? '']),
					'cerb.workspace.widget.chart.timeblocks' => implode(' ', [$w->params['datasets_kata'] ?? '', $w->params['timeblocks_kata'] ?? '']),
					'core.workspace.widget.form_interaction' => $w->params['interactions_kata'] ?? '',
					'cerb.workspace.widget.map.geopoints' => implode(' ', [$w->params['map_kata'] ?? '', $w->params['automation']['map_clicked'] ?? '']),
					'core.workspace.widget.record.fields' => $w->params['toolbar_kata'] ?? '',
					'core.workspace.widget.sheet' => implode(' ', [$w->params['data_query'] ?? '', $w->params['sheet_kata'] ?? '', $w->params['toolbar_kata'] ?? '']),
					default => '',
				};
				
				$tokens = DevblocksPlatform::services()->string()->tokenize($content, false);
				
				return in_array($automation_name, $tokens);
			});
			
			if($linked_workspace_widgets)
				$results['workspace_widget'] = array_column($linked_workspace_widgets, 'id');
		}
		
		return $results;
	}
	
	public function getEditorToolbarItems(array $toolbar): array {
		return $toolbar;
	}
	
	public function getAutocompleteSuggestions() : array {
		$toolbar_keyprefix = '(.*):await:form:elements:(.*):(.*):?toolbar:';
		
		$suggestions = [
			'*' => [
				'(.*):await:' => [
					[
						'caption' => 'form:',
						'snippet' => "form:\n\ttitle: Form Title\n\telements:\n\t\t\${1:}",
						'score' => 2000,
						'description' => "Display a form and wait for valid user input",
					],
					[
						'caption' => 'interaction:',
						'snippet' => "interaction:\n\t",
						'score' => 1999,
						'description' => "Run an interaction and wait for completion",
					],
					[
						'caption' => 'draft:',
						'snippet' => "draft:\n\t",
						'description' => "Open the email editor popup and wait for completion",
					],
					[
						'caption' => 'duration:',
						'snippet' => "duration:\n\t",
						'description' => "Wait for an amount of time",
					],
					[
						'caption' => 'record:',
						'snippet' => "record:\n\t",
						'description' => "Open a record editor popup and wait for completion",
					],
				],
				
				'(.*):await:draft:' => [
					[
						'caption' => 'uri:',
						'snippet' => "uri:",
						'score' => 2000,
						'description' => "The draft record to open in the editor",
					],
					[
						'caption' => 'output:',
						'snippet' => "output: \${1:results}",
					],
				],
				'(.*):await:draft:uri' => [
					'type' => 'cerb-uri',
					'params' => [
						'draft' => null,	
					]			
				],
				
				'(.*):await:duration:' => [
					'message: Waiting...',
					'until: 5 seconds',
				],
				
				$toolbar_keyprefix => [
					[
						'caption' => 'interaction:',
						'snippet' => 'interaction/${1:name}:'
					],
					[
						'caption' => 'menu:',
						'snippet' => 'menu/${1:name}:'
					]
				],
				$toolbar_keyprefix . '(.*):?interaction:' => [
					[
						'caption' => 'uri:',
						'snippet' => 'uri: cerb:automation:${1:}'
					],
					'label:',
					'icon:',
					'tooltip:',
					[
						'caption' => 'hidden:',
						'snippet' => 'hidden@bool: ${1:yes}'
					],
					[
						'caption' => 'badge:',
						'snippet' => 'badge: 123'
					],
					'inputs:'
				],
				$toolbar_keyprefix . '(.*):?interaction:hidden:' => [
					'yes',
					'no',
				],
				$toolbar_keyprefix . '(.*):?interaction:icon:' => [
					'type' => 'icon'
				],
				$toolbar_keyprefix . '(.*):?interaction:inputs:' => [
					'type' => 'automation-inputs'
				],
				$toolbar_keyprefix . '(.*):?interaction:uri:' => [
					'type' => 'cerb-uri',
					'params' => [
						'automation' => [
							'triggers' => [
								'cerb.trigger.interaction.worker'
							]
						]
					]
				],
				$toolbar_keyprefix . '(.*):?menu:' => [
					'label:',
					[
						'caption' => 'hidden:',
						'snippet' => 'hidden@bool: ${1:yes}'
					],
					'icon:',
					'tooltip:',
					'items:'
				],
				$toolbar_keyprefix . '(.*):?menu:icon:' => [
					'type' => 'icon'
				],
				$toolbar_keyprefix . '(.*):?menu:items:' => [
					[
						'caption' => 'interaction:',
						'snippet' => 'interaction/${1:name}:'
					],
					[
						'caption' => 'menu:',
						'snippet' => 'menu/${1:name}:'
					]
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
						'caption' => 'audio:',
						'snippet' => "audio/\${1:prompt_audio}:\n\t\${2:}",
						'description' => "Play an audio file",
					],
					[
						'caption' => 'chart:',
						'snippet' => "chart/\${1:prompt_chart}:\n\t\${2:}",
						'description' => "Display a chart visualization",
					],
					[
						'caption' => 'chooser:',
						'snippet' => "chooser/\${1:prompt_chooser}:\n\t\${2:}",
						'description' => "Open a search popup for selecting records of a given type",
					],
					[
						'caption' => 'editor:',
						'snippet' => "editor/\${1:prompt_editor}:\n\t\${2:}",
						'description' => "Prompt with a code editor",
						'interaction' => 'ai.cerb.automationBuilder.interaction.worker.await.promptEditor',
					],
					[
						'caption' => 'fileDownload:',
						'snippet' => "fileDownload/\${1:prompt_file}:\n\t\${2:}",
						'description' => "Prompt for a file download",
					],
					[
						'caption' => 'fileUpload:',
						'snippet' => "fileUpload/\${1:prompt_file}:\n\t\${2:}",
						'description' => "Prompt for a file upload",
					],
					[
						'caption' => 'map:',
						'snippet' => "map/\${1:prompt_map}:\n\t\${2:}",
						'description' => "Display an interactive map",
						'interaction' => 'ai.cerb.automationBuilder.interaction.worker.await.map',
					],
					[
						'caption' => 'query:',
						'snippet' => "query/\${1:prompt_query}:\n\t\${2:}",
						'description' => "Prompt for a search query with autocompletion",
					],
					[
						'caption' => 'say:',
						'snippet' => "say/\${1:prompt_say}:\n\t\${2:}",
						'description' => "Display arbitrary plaintext or Markdown",
						'interaction' => 'ai.cerb.automationBuilder.interaction.worker.await.say',
					],
					[
						'caption' => 'sheet:',
						'snippet' => "sheet/\${1:prompt_sheet}:\n\t\${2:}",
						'description' => "Prompt using a table with single/multiple selection, filtering, and paging",
						'interaction' => 'ai.cerb.automationBuilder.interaction.worker.await.promptSheet',
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
						'interaction' => 'ai.cerb.automationBuilder.interaction.worker.await.promptText',
					],
					[
						'caption' => 'textarea:',
						'snippet' => "textarea/\${1:prompt_textarea}:\n\t\${2:}",
						'description' => "Prompt for multiple lines of text",
					],
				],
				
				'(.*):await:form:elements:audio:' => [
					[
						'caption' => 'label:',
						'snippet' => "label: \${1:Label:}",
						'score' => 2000,
					],
					'autoplay@bool: yes',
					'controls@bool: yes',
					'hidden@bool: no',
					'loop@bool: no',
					'source:',
				],
				'(.*):await:form:elements:audio:source:' => [
					'blob: data:audio/mpeg;base64,...',
					'uri:'
				],
				'(.*):await:form:elements:audio:source:uri:' => [
					'type' => 'cerb-uri',
					'params' => [
						'resource' => null,
						'automation_resource' => null,
					],
				],
				
				'(.*):await:form:elements:chart:' => [
					[
						'caption' => 'label:',
						'snippet' => "label: \${1:Label:}",
						'score' => 2000,
					],
					'hidden@bool: no',
					'datasets:',
					'schema:',
				],
				
				'(.*):await:form:elements:chooser:' => [
					[
						'caption' => 'label:',
						'snippet' => "label: \${1:Label:}",
						'score' => 2000,
					],
					'default:',
					'hidden@bool: no',
					'record_type:',
					'query@text:',
					'multiple@bool: yes',
					'required@bool: yes',
					'autocomplete@bool: no',
				],
				'(.*):await:form:elements:chooser:record_type:' => [
					'type' => 'record-type',
				],
				'(.*):await:form:elements:chooser:is_multiple:' => [
					'yes',
					'no',
				],
				
				'(.*):await:form:elements:editor:' => [
					[
						'caption' => 'label:',
						'snippet' => "label: \${1:Label:}",
						'score' => 2000,
					],
					'syntax:',
					'default:',
					'hidden@bool: no',
					'validation@raw:',
					'readonly@bool: yes',
					'line_numbers@bool: no',
					'toolbar:',
				],
				'(.*):await:form:elements:editor:line_numbers:' => [
					'yes',
					'no',
				],
				'(.*):await:form:elements:editor:readonly:' => [
					'yes',
					'no',
				],
				'(.*):await:form:elements:editor:syntax:' => [
					'cerb_query_data',
					'cerb_query_search',
					'html',
					'json',
					'kata',
					'markdown',
					'text',
					'yaml',
				],
				
				'(.*):await:form:elements:fileDownload:' => [
					[
						'caption' => 'label:',
						'snippet' => "label: \${1:Label:}",
						'score' => 2000,
					],
					'hidden@bool: no',
					[
						'caption' => 'uri:',
						'snippet' => "uri: cerb:\${1:}",
						'score' => 1999,
					],
					[
						'caption' => 'filename:',
						'snippet' => "filename: \${1:example.zip}",
						'score' => 1998,
					],
				],
				'(.*):await:form:elements:fileDownload:uri:' => [
					'type' => 'cerb-uri',
					'params' => [
						'attachment' => null,
						'automation_resource' => null,
						'resource' => null,
					],
				],
				
				'(.*):await:form:elements:fileUpload:' => [
					[
						'caption' => 'label:',
						'snippet' => "label: \${1:Label:}",
						'score' => 2000,
					],
					'as:',
					'required@bool: yes',
					'validation@raw:',
				],
				'(.*):await:form:elements:fileUpload:as:' => [
					'attachment',
					'automation_resource',
				],
			
				'(.*):await:form:elements:map:' => [
					[
						'caption' => 'resource:',
						'snippet' => "resource:\n\turi: cerb:resource:\${1:}",
						'score' => 2000,
					],
					'hidden@bool: no',
					'projection:',
					[
						'caption' => 'regions:',
						'snippet' => "regions:\n\tproperties:\n\t#label:\n\t#filter:\n\t#fill:",
						'description' => "Define the shapes used in the base map (countries, states, etc.)",
						'score' => 1999,
					],
					'points:',
				],

				'(.*):await:form:elements:map:resource:' => [
					[
						'caption' => 'uri:',
						'snippet' => "uri: cerb:resource:\${1:}",
						'score' => 2000,
					],
				],

				'(.*):await:form:elements:map:resource:uri:' => [
					'type' => 'cerb-uri',
					'params' => [
						'resource' => [
							'types' => [
								ResourceType_Map::ID,
							]
						]
					],
				],

				'(.*):await:form:elements:map:projection:' => [
					'type:',
					'scale:',
					'center:',
					'zoom:',
				],
				
				'(.*):await:form:elements:map:projection:type:' => [
					'mercator',
					'naturalEarth',
					'albersUsa',
				],
				
				'(.*):await:form:elements:map:projection:center:' => [
					'latitude:',
					'longitude:',
				],
				
				'(.*):await:form:elements:map:projection:zoom:' => [
					'latitude:',
					'longitude:',
					'scale:',
				],
				
				'(.*):await:form:elements:map:regions:' => [
					'properties:',
					'label:',
					'filter:',
					'fill:',
				],
				
				'(.*):await:form:elements:map:regions:properties:' => [
					'resource:',
					[
						'caption' => 'resource:',
						'snippet' => "resource:\n  uri: cerb:resource:\${1:}",
						'score' => 2000,
					],
					'data:',
					'join:',
				],
				
				'(.*):await:form:elements:map:regions:properties:resource:' => [
					[
						'caption' => 'uri:',
						'snippet' => "uri: cerb:resource:\${1:}",
						'score' => 2000,
					],
				],

				'(.*):await:form:elements:map:regions:properties:resource:uri:' => [
					'type' => 'cerb-uri',
					'params' => [
						'resource' => [
							'types' => [
								ResourceType_MapProperties::ID,
							]
						]
					]
				],
				
				'(.*):await:form:elements:map:regions:properties:join:' => [
					[
						'caption' => 'property:',
						'snippet' => "property: \${1:name}",
						'score' => 2000,
					],
					'case:',
				],
				
				'(.*):await:form:elements:map:regions:properties:join:case:' => [
					[
						'caption' => 'upper',
						'snippet' => "upper",
						'description' => "Normalize values of keys to upper case",
						'score' => 2000,
					],
					[
						'caption' => 'lower',
						'snippet' => 'lower',
						'description' => "Normalize values of keys to lower case",
						'score' => 1999,
					],
				],
				
				'(.*):await:form:elements:map:regions:label:' => [
					[
						'caption' => 'title:',
						'snippet' => "title: \${1:key}",
						'description' => 'Define the property use as title',
						'score' => 2000,
					],
					[
						'caption' => 'properties:',
						'snippet' => "properties:\n  key:\n    label: Value\n    format: number\n  #key2:\n    #label: Value 2\n    #format: number",
						'description' => 'Define the properties which should be displayed',
						'score' => 1999,
					],
				],
				
				'(.*):await:form:elements:map:regions:filter:' => [
					[
						'caption' => 'property:',
						'snippet' => "property: \${1:key}",
						'score' => 2001,
					],
					[
						'caption' => 'is:',
						'snippet' => "is: Value",
						'score' => 2000,
					],
					[
						'caption' => 'is@list:',
						'snippet' => "is@list:\n  Value 1\n  Value 2\n  Value 3",
						'score' => 1999,
					],
					[
						'caption' => 'is@csv:',
						'snippet' => "is@csv: Value 1, Value 2, Value 3",
						'score' => 1998,
					],
					[
						'caption' => 'not:',
						'snippet' => "not: Value",
						'score' => 1997,
					],
					[
						'caption' => 'not@list:',
						'snippet' => "not@list:\n  Value 1\n  Value 2\n  Value 3",
						'score' => 1996,
					],
					[
						'caption' => 'not@csv:',
						'snippet' => "not@csv: Value 1, Value 2, Value 3",
						'score' => 1996,
					],
				],
				
				'(.*):await:form:elements:map:regions:fill:' => [
					[
						'caption' => 'color_key:',
						'snippet' => "color_key:\n\tproperty: key",
						'description' => "Select colors directly from a property.",
						'score' => 2000,
					],
					[
						'caption' => 'color_map:',
						'snippet' => "color_map:\n\tproperty: key\n\tcolors:\n\t\t1: gray\n\t\t2: blue\n\t\t3: green\n\t\t4: orange\n\t\t5: red",
						'description' => "Associate colors with specific property values",
						'score' => 1999,
					],
					[
						'caption' => 'choropleth:',
						'snippet' => "choropleth:\n\tproperty: key\n\tclasses: value",
						'description' => "Interpolate color intensity on a scale based on a numeric property",
						'score' => 1998,
					],
				],
				
				'(.*):await:form:elements:map:points:' => [
					[
						'caption' => 'resource:',
						'snippet' => "resource:\n  uri: cerb:resource:\${1:}",
						'score' => 2000,
					],
					[
						'caption' => 'data:',
						'snippet' => "data:\n\tpoint/berlin:\n\t\tlatitude: 52.549636074382285\n\t\tlongitude: 13.403320312499998\n\t\tproperties:\n\t\t\tname: Berlin\n\t\t\tcountry: Germany\n\t\t\tcontinent: Europe\n\tpoint/los_angeles:\n\t\tlatitude: 34.08906131584994\n\t\tlongitude: 241.69921874999997\n\t\tproperties:\n\t\t\tname: Los Angeles\n\t\t\tcountry: United States of America\n\t\t\tcontinent: North America",
						'description' => "Select colors directly from a property",
						'score' => 1999,
					],
					[
						'caption' => 'label:',
						'snippet' => "label:\n\ttitle: key\n\tproperties:\n\t\tkey:\n\t\t\tlabel: Value\n\t\tkey2:\n\t\t\tlabel: Value 2\n\t\t\tformat: number",
						'description' => "Define the properties which should be displayed",
						'score' => 1998,
					],
					[
						'caption' => 'filter:',
						'snippet' => "filter:\n\tproperty: \${1:key}\n\t#is@list:\n\t\t#Value 1\n\t\t#Value 2",
						'score' => 1997,
					],
					[
						'caption' => 'size:',
						'snippet' => "size:\n\tdefault: \${1:2.5}\n\t#value_map:",
						'score' => 1996,
					],
					[
						'caption' => 'fill:',
						'snippet' => "fill:\n\tdefault: \${1:red}\n\t#color_map:\n\t\t#property: key\n\t\t#colors: \n\t\t\t#1: red\n\t\t\t#2: blue\n\t\t\t#3: green",
						'score' => 1995,
					],
				],
				
				'(.*):await:form:elements:map:points:resource:uri:' => [
					'type' => 'cerb-uri',
					'params' => [
						'resource' => [
							'types' => [
								ResourceType_MapPoints::ID,
							]
						]
					],
				],
				
				'(.*):await:form:elements:map:points:filter:' => [
					[
						'caption' => 'is:',
						'snippet' => "is: Value",
						'score' => 2000,
					],
					[
						'caption' => 'is@list:',
						'snippet' => "is@list:\n  Value 1\n  Value 2\n  Value 3",
						'score' => 1999,
					],
					[
						'caption' => 'is@csv:',
						'snippet' => "is@csv: Value 1, Value 2, Value 3",
						'score' => 1998,
					],
					[
						'caption' => 'not:',
						'snippet' => "not: Value",
						'score' => 1997,
					],
					[
						'caption' => 'not@list:',
						'snippet' => "not@list:\n  Value 1\n  Value 2\n  Value 3",
						'score' => 1996,
					],
					[
						'caption' => 'not@csv:',
						'snippet' => "not@csv: Value 1, Value 2, Value 3",
						'score' => 1996,
					],
					[
						'caption' => 'property:',
						'snippet' => "property: \${1:key}",
						'score' => 1995,
					],
				],
				
				'(.*):await:form:elements:map:points:size:' => [
					[
						'caption' => 'default:',
						'snippet' => "default: \${1:2.5}",
						'score' => 2000,
					],
					[
						'caption' => 'value_map:',
						'snippet' => "value_map:\n\tproperty: \${1:key}\n\tvalues:\n\t\t1: 5.0\n\t\t2: 7.5",
						'score' => 1999,
					],
				],
				
				'(.*):await:form:elements:map:points:fill:' => [
					[
						'caption' => 'default:',
						'snippet' => "default: \${1:red}",
						'score' => 2000,
					],
					[
						'caption' => 'color_map:',
						'snippet' => "color_map:\n\tproperty: \${1:key}\n\tcolors: \n\t\t1: red\n\t\t2: blue\n\t\t3: green",
						'score' => 1999,
					],
				],
				
				'(.*):await:form:elements:query:' => [
					[
						'caption' => 'label:',
						'snippet' => "label: \${1:Label:}",
						'score' => 2000,
					],
					[
						'caption' => 'record_type:',
						'snippet' => "record_type: \${1::}",
						'score' => 1999,
					],
					'hidden@bool: no',
					'default:',
				],
				'(.*):await:form:elements:query:record_type:' => [
					'type' => 'record-type',
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
					'hidden@bool: no',
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
					'hidden@bool: no',
					'limit:',
					'page:',
					'required@bool: yes',
					'toolbar:',
					'validation@raw:',
				],
				'(.*):await:form:elements:sheet:data:' => [
					[
						'caption' => 'automation:',
						'snippet' => "automation:\n\turi: cerb:automation:\${1:cerb.data.records}\n\tinputs:\n\t\trecord_type: ticket\n\t\tquery_required: status:o\n",
					],
					[
						'caption' => '(manual)',
						'snippet' => "0:\n\tkey: key1\n\tvalue: value1\n1:\n\tkey: key2\n\tvalue: value2\n",
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
					[
						'caption' => 'card:',
						'snippet' => "card/\${1:_label}:",
					],
					'date/key:',
					'icon/key:',
					'interaction/key:',
					'link/key:',
					'selection/key:',
					'slider/key:',
					'text/key:',
					'time_elapsed/key:',
				],
				'(.*):await:form:elements:sheet:schema:columns:card:' => [
					'label:',
					'params:',
				],
				'(.*):await:form:elements:sheet:schema:columns:card:params:' => [
					'bold@bool: yes',
					'context:',
					'context_key:',
					'context_template@raw:',
					'icon:',
					'id:',
					'id_key:',
					'id_template@raw:',
					'image@bool: yes',
					'label:',
					'label_key:',
					'label_template@raw:',
					'color@raw:',
					'text_color@raw:',
					'text_size@raw: 150%',
					'underline@bool: yes',
				],
				'(.*):await:form:elements:sheet:schema:columns:card:params:icon:' => [
					[
						'caption' => 'image: circle-ok',
						'snippet' => "image: \${1:circle-ok}",
					],
					'image_key: icon_key',
					'image_template@raw:',
					'record_uri@raw:',
				],
				'(.*):await:form:elements:sheet:schema:columns:card:params:image:' => [
					'type' => 'icon'
				],
				'(.*):await:form:elements:sheet:schema:columns:card:params:record_uri:' => [
					'type' => 'cerb-uri',
				],
				'(.*):await:form:elements:sheet:schema:columns:date:' => [
					'params:',
				],
				'(.*):await:form:elements:sheet:schema:columns:date:params:' => [
					'format: d-M-Y H:i:s T',
					'value: 1577836800',
					'value_key: updated',
				],
				'(.*):await:form:elements:sheet:schema:columns:icon:' => [
					'label:',
					'params:',
				],
				'(.*):await:form:elements:sheet:schema:columns:icon:params:' => [
					[
						'caption' => 'image: circle-ok',
						'snippet' => "image: \${1:circle-ok}",
					],
					'image_key: icon_key',
					'image_template@raw:',
					'record_uri@raw:',
					'color@raw:',
					'text_color@raw:',
					'text_size@raw: 150%',
				],
				'(.*):await:form:elements:sheet:schema:columns:icon:params:image:' => [
					'type' => 'icon',
				],
				'(.*):await:form:elements:sheet:schema:columns:icon:params:record_uri:' => [
					'type' => 'cerb-uri',
				],
				'(.*):await:form:elements:sheet:schema:columns:interaction:' => [
					'label:',
					'params:',
				],
				'(.*):await:form:elements:sheet:schema:columns:interaction:params:' => [
					'inputs:',
					'text: Link title',
					'text_key: _label',
					'text_template@raw:',
					'color@raw:',
					'text_color@raw:',
					'text_size@raw: 150%',
					'uri:',
					'uri_key:',
					'uri_template@raw:',
				],
				'(.*):await:form:elements:sheet:schema:columns:interaction:params:inputs:' => [
					'type' => 'automation-inputs',
				],
				'(.*):await:form:elements:sheet:schema:columns:interaction:params:uri:' => [
					'type' => 'cerb-uri',
					'params' => [
						'automation' => [
							'triggers' => [
								'cerb.trigger.interaction.worker',
							]
						]
					]
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
					'color@raw:',
					'text_color@raw:',
					'text_size@raw: 150%'
				],
				'(.*):await:form:elements:sheet:schema:columns:markdown:' => [
					'params:',
				],
				'(.*):await:form:elements:sheet:schema:columns:markdown:params:' => [
					'value: **Markdown**',
					'value_key: key',
					'value_template@raw: {{key}}',
				],
				'(.*):await:form:elements:sheet:schema:columns:search:' => [
					'label:',
					'params:',
				],
				'(.*):await:form:elements:sheet:schema:columns:search:params:' => [
					[
						'caption' => 'context: ticket',
						'snippet' => "context: \${1:ticket}",
					],
					'query_key: query',
					'query_template@raw:',
					'color@raw:',
					'text_color@raw:',
					'text_size@raw: 150%'
				],
				'(.*):await:form:elements:sheet:schema:columns:search:params:context:' => [
					'type' => 'record-type',
				],
				'(.*):await:form:elements:sheet:schema:columns:search_button:' => [
					'label:',
					'params:',
				],
				'(.*):await:form:elements:sheet:schema:columns:search_button:params:' => [
					[
						'caption' => 'context: ticket',
						'snippet' => "context: \${1:ticket}",
					],
					'query_key: query',
					'query_template@raw:',
					'color@raw:',
					'text_color@raw:',
					'text_size@raw: 150%'
				],
				'(.*):await:form:elements:sheet:schema:columns:search_button:params:context:' => [
					'type' => 'record-type',
				],
				'(.*):await:form:elements:sheet:schema:columns:selection:' => [
					'label:',
					'params:',
				],
				'(.*):await:form:elements:sheet:schema:columns:selection:params:' => [
					[
						'caption' => 'mode:',
						'snippet' => "mode: \${1:single}",
						'description' => "`single` or `multiple` row selection",
					],
					'label: Description',
					'label_key: description',
					'label_template@raw: {{description}}',
					'value: 123',
					'value_key: key',
					'value_template@raw: {{key}}',
					'color@raw:',
					'text_color@raw:',
					'text_size@raw: 150%'
				],
				'(.*):await:form:elements:sheet:schema:columns:selection:params:mode:' => [
					'single',
					'multiple',
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
					'color@raw:',
					'text_color@raw:',
					'text_size@raw: 150%',
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
					'color@raw:',
					'text_color@raw:',
					'text_size@raw: 150%',
				],
				'(.*):await:form:elements:sheet:schema:columns:text:params:icon:' => [
					[
						'caption' => 'image: circle-ok',
						'snippet' => "image: \${1:circle-ok}",
					],
					'image_key: icon_key',
					'image_template@raw:',
					'record_uri@raw:',
				],
				'(.*):await:form:elements:sheet:schema:columns:text:params:icon:image:' => [
					'type' => 'icon',
				],
				'(.*):await:form:elements:sheet:schema:columns:text:params:icon:record_uri:' => [
					'type' => 'cerb-uri',
				],
				'(.*):await:form:elements:sheet:schema:columns:time_elapsed:' => [
					'label:',
					'params:',
				],
				'(.*):await:form:elements:sheet:schema:columns:time_elapsed:params:' => [
					'precision: 2',
					'value: 123',
					'value_key: key',
					'value_template@raw: {{key}}',
					'color@raw:',
					'text_color@raw:',
					'text_size@raw: 150%',
				],
				'(.*):await:form:elements:sheet:schema:layout:' => [
					'filtering@bool: yes',
					'headings@bool: yes',
					'paging@bool: yes',
					[
						'caption' => 'params:',
						'snippet' => "params:\n\t\${1:}",
					],
					[
						'caption' => 'style:',
						'snippet' => "style: \${1:table}",
					],
					[
						'caption' => 'title_column:',
						'snippet' => "title_column: \${1:_label}",
						'description' => "The column to emphasize as the row title",
					],
				],
				'(.*):await:form:elements:sheet:schema:layout:style:' => [
					[
						'caption' => 'table',
						'snippet' => 'table',
						'description' => "Display the rows as a table",
						'score' => 2000,
					],
					[
						'caption' => 'columns',
						'snippet' => 'columns',
						'description' => "Display items as columns",
					],
					[
						'caption' => 'fieldsets',
						'snippet' => 'fieldsets',
						'description' => "Display the rows as fieldsets",
					],
					[
						'caption' => 'grid',
						'snippet' => 'grid',
						'description' => "Display the rows as a grid",
					],
				],
				'(.*):await:form:elements:sheet:toolbar:' => [
				],
				
				'(.*):await:form:elements:submit:' => [
					'buttons:',
					'continue@bool: yes',
					'hidden@bool: no',
					'reset@bool: no',
				],
				
				'(.*):await:form:elements:submit:buttons:' => [
					[
						'caption' => 'continue:',
						'snippet' => "continue/\${1:yes}:\n\tlabel: Continue\n\ticon: circle-ok\n\ticon_at: start\n\tvalue: yes\n",
					],
					[
						'caption' => 'reset:',
						'snippet' => "reset:\n\tlabel: Reset\n\ticon: refresh\n\ticon_at: start",
					],
				],
				
				'(.*):await:form:elements:submit:buttons:continue:' => [
					[
						'caption' => 'label:',
						'snippet' => "label: \${1:Label:}",
						'score' => 2000,
					],
					'hidden@bool: yes',
					'icon:',
					'icon_at:',
					'style:',
					'value:',
				],
				
				'(.*):await:form:elements:submit:buttons:continue:icon:' => [
					'type' => 'icon',
				],
				
				'(.*):await:form:elements:submit:buttons:continue:icon_at:' => [
					'start',
					'end',
				],
				
				'(.*):await:form:elements:submit:buttons:continue:style:' => [
					'outline',
					'secondary',
				],
				
				'(.*):await:form:elements:submit:buttons:reset:' => [
					[
						'caption' => 'label:',
						'snippet' => "label: \${1:Label:}",
						'score' => 2000,
					],
					'hidden@bool: yes',
					'icon:',
					'icon_at:',
					'style:',
				],
				
				'(.*):await:form:elements:submit:buttons:reset:icon' => [
					'type' => 'icon',
				],
				
				'(.*):await:form:elements:submit:buttons:reset:icon_at:' => [
					'start',
					'end',
				],
				
				'(.*):await:form:elements:submit:buttons:reset:style:' => [
					'outline',
					'secondary',
				],
				
				'(.*):await:form:elements:text:' => [
					[
						'caption' => 'label:',
						'snippet' => "label: \${1:Label:}",
						'score' => 2000,
					],
					'default:',
					'hidden@bool: no',
					'max_length@int:',
					'min_length@int:',
					'placeholder:',
					'required@bool: yes',
					'truncate@bool: yes',
					'type:',
					'validation@raw:',
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
					'password',
					'uri',
					'url',
				],
				
				'(.*):await:form:elements:textarea:' => [
					'default:',
					'hidden@bool: no',
					'label:',
					'max_length@int:',
					'min_length@int:',
					'placeholder:',
					'required@bool: yes',
					'truncate@bool: yes',
					'validation@raw:',
				],
				
				'(.*):await:interaction:' => [
					'inputs:',
					[
						'caption' => 'output:',
						'snippet' => "output: \${1:results}",
					],
					'uri:',
				],
				'(.*):await:interaction:inputs:' => [
					'type' => 'automation-inputs',
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
				
				'(.*):await:record:' => [
					'uri:',
					[
						'caption' => 'output:',
						'snippet' => "output: \${1:results}",
					],
				],
				'(.*):await:record:uri:' => [
					'type' => 'cerb-uri',
				],
			]
		];
		
		// Chart schema
		$suggestions['*'] += CerberusApplication::kataAutocompletions()->chart(
			'(.*?):await:form:elements:chart:schema:',
			true
		);
		
		// Dataset
		$suggestions['*'] += CerberusApplication::kataAutocompletions()->dataset(
			'(.*?):await:form:elements:chart:datasets:',
		);
		
		$suggestions['*']['(.*):return:callout:'] = [
			'selector: #someElement',
			'message: This is the callout text',
			'my: right top',
			'at: left bottom'
		];
		
		$suggestions['*']['(.*):return:'] = [
			'alert:',
			'callout:',
			'clipboard:',
			'open_link:',
			'open_url:',
			'snippet:',
			'timer:',
		];
		
		return $suggestions;
	}
}
