<h2>Database Schema</h2>

{if 'fieldsets' == $layout.style}
    {include file="devblocks:cerberusweb.core::ui/sheets/render_fieldsets.tpl"}
{elseif in_array($layout.style, ['columns','grid'])}
    {include file="devblocks:cerberusweb.core::ui/sheets/render_grid.tpl"}
{else}
    {include file="devblocks:cerberusweb.core::ui/sheets/render.tpl"}
{/if}