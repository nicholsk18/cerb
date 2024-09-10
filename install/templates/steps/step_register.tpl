<h2>Congratulations!  Setup has been successfully completed.</h2>

<form action="index.php" method="POST">
<input type="hidden" name="step" value="{$smarty.const.STEP_REGISTER}">
<input type="hidden" name="form_submit" value="1">
<input type="hidden" name="skip" value="1">

	<div class="error">
		You should delete the 'install' directory now.
	</div>

	<p style="margin-left:20px;">
		<b>You are now running in testing mode.</b> This provides full functionality with no time limit for a single <abbr title="Seats are the maximum number of workers who can log in at the same time." style="border-bottom:1px dotted rgb(100,100,100);cursor:help;">seat</abbr>.
	</p>

	<p style="margin-left:20px;">
		Once you <b><a href="https://cerb.ai/pricing/site/" target="_blank" rel="noopener">purchase a license</a></b> you can install it from <b>Setup &raquo; Configure &raquo; License</b>.
	</p>

	<p style="margin-left:20px;">
		<a href="{devblocks_url}c=login{/devblocks_url}" style="font-size:150%;"><b>Log in and get started</b></a>
	</p>
</div>

</form>