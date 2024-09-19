{$msg_id = uniqid()}
<div class="bot-chat-object" data-delay-ms="{$delay_ms|default:0}" data-typing-indicator="true" id="{$msg_id}">
	<div class="bot-chat-message bot-chat-right">
		<div class="bot-chat-message-bubble">
			{foreach from=$labels item=label key=idx}
			{$image = $images.$idx}
			{if $label}
			<button type="button" class="bot-chat-button-image" style="" value="{$label}" title="{$label}"><img src="{$image}"></button>
			{/if}
			{/foreach}
		</div>
	</div>
	
	<br clear="all">
	
	<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
	$(function() {
		var $msg = $('#{$msg_id}');
		
		var $chat_window_input_form = $('#{$layer} form.bot-chat-window-input-form');
		var $chat_input = $chat_window_input_form.find('textarea[name=message]');
		
		$msg.find('button.bot-chat-button-image')
			.click(function() {
				var $button = $(this);
				
				var txt = $button.val();
				
				$chat_input.val(txt);
				$chat_window_input_form.submit();
				$msg.remove();
			})
			.first()
			.focus()
		;
	})
	</script>
	</script>
</div>
