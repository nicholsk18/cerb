<div id="tab{$tab->id}Config" style="margin-top:10px;">
	<fieldset class="peek">
		<legend>{'common.layout'|devblocks_translate|capitalize}</legend>
		
		<div style="margin:5px;display:inline-block;">
			<label>
				<input type="radio" name="params[layout]" value="" {if empty($tab->extension_params.layout)}checked="checked"{/if}>
				<svg width="100" height="80" style="vertical-align:middle;">
					<g style="fill:lightgray;stroke:gray;stroke-width:1">
						<rect x="1" y="1" width="98" height="78" />
					</g>
					<g style="fill:rgb(180,180,180);stroke:gray;stroke-width:1">
						<rect x="5" y="5" width="90" height="70" />
					</g>
				</svg>
			</label>
		</div>
		
		<div style="margin:5px;display:inline-block;">
			<label>
				<input type="radio" name="params[layout]" value="sidebar_left" {if 'sidebar_left' == $tab->extension_params.layout}checked="checked"{/if}>
				<svg width="100" height="80" style="vertical-align:middle;">
					<g style="fill:lightgray;stroke:gray;stroke-width:1">
						<rect x="1" y="1" width="98" height="78" />
					</g>
					<g style="fill:rgb(180,180,180);;stroke:gray;stroke-width:1">
						<rect x="5" y="5" width="30" height="70" />
						<rect x="40" y="5" width="55" height="70" />
					</g>
				</svg>
			</label>
		</div>
		
		<div style="margin:5px;display:inline-block;">
			<label>
				<input type="radio" name="params[layout]" value="sidebar_right" {if 'sidebar_right' == $tab->extension_params.layout}checked="checked"{/if}>
				<svg width="100" height="80" style="vertical-align:middle;">
					<g style="fill:lightgray;stroke:gray;stroke-width:1">
						<rect x="1" y="1" width="98" height="78" />
					</g>
					<g style="fill:rgb(180,180,180);;stroke:gray;stroke-width:1">
						<rect x="5" y="5" width="55" height="70" />
						<rect x="65" y="5" width="30" height="70" />
					</g>
				</svg>
			</label>
		</div>
		
	</fieldset>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $frm = $('#tab{$tab->id}Config');
	
	var $textarea = $frm.find('.placeholders');
	
	var $editor = $textarea
		.cerbCodeEditor()
		.nextAll('pre.ace_editor')
		;
});
</script>