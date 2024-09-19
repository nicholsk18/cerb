{foreach from=$params.images item=image key=idx}
{$label = $params.labels[$idx]}
{if !empty($label)}
<fieldset>
<b>{'common.image'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<div style="float:left;margin-right:5px;">
		<img src="{$image}" class="cerb-prompt-image">
	</div>
	<div style="float:left;">
		<button type="button" class="cerb-avatar-chooser">{'common.edit'|devblocks_translate|capitalize}</button>
		<input type="hidden" name="{$namePrefix}[images][]" value="{$image}">
	</div>
	<br clear="all">
</div>

<b>{'common.label'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<textarea name="{$namePrefix}[labels][]" rows="5" cols="45" style="width:100%;height:150px;" class="placeholders">{$label}</textarea>
</div>
</fieldset>
{/if}
{/foreach}

<fieldset class="cerb-prompt-image-add-template" style="display:none;">
	<b>{'common.image'|devblocks_translate|capitalize}:</b>
	<div style="margin-left:10px;margin-bottom:0.5em;">
		<div style="float:left;margin-right:5px;">
			<img src="" class="cerb-prompt-image">
		</div>
		<div style="float:left;">
			<button type="button" class="cerb-avatar-chooser">{'common.edit'|devblocks_translate|capitalize}</button>
			<input type="hidden" name="{$namePrefix}[images][]" value="">
		</div>
		<br clear="all">
	</div>
	
	<b>{'common.label'|devblocks_translate|capitalize}:</b>
	<div style="margin-left:10px;margin-bottom:0.5em;">
		<textarea name="{$namePrefix}[labels][]" rows="5" cols="45" style="width:100%;height:150px;"></textarea>
	</div>
</fieldset>

<button type="button" class="cerb-prompt-image-add"><span class="glyphicons glyphicons-circle-plus"></span></button>

<br>
<br>

<b>Save the response to a placeholder named:</b> {include file="devblocks:cerberusweb.core::help/docs_button.tpl" url="https://cerb.ai/guides/bots/prompts#saving-placeholders"}
<div style="margin-left:10px;margin-bottom:0.5em;">
	&#123;&#123;<input type="text" name="{$namePrefix}[var]" size="32" value="{if !empty($params.var)}{$params.var}{else}placeholder{/if}" required="required" spellcheck="false">&#125;&#125;
	<div style="margin-top:5px;">
		<i><small>The placeholder name must be lowercase, without spaces, and may only contain a-z, 0-9, and underscores (_)</small></i>
	</div>
</div>

<b>Format the placeholder with this template:</b> {include file="devblocks:cerberusweb.core::help/docs_button.tpl" url="https://cerb.ai/guides/bots/prompts#formatting"}
<div style="margin-left:10px;margin-bottom:0.5em;">
	<textarea name="{$namePrefix}[var_format]" class="placeholders">{$params.var_format|default:'{{message}}'}</textarea>
</div>

<b>Validate the placeholder with this template:</b> {include file="devblocks:cerberusweb.core::help/docs_button.tpl" url="https://cerb.ai/guides/bots/prompts#validation"}
<div style="margin-left:10px;margin-bottom:0.5em;">
	<textarea name="{$namePrefix}[var_validate]" class="placeholders">{$params.var_validate}</textarea>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $action = $('#{$namePrefix}_{$nonce}');
	var $add = $action.find('button.cerb-prompt-image-add');
	var $template = $action.find('fieldset.cerb-prompt-image-add-template');
	
	$action.find('button.cerb-avatar-chooser').each(function() {
		var $avatar_chooser = $(this);
		var $avatar_image = $avatar_chooser.parent().parent().find('img.cerb-prompt-image');
		ajax.chooserAvatar($avatar_chooser, $avatar_image);
	});
	
	$add.on('click', function(e) {
		var $clone = $template
			.clone()
			.removeClass('cerb-prompt-image-add-template')
			;
		$clone.insertBefore($template);
		
		// Code editor
		$clone.find('textarea').addClass('placeholders').cerbCodeEditor();

		// Avatar chooser
		var $avatar_chooser = $clone.find('button.cerb-avatar-chooser');
		var $avatar_image = $avatar_chooser.parent().parent().find('img.cerb-prompt-image');
		ajax.chooserAvatar($avatar_chooser, $avatar_image);
		
		$clone.fadeIn();
	});
});
</script>
