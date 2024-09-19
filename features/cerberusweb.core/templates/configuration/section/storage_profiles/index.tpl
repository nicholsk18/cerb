<div>
	<h2>Storage Profiles</h2>
</div>

<div>
	{include file="devblocks:cerberusweb.core::search/quick_search.tpl" view=$view return_url=null reset=false focus=true}
</div>

<form id="frmCerbSetupStorageProfiles" action="{devblocks_url}{/devblocks_url}" method="POST" style="margin-bottom:5px;">
	<button type="button"><span class="glyphicons glyphicons-circle-plus"></span> {'common.add'|devblocks_translate|capitalize}</button>
</form>

{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#frmCerbSetupStorageProfiles');

	$frm.find('button').on('click', function(e) {
		e.stopPropagation();
		genericAjaxPopup('peek','c=config&a=invoke&module=storage_profiles&action=showStorageProfilePeek&id=0&view_id={$view->id|escape:'url'}',null,false,'50%');
	});
});
</script>