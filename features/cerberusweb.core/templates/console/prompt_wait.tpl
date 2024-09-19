<div class="bot-chat-object" data-delay-ms="{$delay_ms|default:0}">
	<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
	$(function() {
		var $chat_window_convo = $('#{$layer} div.bot-chat-window-convo');
		var $chat_window_input_form = $('#{$layer} form.bot-chat-window-input-form');
		var $chat_input = $chat_window_input_form.find('textarea[name=message]');
		
		var cb = function() {
			$chat_input.val('');
			$chat_window_convo.trigger('bot-chat-message-send');
		}
		
		setTimeout(cb, 500);
	});
	</script>
</div>
