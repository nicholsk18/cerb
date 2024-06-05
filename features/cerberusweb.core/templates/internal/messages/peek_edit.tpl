{$peek_context = CerberusContexts::CONTEXT_MESSAGE}
{$peek_context_id = $model->id}
{$form_id = "frmMessagePeek{uniqid()}"}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="message">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

{$ticket = $model->getTicket()}
{$headers = $model->getHeaders()}

{if $headers.from}
	<b>{'message.header.from'|devblocks_translate|capitalize}:</b> 
	{$headers.from}
	<br>
{/if}

{if $headers.to}
	<b>{'message.header.to'|devblocks_translate|capitalize}:</b> 
	{$headers.to}
	<br>
{/if}

{if $headers.subject}
	<b>{'message.header.subject'|devblocks_translate|capitalize}:</b> 
	{$headers.subject}
	<br>
{/if}

<b>{'message.header.date'|devblocks_translate|capitalize}:</b> 
{$model->created_date|devblocks_date} ({$model->created_date|devblocks_prettytime})
<br>

<br>

{if !empty($custom_fields)}
<fieldset class="peek">
	<legend>{'common.properties'|devblocks_translate}</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
</fieldset>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id) && $active_worker->hasPriv('contexts.cerberusweb.contexts.message.delete')}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to delete this message?
	</div>

	<button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" class="delete-cancel">{'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="buttons">
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok"></span> {$translate->_('common.save_changes')|capitalize}</button>
	{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="delete-prompt"><span class="glyphicons glyphicons-circle-remove"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $popup = genericAjaxPopupFind('#{$form_id}');

	Devblocks.formDisableSubmit($popup);
	
	$popup.one('popup_open', function() {
		$popup.dialog('option','title',"{'common.edit'|devblocks_translate|capitalize|escape:'javascript' nofilter}: {'common.message'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		
		// Buttons
		
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		$popup.find('button.delete-prompt').click(Devblocks.callbackPeekEditDeletePrompt);
		$popup.find('button.delete-cancel').click(Devblocks.callbackPeekEditDeleteCancel);
	});
});
</script>
