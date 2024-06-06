{$view_context = CerberusContexts::CONTEXT_ORG}
{$view_fields = $view->getColumnsAvailable()}
{$results = $view->getData()}
{$total = $results[1]}
{$data = $results[0]}
{$data_count = count($data)}
{$avatar_limit = 50}

{include file="devblocks:cerberusweb.core::internal/views/view_marquee.tpl" view=$view}

<table cellpadding="0" cellspacing="0" border="0" class="worklist" width="100%" {if array_key_exists('header_color', $view->options) && $view->options.header_color}style="background-color:{$view->options.header_color};"{/if}>
	<tr>
		<td nowrap="nowrap"><span class="title">{$view->name}</span></td>
		<td nowrap="nowrap" align="right" class="title-toolbar">
			{if $active_worker->hasPriv("contexts.{$view_context}.create")}<a title="{'common.add'|devblocks_translate|capitalize}" class="minimal cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_ORG}" data-context-id="0"><span class="glyphicons glyphicons-circle-plus"></span></a>{/if}
			<a data-cerb-worklist-icon-search title="{'common.search'|devblocks_translate|capitalize}" class="minimal"><span class="glyphicons glyphicons-search"></span></a>
			<a data-cerb-worklist-icon-customize title="{'common.customize'|devblocks_translate|capitalize}" class="minimal"><span class="glyphicons glyphicons-cogwheel"></span></a>
			<a data-cerb-worklist-icon-subtotals title="{'common.subtotals'|devblocks_translate|capitalize}" class="minimal"><span class="glyphicons glyphicons-signal"></span></a>
			{if $active_worker->hasPriv("contexts.{$view_context}.import")}<a data-cerb-worklist-icon-import title="{'common.import'|devblocks_translate|capitalize}"><span class="glyphicons glyphicons-file-import"></span></a>{/if}
			{if $active_worker->hasPriv("contexts.{$view_context}.export")}<a data-cerb-worklist-icon-export title="{'common.export'|devblocks_translate|capitalize}" class="minimal"><span class="glyphicons glyphicons-file-export"></span></a>{/if}
			<a data-cerb-worklist-icon-copy title="{'common.copy'|devblocks_translate|capitalize}"><span class="glyphicons glyphicons-duplicate"></span></a>
			<a data-cerb-worklist-icon-refresh title="{'common.refresh'|devblocks_translate|capitalize}" class="minimal"><span class="glyphicons glyphicons-refresh"></span></a>
			<input type="checkbox" class="select-all">
		</td>
	</tr>
</table>

