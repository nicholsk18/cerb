{$view_context = 'cerb.contexts.mail.inbound.log'}
{$view_fields = $view->getColumnsAvailable()}
{$results = $view->getData()}
{$total = $results[1]}
{$data = $results[0]}

{include file="devblocks:cerberusweb.core::internal/views/view_marquee.tpl" view=$view}

<table cellpadding="0" cellspacing="0" border="0" class="worklist" width="100%" {if array_key_exists('header_color', $view->options) && $view->options.header_color}style="background-color:{$view->options.header_color};"{/if}>
    <tr>
        <td nowrap="nowrap"><span class="title">{$view->name}</span></td>
        <td nowrap="nowrap" align="right" class="title-toolbar">
            <a data-cerb-worklist-icon-search title="{'common.search'|devblocks_translate|capitalize}" class="minimal"><span class="glyphicons glyphicons-search"></span></a>
            <a data-cerb-worklist-icon-customize title="{'common.customize'|devblocks_translate|capitalize}" class="minimal"><span class="glyphicons glyphicons-cogwheel"></span></a>
            <a data-cerb-worklist-icon-subtotals title="{'common.subtotals'|devblocks_translate|capitalize}" class="minimal"><span class="glyphicons glyphicons-signal"></span></a>
            {*if $active_worker->hasPriv("contexts.{$view_context}.import")}<a data-cerb-worklist-icon-import title="{'common.import'|devblocks_translate|capitalize}"><span class="glyphicons glyphicons-file-import"></span></a>{/if*}
            {*if $active_worker->hasPriv("contexts.{$view_context}.export")}<a data-cerb-worklist-icon-export title="{'common.export'|devblocks_translate|capitalize}" class="minimal"><span class="glyphicons glyphicons-file-export"></span></a>{/if*}
            <a data-cerb-worklist-icon-refresh title="{'common.refresh'|devblocks_translate|capitalize}" class="minimal"><span class="glyphicons glyphicons-refresh"></span></a>
            <input type="checkbox" class="select-all">
        </td>
    </tr>
</table>

<div id="{$view->id}_tips" class="block" style="display:none;margin:10px;padding:5px;">Loading...</div>
<form id="customize{$view->id}" name="customize{$view->id}" action="#"></form>
<form id="viewForm{$view->id}" name="viewForm{$view->id}" action="{devblocks_url}{/devblocks_url}" method="post">
    <input type="hidden" name="view_id" value="{$view->id}">
    <input type="hidden" name="context_id" value="{$view_context}">
    <input type="hidden" name="c" value="profiles">
    <input type="hidden" name="a" value="invoke">
    <input type="hidden" name="module" value="mail_inbound_log">
    <input type="hidden" name="action" value="">
    <input type="hidden" name="explore_from" value="0">
    <input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

    <table cellpadding="1" cellspacing="0" border="0" width="100%" class="worklistBody">

        {* Column Headers *}
        <thead>
        <tr>
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
        {foreach from=$data item=result key=idx name=results}
            {if $smarty.foreach.results.iteration % 2}
                {$tableRowClass = "even"}
            {else}
                {$tableRowClass = "odd"}
            {/if}

            {capture name="title_column"}
                <input type="checkbox" name="row_id[]" value="{$result.m_id}" style="display:none;">
                <a href="{devblocks_url}c=profiles&type=mail_inbound_log&id={$result.m_id}-{$result.m_subject|devblocks_permalink}{/devblocks_url}" class="subject">{$result.m_subject}</a>
                <button type="button" class="peek cerb-peek-trigger" data-context="{$view_context}" data-context-id="{$result.m_id}"><span class="glyphicons glyphicons-new-window-alt"></span></button>
            {/capture}

            <tbody style="cursor:pointer;">
            {if !in_array('m_subject', $view->view_columns)}
                <tr class="{$tableRowClass}">
                    <td data-column="label" colspan="{$smarty.foreach.headers.total}">
                        {$smarty.capture.title_column nofilter}
                    </td>
                </tr>
            {/if}

            <tr class="{$tableRowClass}">
                {foreach from=$view->view_columns item=column name=columns}
                    {if DevblocksPlatform::strStartsWith($column, "cf_")}
                        {include file="devblocks:cerberusweb.core::internal/custom_fields/view/cell_renderer.tpl"}
                    {elseif in_array($column, ["m_created_at"])}
                        <td>
                            {if !empty($result.$column)}
                                <abbr title="{$result.$column|devblocks_date}">{$result.$column|devblocks_prettytime}</abbr>
                            {/if}
                        </td>
                    {elseif $column == "m_from_id"}
                        <td data-column="{$column}">
                            {if is_array($sender_addresses) && array_key_exists($result.$column, $sender_addresses)}
                                <a class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-context-id="{$result.$column}">{$sender_addresses[$result.$column]->email}</a>
                            {elseif $result.$column}
                                {$result.$column}
                            {/if}
                        </td>
                    {elseif $column == "m_message_id"}
                        <td data-column="{$column}">
                            {if is_array($messages) && array_key_exists($result.$column, $messages)}
                                <a class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_MESSAGE}" data-context-id="{$result.$column}">{$result.$column}</a>
                            {elseif $result.$column}
                                {$result.$column}
                            {/if}
                        </td>
                    {elseif $column == "m_status_id"}
                        <td data-column="{$column}">
                            {$status_id = $result.$column}
                            <span class="tag" style="color:white;{if 1 != $result.$column}background-color:rgb(185,50,40);{else}background-color:rgb(100,140,25);{/if}">
                            {if 1 == $result.$column}parsed{elseif 0 == $result.$column}failed{elseif 2 == $result.$column}rejected{/if}
                            </span>
                        </td>
                    {elseif $column == "m_subject"}
                        <td data-column="{$column}">
                            {$smarty.capture.title_column nofilter}
                        </td>
                    {elseif $column == "m_mailbox_id"}
                        <td data-column="{$column}">
                            {if is_array($mailboxes) && array_key_exists($result.$column, $mailboxes)}
                                <a class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_MAILBOX}" data-context-id="{$result.$column}">{$mailboxes[$result.$column]->name}</a>
                            {elseif $result.$column}
                                {$result.$column}
                            {/if}
                        </td>
                    {elseif $column == "m_ticket_id"}
                        <td data-column="{$column}">
                            {if is_array($tickets) && array_key_exists($result.$column, $tickets)}
                                <a class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_TICKET}" data-context-id="{$result.$column}">{$result.$column}</a>
                            {elseif $result.$column}
                                {$result.$column}
                            {/if}
                        </td>
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
            </div>
        </div>
    {/if}

    <div style="clear:both;"></div>

</form>

{include file="devblocks:cerberusweb.core::internal/views/view_common_jquery_ui.tpl"}

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
    $(function() {
        let $frm = $('#viewForm{$view->id}');

        {if $pref_keyboard_shortcuts}
        $frm.bind('keyboard_shortcut',function(event) {
            let $view_actions = $('#{$view->id}_actions');
            let hotkey_activated = true;

            switch(event.keypress_event.which) {
                case 101: // (e) explore
                    let $btn = $view_actions.find('button.action-explore');

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
