{$guid = uniqid()}
{$worker_prefs = DAO_WorkerPref::getByWorker($active_worker->id)}
{$time_format = $worker_prefs.time_format|default:'D, d M Y h:i a'}
{if $time_format = 'D, d M Y h:i a'}{$hour_format = 'g'}{else}{$hour_format = 'H'}{/if}

<form id="frm{$guid}" action="#" style="margin-bottom:5px;width:98%;">
	<div style="float:left;">
		<span style="font-weight:bold;font-size:150%;">{$calendar_properties.calendar_date|devblocks_date:'F Y'}</span>
		
		<span style="margin-left:10px;">
			<ul class="bubbles">
				<li><a class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_CALENDAR}" data-context-id="{$calendar->id}">{$calendar->name}</a></li>
			</ul> 
		</span>
	</div>

	<div style="float:right;">
		<button type="button" data-cerb-calender-nav="prev"><span class="glyphicons glyphicons-chevron-left"></span></button>
		<button type="button" data-cerb-calender-nav="today">{{'common.today'|devblocks_translate|capitalize}}</button>
		<button type="button" data-cerb-calender-nav="next"><span class="glyphicons glyphicons-chevron-right"></span></button>
	</div>
	
	<br clear="all">
</form>

<table cellspacing="0" cellpadding="0" border="0" class="calendar">
<tr class="heading">
{if $calendar->params.start_on_mon}
	<th>{'common.day.monday.abbr'|devblocks_translate|capitalize}</th>
	<th>{'common.day.tuesday.abbr'|devblocks_translate|capitalize}</th>
	<th>{'common.day.wednesday.abbr'|devblocks_translate|capitalize}</th>
	<th>{'common.day.thursday.abbr'|devblocks_translate|capitalize}</th>
	<th>{'common.day.friday.abbr'|devblocks_translate|capitalize}</th>
	<th>{'common.day.saturday.abbr'|devblocks_translate|capitalize}</th>
	<th>{'common.day.sunday.abbr'|devblocks_translate|capitalize}</th>
{else}
	<th>{'common.day.sunday.abbr'|devblocks_translate|capitalize}</th>
	<th>{'common.day.monday.abbr'|devblocks_translate|capitalize}</th>
	<th>{'common.day.tuesday.abbr'|devblocks_translate|capitalize}</th>
	<th>{'common.day.wednesday.abbr'|devblocks_translate|capitalize}</th>
	<th>{'common.day.thursday.abbr'|devblocks_translate|capitalize}</th>
	<th>{'common.day.friday.abbr'|devblocks_translate|capitalize}</th>
	<th>{'common.day.saturday.abbr'|devblocks_translate|capitalize}</th>
{/if}
</tr>
{foreach from=$calendar_properties.calendar_weeks item=week name=weeks}
<tr class="week">
	{foreach from=$week item=day name=days}
		<td class="{if $calendar_properties.today == $day.timestamp}today{/if}{if $day.is_padding} inactive{/if}{if $smarty.foreach.days.last} cellborder_r{/if}{if $smarty.foreach.weeks.last} cellborder_b{/if}" style="position:relative;">
			<div class="day_header">
				{if $calendar->params.manual_disabled}
					{if $calendar_properties.today == $day.timestamp}
					<a>Today, {$calendar_properties.today|devblocks_date:"M d"}</a>
					{else}
					<a>{$day.dom}</a>
					{/if}
				{else}
					{if $calendar_properties.today == $day.timestamp}
					<a class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_CALENDAR_EVENT}" data-context-id="0" data-edit="calendar.id:{$calendar->id} start:{$day.timestamp}">Today, {$calendar_properties.today|devblocks_date:"M d"}</a>
					{else}
					<a class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_CALENDAR_EVENT}" data-context-id="0" data-edit="calendar.id:{$calendar->id} start:{$day.timestamp}">{$day.dom}</a>
					{/if}
				{/if}
			</div>
			<div class="day_contents">
				{if is_array($calendar_events) && array_key_exists($day.timestamp, $calendar_events)}
					{foreach from=$calendar_events.{$day.timestamp} item=event}
						<div class="event" style="background-color:{$event.color|default:'#C8C8C8'};" link="{$event.link}">
							<a class="cerb-peek-trigger" data-context="{$event.context|default:null}" data-context-id="{$event.context_id|default:0}">
							{$event.label}
							</a>
							
							{if !$calendar->params.hide_start_time}
								<span class="time">
								{if $event.ts_end-$event.ts == 86399}
								{elseif $event.ts == $event.ts_end}
								{else}
									{strip}
										{if $event.ts|devblocks_date:'i' == '00'}
											{$event.ts|devblocks_date:$hour_format}{if $hour_format=='g'}{$event.ts|devblocks_date:'a'|substr:0:1}{/if}
										{else}
											{$event.ts|devblocks_date:"{$hour_format}:i"}{if $hour_format=='g'}{$event.ts|devblocks_date:'a'|substr:0:1}{/if}
										{/if}
										-
										{if $event.ts_end|devblocks_date:'i' == '00'}
											{$event.ts_end|devblocks_date:$hour_format}{if $hour_format=='g'}{$event.ts_end|devblocks_date:'a'|substr:0:1}{/if}
										{else}
											{$event.ts_end|devblocks_date:"{$hour_format}:i"}{if $hour_format=='g'}{$event.ts_end|devblocks_date:'a'|substr:0:1}{/if}
										{/if}
									{/strip}
								{/if}
								</span>
							{/if}
						</div>
					{/foreach}
				{/if}
			</div>
		</td>
	{/foreach}
</tr>
{/foreach}
</table>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#frm{$guid}');
	let $tab = $frm.closest('div.cerb-workspace-widget--content');

	$frm.find('[data-cerb-calender-nav]').on('click', function(e) {
		e.stopPropagation();
		let action = $(this).attr('data-cerb-calender-nav');

		if('prev' === action) {
			genericAjaxGet($(this).closest('div.cerb-workspace-widget--content'), 'c=pages&a=invokeWidget&widget_id={$widget->id}&action=showCalendarTab&id={$calendar->id}&month={$calendar_properties.prev_month}&year={$calendar_properties.prev_year}');
		} else if('next' === action) {
			genericAjaxGet($(this).closest('div.cerb-workspace-widget--content'), 'c=pages&a=invokeWidget&widget_id={$widget->id}&action=showCalendarTab&id={$calendar->id}&month={$calendar_properties.next_month}&year={$calendar_properties.next_year}');
		} else {
			genericAjaxGet($(this).closest('div.cerb-workspace-widget--content'), 'c=pages&a=invokeWidget&widget_id={$widget->id}&action=showCalendarTab&id={$calendar->id}&month=&year=');
		}
	});

	$tab.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
		.on('cerb-peek-opened', function(e) {
		})
		.on('cerb-peek-saved cerb-peek-deleted', function(e) {
			var month = (e.month) ? e.month : '{$calendar_properties.month}';
			var year = (e.year) ? e.year : '{$calendar_properties.year}';
			
			genericAjaxGet($('#frm{$guid}').closest('div.cerb-workspace-widget--content'), 'c=pages&a=invokeWidget&widget_id={$widget->id}&action=showCalendarTab&id={$calendar->id}&month=' + month + '&year=' + year);
			e.stopPropagation();
		})
		;
});
</script>