<div id="{$view->id}_tips" class="block" style="display:none;margin:10px;padding:5px;">Analyzing...</div>
<form id="customize{$view->id}" name="customize{$view->id}" action="#"></form>
<form id="viewForm{$view->id}" name="viewForm{$view->id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="view_id" value="{$view->id}">
<input type="hidden" name="context_id" value="{$view_context}">
<input type="hidden" name="id" value="{$view->id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="org">
<input type="hidden" name="action" value="viewExplore">
<input type="hidden" name="explore_from" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellpadding="1" cellspacing="0" border="0" width="100%" class="worklistBody">

	{* Column Headers *}
	<thead>
	<tr>
		{if !array_key_exists('disable_watchers', $view->options) || !$view->options.disable_watchers}
		<th class="no-sort" style="text-align:center;width:40px;padding-left:0;padding-right:0;" title="{'common.watchers'|devblocks_translate|capitalize}">
			<span class="glyphicons glyphicons-eye-open"></span>
		</th>
		{/if}
		
		{if $data_count <= $avatar_limit}
		<th class="no-sort" style="text-align:center;width:40px;padding-left:0;padding-right:0;" title="{'common.photo'|devblocks_translate|capitalize}">
			<span class="glyphicons glyphicons-camera"></span>
		</th>
		{/if}

		{foreach from=$view->view_columns item=header name=headers}
			{* start table header, insert column title and link *}
			<th class="{if array_key_exists('disable_sorting', $view->options) && $view->options.disable_sorting}no-sort{/if}">
			{if (!array_key_exists('disable_sorting', $view->options) || !$view->options.disable_sorting) && !empty($view_fields.$header->db_column)}
				<a data-cerb-worklist-sort="{$header}">{$view_fields.$header->db_label|capitalize}</a>
			{else}
				<a style="text-decoration:none;">{$view_fields.$header->db_label|capitalize}</a>
			{/if}
			
			{* add arrow if sorting by this column, finish table header tag *}
			{if $header==$view->renderSortBy}
				<span class="glyphicons {if $view->renderSortAsc}glyphicons-sort-by-attributes{else}glyphicons-sort-by-attributes-alt{/if}" style="font-size:14px;{if array_key_exists('disable_sorting', $view->options) && $view->options.disable_sorting}color:rgb(80,80,80);{else}color:rgb(39,123,213);{/if}"></span>
			{/if}
			</th>
		{/foreach}
	</tr>
	</thead>

	{* Column Data *}
	{$object_watchers = DAO_ContextLink::getContextLinks($view_context, array_keys($data), CerberusContexts::CONTEXT_WORKER)}
	{foreach from=$data item=result key=idx name=results}

	{* Bulk lazy load contacts for this page *}
	{$object_addys = []}
	{if in_array(SearchFields_ContactOrg::EMAIL_ID, $view->view_columns)}
		{$addy_ids = DevblocksPlatform::extractArrayValues($results, SearchFields_ContactOrg::EMAIL_ID)}
		{$object_addys = DAO_Address::getIds($addy_ids)}
	{/if}

	{if $smarty.foreach.results.iteration % 2}
		{$tableRowClass = "even"}
	{else}
		{$tableRowClass = "odd"}
	{/if}
	<tbody style="cursor:pointer;">
		<tr class="{$tableRowClass}">
			<td align="center" rowspan="2">
				{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=$view_context context_id=$result.c_id}
			</td>
			
			{if $data_count <= $avatar_limit}
			<td data-column="*_image" align="center" rowspan="2" nowrap="nowrap" style="padding:5px;">
				<div style="position:relative;">
					<img src="{devblocks_url}c=avatars&context=org&context_id={$result.c_id}{/devblocks_url}?v={$result.c_updated}" style="height:32px;width:32px;border-radius:16px;vertical-align:middle;">
				</div>
			</td>
			{/if}
			
			<td data-column="label" colspan="{$smarty.foreach.headers.total}">
				<input type="checkbox" name="row_id[]" value="{$result.c_id}" style="display:none;">
				<a href="{devblocks_url}c=profiles&type=org&id={$result.c_id}-{$result.c_name|devblocks_permalink}{/devblocks_url}" class="subject">{$result.c_name}</a> 
				<button type="button" class="peek cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_ORG}" data-context-id="{$result.c_id}"><span class="glyphicons glyphicons-new-window-alt"></span></button>
			</td>
		</tr>
		<tr class="{$tableRowClass}">
		{foreach from=$view->view_columns item=column name=columns}
			{if DevblocksPlatform::strStartsWith($column, "cf_")}
				{include file="devblocks:cerberusweb.core::internal/custom_fields/view/cell_renderer.tpl"}
			{elseif $column=="c_id"}
			<td data-column="{$column}">{$result.c_id}&nbsp;</td>
			{elseif $column == SearchFields_ContactOrg::EMAIL_ID}
			<td data-column="{$column}">
				{if isset($object_addys.{$result.$column})}
				{$addy = $object_addys.{$result.$column}}
				<a class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-context-id="{$addy->id}">{$addy->email}</a>
				{/if}
			</td>
			{elseif $column=="c_website"}
			<td data-column="{$column}"><a href="{$result.c_website}" target="_blank" rel="noopener noreferrer">{$result.c_website|truncate:45:'...':true}</a>&nbsp;</td>
			{elseif in_array($column, ["c_created", "c_updated"])}
			<td data-column="{$column}" title="{$result.$column|devblocks_date}">{$result.$column|devblocks_prettytime}&nbsp;</td>
			{else}
			<td data-column="{$column}">{$result.$column}</td>
			{/if}
		{/foreach}
		</tr>
	</tbody>
	{/foreach}
</table>

{if $total >= 0}
<div style="padding-top:5px;">
	{include file="devblocks:cerberusweb.core::internal/views/view_paging.tpl" view=$view}

	<div style="float:left;" id="{$view->id}_actions">
		{$view_toolbar = $view->getToolbar()}
		{include file="devblocks:cerberusweb.core::internal/views/view_toolbar.tpl" view_toolbar=$view_toolbar}
		{if !$view_toolbar['explore']}<button type="button" class="action-always-show action-explore"><span class="glyphicons glyphicons-compass"></span> {'common.explore'|devblocks_translate|lower}</button>{/if}
		{if $active_worker->hasPriv("contexts.{$view_context}.update.bulk")}<button data-cerb-worklist-action-bulk="org" type="button" class="action-always-show action-bulkupdate"><span class="glyphicons glyphicons-folder-closed"></span> {'common.bulk_update'|devblocks_translate|lower}</button>{/if}
		{if $active_worker->hasPriv("contexts.{$view_context}.merge")}<button data-cerb-worklist-action-merge type="button"><span class="glyphicons glyphicons-git-merge"></span> {'common.merge'|devblocks_translate|lower}</button>{/if}
	</div>
</div>
{/if}

<div style="clear:both;"></div>

</form>

{include file="devblocks:cerberusweb.core::internal/views/view_common_jquery_ui.tpl"}

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $frm = $('#viewForm{$view->id}');
	
	{if $pref_keyboard_shortcuts}
	$frm.bind('keyboard_shortcut',function(event) {
		var $view_actions = $('#{$view->id}_actions');
		var hotkey_activated = true;
	
		switch(event.keypress_event.which) {
			case 98: // (b) bulk update
				var $btn = $view_actions.find('button.action-bulkupdate');
			
				if(event.indirect) {
					$btn.select().focus();
					
				} else {
					$btn.click();
				}
				break;
			
			case 101: // (e) explore
				var $btn = $view_actions.find('button.action-explore');
			
				if(event.indirect) {
					$btn.select().focus();
					
				} else {
					$btn.click();
				}
				break;
				
			default:
				hotkey_activated = false;
				break;
		}
	
		if(hotkey_activated)
			event.preventDefault();
	});
	{/if}
});
</script>