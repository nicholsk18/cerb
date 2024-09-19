{$msg_id = uniqid()}
<div class="bot-chat-object" data-delay-ms="{$delay_ms|default:0}" id="{$msg_id}" style="text-align:center;">
	<input type="text" class="bot-chat-input" placeholder="{$placeholder}" autocomplete="off" autofocus="autofocus">
	
	<div>
		<button type="button" class="bot-chat-button send">{'common.send'|devblocks_translate|capitalize}</button>
	</div>

	<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
	$(function() {
		var $msg = $('#{$msg_id}');
		
		var $chat_window_input_form = $('#{$layer} form.bot-chat-window-input-form');
		var $chat_input = $chat_window_input_form.find('textarea[name=message]');
		var $button_send = $msg.find('button.send').hide();
		
		var $txt = $msg.find('input:text')
			.blur()
			.focus()
			.on('keyup', function(e) {
				var keycode = e.keyCode || e.which;
				if(13 != keycode) {
					$button_send.hide();
					return;
				}
			})
			.cerbDateInputHelper()
			.on('cerb-date-changed', function(e) {
				$button_send.fadeIn().focus();
			})
		;
		
		$button_send
			.on('click', function(e) {
				$chat_input.val($txt.val());
				$chat_window_input_form.submit();
				$msg.remove();
			})
		;
	});
	</script>
</div>

