<h2>Cache</h2>

<fieldset id="setupConfigCache">
	<legend>
		{$cacher->manifest->name}
		(<a>{'common.edit'|devblocks_translate|lower}</a>)
	</legend>
	
	<div>
		{$cacher->renderStatus()}
	</div>
</fieldset>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $fieldset = $('#setupConfigCache');
	$fieldset.find('legend a').on('click', function(e) {
		e.stopPropagation();
		genericAjaxPopup('peek','c=config&a=invoke&module=cache&action=showCachePeek', null, false);
	});
});
</script>