<h2>{'common.team'|devblocks_translate|capitalize}</h2>

<div id="tabsSetupTeam">
	<ul>
		{$tabs = ['config', 'roles', 'groups', 'workers']}
		<li data-alias="config"><a href="{devblocks_url}ajax.php?c=config&a=invoke&module=team&action=renderTabConfig{/devblocks_url}">{'common.configure'|devblocks_translate|capitalize}</a></li>
		<li data-alias="roles"><a href="{devblocks_url}ajax.php?c=config&a=invoke&module=team&action=renderTabRoles{/devblocks_url}">{'common.roles'|devblocks_translate|capitalize}</a></li>
		<li data-alias="groups"><a href="{devblocks_url}ajax.php?c=config&a=invoke&module=team&action=renderTabGroups{/devblocks_url}">{'common.groups'|devblocks_translate|capitalize}</a></li>
		<li data-alias="workers"><a href="{devblocks_url}ajax.php?c=config&a=invoke&module=team&action=renderTabWorkers{/devblocks_url}">{'common.workers'|devblocks_translate|capitalize}</a></li>
	</ul>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var tabOptions = Devblocks.getDefaultjQueryUiTabOptions();
	tabOptions.active = Devblocks.getjQueryUiTabSelected('tabsSetupTeam', '{$tab}');
	
	$('#tabsSetupTeam').tabs(tabOptions);
});
</script>