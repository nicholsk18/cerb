<form action="javascript:;" method="post" id="frmAvatarEditor" onsubmit="return false;">
	<table width="100%" cellpadding="0" cellspacing="5">
		<tr>
			<td width="1%" valign="top" nowrap="nowrap">
				<div style="margin:0;padding:0;border:1px solid var(--cerb-color-background-contrast-230);display:inline-block;">
					<canvas class="canvas-avatar" width="{$image_width}" height="{$image_height}" style="max-width:100px;height:auto;cursor:move;"></canvas>
				</div>
				<div style="margin-top:5px;">
					<input type="text" name="bgcolor" value="#ffffff" size="8" class="color-picker" spellcheck="false">
				</div>
				<input type="hidden" name="imagedata" class="canvas-avatar-imagedata">
			</td>

			<td width="99%" valign="top">
				<fieldset class="peek">
					<legend>Editor tools:</legend>

					<button type="button" class="canvas-avatar-zoomin" title="{'common.zoom.in'|devblocks_translate|capitalize}"><span class="glyphicons glyphicons-zoom-in"></span></button>
					<button type="button" class="canvas-avatar-zoomout" title="{'common.zoom.out'|devblocks_translate|capitalize}"><span class="glyphicons glyphicons-zoom-out"></span></button>
					<button type="button" class="canvas-avatar-remove" title="{'common.clear'|devblocks_translate|capitalize}"><span class="glyphicons glyphicons-erase"></span></button>
				</fieldset>

				<fieldset class="peek">
					<legend>Image generation:</legend>

					{if $avatar_toolbar}
					<span data-cerb-avatar-toolbar>
						{DevblocksPlatform::services()->ui()->toolbar()->render($avatar_toolbar)}
					</span>
					{/if}
				</fieldset>
			</td>
		</tr>
	</table>

	<div style="margin-top:10px;">
		{include file="devblocks:cerberusweb.core::ui/spinner.tpl" hidden=true}
		<button type="button" class="canvas-avatar-export" title="{'common.save_changes'|devblocks_translate|capitalize}"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	</div>
	
	<div class="cerb-avatar-error"></div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFind($('#frmAvatarEditor'));
	
	$popup.one('popup_open', function() {
		$popup.dialog('option','title',"Profile Picture Editor");
		$popup.css('overflow', 'inherit');
		
		var $canvas = $popup.find('canvas.canvas-avatar');
		var canvas = $canvas.get(0);
		var context = canvas.getContext('2d');
		var $export = $popup.find('button.canvas-avatar-export');
		var $error = $popup.find('div.cerb-avatar-error');
		var $spinner = $popup.find('svg.cerb-spinner');
		var $bgcolor_well = $popup.find('input.color-picker');

		$bgcolor_well.minicolors({
			swatches: ['#CF2C1D','#FEAF03','#57970A','#007CBD','#7047BA','#CF25F5','#ADADAD','#34434E', '#FFFFFF'],
			opacity: true,
			change: function() {
				$canvas.trigger('avatar-redraw');
			}
		});
		
		var isMouseDown = false;
		var x = 0, lastX = 0;
		var y = 0, lastY = 0;
	
		var scale = 1.0;
		context.scale(scale,scale);
		
		var img = new Image();
		{if $imagedata}
			$(img).one('load', function() {
				$canvas.trigger('avatar-redraw');
			});
		
			img.src = "{$imagedata}";
		{/if}
		
		$canvas.mousedown(function (event) {
			isMouseDown = true;
			lastX = event.offsetX;
			lastY = event.offsetY;
		});
		
		$canvas.mouseup(function() {
			isMouseDown = false;
		});
		
		$canvas.mouseout(function() {
			isMouseDown = false;
		});
		
		$canvas.mousemove(function(event) {
			if(isMouseDown) {
				x = x - (lastX - event.offsetX);
				y = y - (lastY - event.offsetY);
				
				$canvas.trigger('avatar-redraw');
				
				lastX = event.offsetX;
				lastY = event.offsetY;
			}
		});
		
		$canvas.on('avatar-redraw', function() {
			var bgcolor = $bgcolor_well.minicolors('rgbaString');
			
			context.save();
			
			context.scale(scale, scale);
			context.clearRect(0, 0, canvas.width, canvas.height);
			
			context.fillStyle = bgcolor;
			context.fillRect(0, 0, canvas.width, canvas.height);
			
			var aspect = img.height/img.width;
			context.drawImage(img, x, y, canvas.width, canvas.width*aspect);
			
			context.restore();
		});

		$export.click(function() {
			var evt = new jQuery.Event('avatar-editor-save');
			
			evt.avatar = {
				'imagedata': canvas.toDataURL()
			};
			
			if(0 === $(img).attr('src').length) {
				evt.avatar.empty = true;
			}
			
			$popup.trigger(evt);
		});
		
		$popup.find('button.canvas-avatar-zoomout').click(function() {
			scale = Math.max(scale-0.10, 1.0);
			$canvas.trigger('avatar-redraw');
		});
		
		$popup.find('button.canvas-avatar-zoomin').click(function() {
			scale = Math.min(scale+0.10, 10.0);
			$canvas.trigger('avatar-redraw');
		});
		
		$popup.find('button.canvas-avatar-remove').click(function() {
			scale = 1.0;
			x = 0;
			y = 0;
			$bgcolor_well.minicolors('value', { color: '#ffffff', opacity:0 });
			$(img).attr('src', '');
			$canvas.trigger('avatar-redraw');
		});

		$canvas.on('cerb-avatar-from-text', function(e) {
			if(!e.hasOwnProperty('text'))
				return;

			let bgcolor = $bgcolor_well.val();

			if('#ffffff' === bgcolor) {
				bgcolor = '#1e5271';
				$bgcolor_well.minicolors('value', { color: bgcolor, opacity:0 });
			}

			let txt = e.text;

			scale = 1.0;
			x = 0;
			y = 0;

			let $new_canvas = $('<canvas height="{$image_height}" width="{$image_width}"/>');
			let new_canvas = $new_canvas.get(0);
			let new_context = new_canvas.getContext('2d');
			new_context.clearRect(0, 0, new_canvas.width, new_canvas.height);

			let height = 180;
			let bounds = { width: {$image_width} };
			while(bounds.width > 244) {
				height = height - 12;
				new_context.font = "Bold " + height + "pt Arial";
				bounds = new_context.measureText(txt);
			}

			new_context.fillStyle = '#FFFFFF';
			new_context.fillText(txt,(new_canvas.width-bounds.width)/2,height+(new_canvas.height-height)/2);

			$(img).one('load', function() {
				$new_canvas.remove();
				$canvas.trigger('avatar-redraw');
			});

			$(img).attr('src', new_canvas.toDataURL());
		});

		$canvas.on('cerb-avatar-from-url', function(e) {
			if(!e.hasOwnProperty('url'))
				return;

			let url = encodeURIComponent(e.url);

			$error.html('').hide();

			$spinner.show();

			genericAjaxGet('', 'c=avatars&a=_fetch&url=' + url, function(json) {
				if(undefined === json.status || !json.status) {
					Devblocks.showError($error, json.error);
					$spinner.hide();
					return;
				}

				if(!json.hasOwnProperty('imageData'))
					return;

				if(undefined === json.imageData) {
					Devblocks.showError($error, "No image data was available at the given URL.");
					$spinner.hide();
					return;
				}

				scale = 1.0;
				x = 0;
				y = 0;
				$(img).one('load', function() {
					$bgcolor_well.minicolors('value', { color: '#ffffff', opacity:1 });
					$canvas.trigger('avatar-redraw');
				});
				$(img).attr('src', json.imageData);
				$spinner.hide();
			});
		});

		$popup.on('cerb-avatar-set-defaults', function(e) {
			if(!e.hasOwnProperty('avatar'))
				return;
			
			if(e.avatar.hasOwnProperty('imagedata')) {
				scale = 1.0;
				x = 0;
				y = 0;
				$(img).one('load', function() {
					$canvas.trigger('avatar-redraw');
				});
				$(img).attr('src', e.avatar.imagedata);
			}
			
			if(e.avatar.hasOwnProperty('imageurl')) {
				$canvas.trigger(
					$.Event('cerb-avatar-from-url', { 'url': e.avatar.imageurl })
				);
			}
		});

		$popup.find('[data-cerb-avatar-toolbar]').cerbToolbar({
			caller: {
				name: 'cerb.toolbar.record.profile.image.editor',
				params: {
					"record__context": "{$context}",
					"record_id": "{$context_id}",
					"image_width": "{$image_width}",
					"image_height": "{$image_height}",
				}
			},
			start: function (formData) {
			},
			done: function (e) {
				e.stopPropagation();

				if (e.eventData.exit === 'error') {

				} else if (e.eventData.exit === 'return') {
					Devblocks.interactionWorkerPostActions(e.eventData);

					if(
						e.eventData.hasOwnProperty('return')
						&& e.eventData.return.hasOwnProperty('image')
						&& 'object' === typeof e.eventData.return.image
					) {
						if(e.eventData.return.image.hasOwnProperty('url')) {
							$canvas.trigger(
								$.Event('cerb-avatar-from-url', { 'url': e.eventData.return.image.url })
							);
						} else if(e.eventData.return.image.hasOwnProperty('text')) {
							$canvas.trigger(
								$.Event('cerb-avatar-from-text', { 'text': e.eventData.return.image.text })
							);
						}
					}
				}
			}
		});
	});
});
</script>