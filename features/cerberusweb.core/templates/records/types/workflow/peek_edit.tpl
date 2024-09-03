{$peek_context = CerberusContexts::CONTEXT_WORKFLOW}
{$peek_context_id = $model->id}
{$form_id = uniqid('form')}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
    <input type="hidden" name="c" value="profiles">
    <input type="hidden" name="a" value="invoke">
    <input type="hidden" name="module" value="workflow">
    <input type="hidden" name="action" value="savePeekJson">
    <input type="hidden" name="view_id" value="{$view_id}">
    {if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
    <input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

    <table cellspacing="0" cellpadding="2" border="0" width="98%">
        <tr>
            <td width="1%" nowrap="nowrap"><b>{'common.name'|devblocks_translate|capitalize}:</b></td>
            <td width="99%">
                <input type="text" name="name" value="{$model->name}" style="width:98%;" autofocus="autofocus" spellcheck="false">
            </td>
        </tr>

        {if !empty($custom_fields)}
            {include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
        {/if}
    </table>

    {include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

    {if $model->id}
    <div class="cerb-code-editor-toolbar" style="margin:0.5em 0;">
        <button type="button" data-cerb-button-template-update><span class="glyphicons glyphicons-file-import"></span> Update Template</button>
    </div>
    {/if}

    <div data-cerb-summary>
    {include file="devblocks:cerberusweb.core::records/types/workflow/peek_edit/summary.tpl"}
    </div>

    <div class="buttons" style="margin-top:10px;">
        {if $model->id}
            <button type="button" class="save"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
            <button type="button" class="save-continue"><span class="glyphicons glyphicons-circle-arrow-right"></span> {'common.save_and_continue'|devblocks_translate|capitalize}</button>
            {if $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="delete-prompt"><span class="glyphicons glyphicons-circle-remove"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
        {else}
            {*<button type="button" class="save"><span class="glyphicons glyphicons-circle-plus"></span> {'common.create'|devblocks_translate|capitalize}</button>*}
            <button type="button" class="create-continue"><span class="glyphicons glyphicons-circle-arrow-right"></span> {'common.create_and_continue'|devblocks_translate|capitalize}</button>
        {/if}
    </div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
    $(function() {
        let $frm = $('#{$form_id}');
        let $popup = genericAjaxPopupFind($frm);

        Devblocks.formDisableSubmit($frm);

        $popup.one('popup_open', function() {
            $popup.dialog('option','title',"{'Workflow'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
            $popup.find('[autofocus]:first').focus();
            $popup.css('overflow', 'inherit');

            // Buttons

            $popup.find('button.save').click(Devblocks.callbackPeekEditSave);
            $popup.find('button.save-continue').click({ mode: 'continue' }, Devblocks.callbackPeekEditSave);
            $popup.find('button.create-continue').click({ mode: 'create_continue' }, Devblocks.callbackPeekEditSave);

            {if $model->id}
            $popup.find('button[data-cerb-button-template-update').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                let formData = new FormData();
                formData.set('c', 'profiles');
                formData.set('a', 'invoke');
                formData.set('module', 'workflow');
                formData.set('action', 'showTemplateUpdatePopup');
                formData.set('id', '{$model->id}');

                let $update_popup = genericAjaxPopup('workflowTemplate', formData, '', '', '80%');
                let $summary = $popup.find('[data-cerb-summary]');

                $update_popup.on('template_updated', function(evt) {
                    evt.stopPropagation();

                    let formData = new FormData();
                    formData.set('c', 'profiles');
                    formData.set('a', 'invoke');
                    formData.set('module', 'workflow');
                    formData.set('action', 'refreshSummary');
                    formData.set('id', '{$model->id}');

                    genericAjaxPost(formData, $summary);
                });
            });

            $popup.find('button.delete-prompt').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                let formData = new FormData();
                formData.set('c', 'profiles');
                formData.set('a', 'invoke');
                formData.set('module', 'workflow');
                formData.set('action', 'showWorkflowDeletePopup');
                formData.set('id', '{$model->id}');

                let $delete_popup = genericAjaxPopup('workflowDelete', formData, '', '', '80%');

                $delete_popup.on('workflow_delete', function(evt) {
                    evt.stopPropagation();

                    {if $view_id}
                    genericAjaxGet('view{$view_id}', 'c=internal&a=invoke&module=worklists&action=refresh&id={$view_id}');
                    {/if}

                    genericAjaxPopupClose($popup, 'peek_deleted');
                });
            });
            {/if}
        });
    });
</script>
