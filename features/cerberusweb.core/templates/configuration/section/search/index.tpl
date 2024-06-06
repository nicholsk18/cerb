<h2>Search Indexes</h2>

<div id="cerbSetupSearchSchemas">
{foreach from=$schemas item=schema}
	{$engine = $schema->getEngine()}

	<fieldset>
		<legend>
			{$schema->manifest->name}
			(<a data-cerb-schema-id="{$schema->manifest->id}">{'common.edit'|devblocks_translate|lower}</a>)
		</legend>

		{if $engine}
			{$schema_meta = $schema->getIndexMeta()}
			<div>
				{if $schema_meta.count}
					{if $schema_meta.is_count_approximate}~{/if}
					<b>{$schema_meta.count|number_format}</b> records indexed in <b>{$engine->manifest->name}</b>.
				{else}
					Records indexed in <b>{$engine->manifest->name}</b>.
				{/if}
			</div>
		{/if}

	</fieldset>
{/foreach}
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $schemas_container = $('#cerbSetupSearchSchemas');

	$schemas_container.find('legend a').on('click', function(e) {
		e.stopPropagation();
		let schema_id = $(this).attr('data-cerb-schema-id');
		genericAjaxPopup('peek','c=config&a=invoke&module=search&action=showSearchSchemaPeek&ext_id=' + encodeURIComponent(schema_id), null, false);
	});
});
</script>