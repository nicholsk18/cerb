{$uniqid = uniqid('automationExport')}

<div id="{$uniqid}">
    <fieldset class="peek">
        <legend>{{'common.package'|devblocks_translate|capitalize}}</legend>
        <textarea data-editor-mode="ace/mode/json" data-editor-readonly="true">{$export_json}</textarea>
    </fieldset>

    <fieldset class="peek">
        <legend>{{'common.workflow'|devblocks_translate|capitalize}}</legend>
        <textarea data-editor-mode="ace/mode/cerb_kata" data-editor-readonly="true">{$export_workflow}</textarea>
    </fieldset>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
    const $div = $('#{$uniqid}');
    const $popup = genericAjaxPopupFind($div);

    $popup.one('popup_open', function() {
        $popup.dialog('option', 'title', '{'common.export'|devblocks_translate|capitalize}: {{'common.automation'|devblocks_translate}|capitalize}');
        
        const $textarea = $popup.find('textarea');
        $textarea.cerbCodeEditor().nextAll('pre.ace_editor');
    });
});
</script>