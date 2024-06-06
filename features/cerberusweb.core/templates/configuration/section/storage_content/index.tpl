<h2>{'common.storage'|devblocks_translate|capitalize}</h2>

<form id="frmCerbSetupStorageSchemas" action="{devblocks_url}{/devblocks_url}" method="POST" style="margin-bottom:5px;">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset>
	<legend>Database</legend>

	Data: <b>{$total_db_data|devblocks_prettybytes:2}</b><br>
	Indexes: <b>{$total_db_indexes|devblocks_prettybytes:2}</b><br>
	Total Disk Space: <b>{$total_db_size|devblocks_prettybytes:2}</b><br>
</fieldset>

<div data-cerb-schemas-container>
{foreach from=$storage_schemas item=schema key=schema_id}
	<div data-cerb-storage-schema="{$schema_id}">
	{include file="devblocks:cerberusweb.core::configuration/section/storage_content/rule.tpl"}
	</div>
{/foreach}
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#frmCerbSetupStorageSchemas');
	let $container = $frm.find('[data-cerb-schemas-container]');

	$container.on('click', function(e) {
		e.stopPropagation();
		let $target = $(e.target);

		if($target.is('[data-cerb-schema-id]')) {
			let schema_id = $target.attr('data-cerb-schema-id');
			genericAjaxPopup('peek','c=config&a=invoke&module=storage_content&action=showStorageSchemaPeek&ext_id=' + encodeURIComponent(schema_id), null, false);
		}
	});
});
</script>