{* begin time tracking *}
<style nonce="{DevblocksPlatform::getRequestNonce()}" type="text/css">
#divTimeTrackingBox {
	position:fixed;
	top:5px;
	right:5px;
	z-index:2;
	vertical-align:middle;
	text-align:center;
	font-size:16pt;
	padding:8px;
	
	color:var(--cerb-color-text);
	background-color:var(--cerb-color-background-contrast-200);
	
	box-shadow: 0 0 10px 0 var(--cerb-color-shadow-menu);
	
	border: solid 1px var(--cerb-color-background-contrast-180);
	border-radius:5px;
	-moz-border-radius:5px;
	-webkit-border-radius:5px;
}
#divTimeTrackingBox BUTTON {
	border:0;	
}
#divTimeTrackingCounter {
	font-size:16pt;
	font-weight:bold;
	margin-right:10px;
}
</style>
<div id="divTimeTrackingBox" style="display:{if isset($session.timetracking)}block{else}none{/if};">
	<div style="float:right;">
		<button id="btnTimeTrackingPlay" type="button" class="cerb-hidden"><span class="glyphicons glyphicons-play"></span></button>
		<button id="btnTimeTrackingPause" type="button" class="cerb-hidden"><span class="glyphicons glyphicons-pause"></span></button>
		<button id="btnTimeTrackingStop" type="button" class="cerb-hidden"><span class="glyphicons glyphicons-stop"></span></button>
	</div>
	<div style="float:left;">
		Time Spent: 
		<span id="divTimeTrackingCounter">--</span>
	</div>
</div>
<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
var timeTrackingTimerClass = function() {
	this.counter = 0;
	this.id = 0;
	this.enabled = false;
	this.timer = null;

	this.increment = function() {
		if(!this.enabled)
			return;

		++this.counter;
		this.redraw();	
	}

	this.redraw = function() {
		var $counterDiv = $('#divTimeTrackingCounter');
		
		if(0 === $counterDiv.length)
			return;
		
		var strTime = "";
		var iSecs = this.counter;
		var iMins = Math.floor(iSecs/60);
		var iHrs = Math.floor(iMins/60);
		iMins -= iHrs * 60;
		iSecs -= iHrs * 3600;
		iSecs -= iMins * 60;
		
		if(iHrs > 0) strTime = strTime + iHrs + "h ";
		if(iMins > 0) strTime = strTime + iMins + "m ";
		strTime = strTime + iSecs + "s ";
		
		$counterDiv.text(strTime);
	}

	this.show = function() {
		let $timerDiv = $('#divTimeTrackingBox').show();

		if(0 === $timerDiv.length)
			return;
		
		let $playBtn = $('#btnTimeTrackingPlay').on('click', function(e) {
			e.stopPropagation();
			timeTrackingTimer.play();
		});

		let $pauseBtn = $('#btnTimeTrackingPause').on('click', function(e) {
			e.stopPropagation();
			timeTrackingTimer.pause();
		});

		$('#btnTimeTrackingStop')
			.on('click', function(e) {
				e.stopPropagation();
				timeTrackingTimer.stop();
			})
			.show()
		;

		if(this.enabled) {
			$playBtn.hide();
			$pauseBtn.show();
			
		} else {
			$playBtn.show();
			$pauseBtn.hide();
		}
		
		this.redraw();
		
		if(this.timer)
			clearInterval(this.timer);
		
		var _self = this;
		this.timer = setInterval(function() {
			_self.increment();
		}, 1000);
	}
	
	this.play = function(id) {
		// Don't start twice
		if(this.enabled) {
			Devblocks.createAlertError("A timer is already running.");
			return;
		}
		
		if(!id) {
			id = this.id;
		// If it's running with a different id
		} else if (id && this.id && this.id != id) {
			Devblocks.createAlertError("Another timer is already running.");
			return;
		}
		
		var scope = this;

		var formData = new FormData();
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'time_tracking');
		formData.set('action', 'startTimer');
		formData.set('id', id);

		genericAjaxPost(formData, '', '', function(json) {
			scope.enabled = true;
			scope.show();
			
			if(json.hasOwnProperty('id'))
				scope.id = parseInt(json.id);
			
			if(json.hasOwnProperty('total_secs')) {
				scope.counter = parseInt(json.total_secs);
			} else if(json.hasOwnProperty('total_mins')) {
				scope.counter = parseInt(json.total_mins) * 60;
			}
				
			timeTrackingTimer.redraw();
		});
	}
	
	this.pause = function() {
		this.enabled = false;
		
		var formData = new FormData();
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'time_tracking');
		formData.set('action', 'pauseTimerJson');
		
		genericAjaxPost(formData, '', '');

		var $timerDiv = $('#divTimeTrackingBox').show();
		
		if(0 === $timerDiv.length)
			return;
		
		$('#btnTimeTrackingPlay').show();
		$('#btnTimeTrackingPause').hide();
		$('#btnTimeTrackingStop').show();
	}
	
	this.stop = function() {
		this.enabled = false;
		
		var $timerDiv = $('#divTimeTrackingBox').show();
		
		if(0 === $timerDiv.length)
			return;
		
		var $playBtn = $('#btnTimeTrackingPlay').hide();
		var $stopBtn = $('#btnTimeTrackingStop').hide();
		$('#btnTimeTrackingPause').hide();
		
		var scope = this;

		var formData = new FormData();
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'time_tracking');
		formData.set('action', 'pauseTimerJson');

		genericAjaxPost(formData, '', '', function(json) {
			if(json.hasOwnProperty('status') && json.hasOwnProperty('id') && json.hasOwnProperty('total_secs') && json.status) {
				var $popup = genericAjaxPopup('peek','c=internal&a=invoke&module=records&action=showPeekPopup&context={CerberusContexts::CONTEXT_TIMETRACKING}&context_id=' + parseInt(json.id) + '&secs=' + scope.counter + '&edit=true',null,false,'50%');
				$popup.one('dialogclose', function() {
					$playBtn.show();
					$stopBtn.show();
				});
			} else {
				scope.finish();
			}
		});
	}
	
	this.finish = function() {
		var $timerDiv = $('#divTimeTrackingBox').hide();
		
		if(0 === $timerDiv.length)
			return;
		
		this.enabled = false;
		this.counter = 0;
		this.id = 0;
		
		var formData = new FormData();
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'time_tracking');
		formData.set('action', 'clearEntry');
		
		genericAjaxPost(formData, '', '');
	}
};

var timeTrackingTimer = new timeTrackingTimerClass();

{if array_key_exists('timetracking_started', $session) && $current_timestamp} {* timer is running *}
	{* Recover the total from any pause/unpause segments *}
	timeTrackingTimer.counter = {if array_key_exists('timetracking_total', $session)}{$session.timetracking_total}{else}0{/if};
	{* Append the current runtime *}
	timeTrackingTimer.counter += {math equation="(x-y)" x=$current_timestamp y=$session.timetracking_started};
	timeTrackingTimer.enabled = true;
{elseif isset($session.timetracking_total)} {* timer is paused *}
	timeTrackingTimer.counter = {$session.timetracking_total|json_encode};
{else}
	timeTrackingTimer.counter = 0;
{/if}

{if array_key_exists('timetracking_id', $session)}
	timeTrackingTimer.id = {$session.timetracking_id|json_encode}; 
{/if}

{if array_key_exists('timetracking_total', $session) || array_key_exists('timetracking_started', $session)}
	timeTrackingTimer.show();
{/if} 
</script>
{* end time tracking *}
