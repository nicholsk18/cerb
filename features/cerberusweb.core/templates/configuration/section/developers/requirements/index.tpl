<h2>Requirements</h2>

<div id="cerbConfigRequirements">
{if $errors}
    {foreach from=$errors item=error}
        <div class="error-box">{$error}</div>
    {/foreach}
{else}
    <div class="help-box">Your server is fully compatible with Cerb.</div>
{/if}
</div>