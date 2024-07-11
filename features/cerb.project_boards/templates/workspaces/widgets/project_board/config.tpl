<div id="widget{$widget->id}Config" style="margin-top:10px;">
	<fieldset class="peek">
		<legend>Display this project board</legend>
		
		<b><a class="cerb-chooser" data-context="{CerberusContexts::CONTEXT_PROJECT_BOARD}" data-single="true">ID</a>:</b>
		
		<div style="margin-left:10px;">
			<input type="text" name="params[project_board_id]" value="{$widget->params.project_board_id}" class="placeholders" style="width:95%;padding:5px;border-radius:5px;" autocomplete="off" spellcheck="false">
		</div>
	</fieldset>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $config = $('#widget{$widget->id}Config');
	var $input = $config.find('input[name="params[project_board_id]"]');
	
	$config.find('.cerb-chooser').cerbChooserTrigger()
		.on('cerb-chooser-selected', function(e) {
			{literal}$input.val(e.values[0] + '{# ' + e.labels[0] + ' #}');{/literal}
		})
		;
});
</script>