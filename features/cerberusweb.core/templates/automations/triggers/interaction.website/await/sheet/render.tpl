{$is_selection_enabled = false}

<div class="cerb-shadow">
	<div>
		<table cellpadding="0" cellspacing="0" class="cerb-sheet">
		{if $rows}
			{if $layout.headings}
			<thead>
				<tr>
					{foreach from=$columns item=column name=columns}
					{if $layout.title_column == $column.key}
					{elseif $column._type == 'selection'}
						<th class="cerb-sheet--row-selection"></th>
					{else}
					<th data-column-key="{$column.key}" data-column-type="{$column._type}">{$column.label}</th>
					{/if}
					{/foreach}
				</tr>
			</thead>
			{/if}
	
			{foreach from=$rows item=row name=rows}
				<tbody class="cerb-sheet--row">
					{foreach from=$columns item=column name=columns}
						{if $column._type == 'selection'}
						{$is_selection_enabled = true}
						<tr>
							<td class="cerb-sheet--row-selection" rowspan="{if $layout.title_column}3{else}2{/if}" colspan="1">
								{$row[$column.key]|replace:'${SHEET_SELECTION_KEY}':{$sheet_selection_key|default:'_selection'} nofilter}
							</td>
						</tr>
						{/if}
					{/foreach}
	
					{if $layout.title_column}
					{$column = $columns[$layout.title_column]}
					<tr>
						<td colspan="{$columns|count-1}" class="cerb-sheet--row-title">{$row[$column.key] nofilter}</td>
					</tr>
					{/if}
	
					<tr>
					{foreach from=$columns item=column name=columns}
						{if $layout.title_column == $column.key}
						{elseif $column._type == 'selection'}
						{else}
						<td class="{if $column.params.bold}cerb-font-bold{/if}">{$row[$column.key] nofilter}</td>
						{/if}
					{/foreach}
					</tr>
				</tbody>
			{/foreach}
		{else}
			<tr>
				<td>
					({'common.data.no'|devblocks_translate|lower})
				</td>
			</tr>
		{/if}
		</table>
	
		{if $paging && $paging.page.of > 1}
		<div class="cerb-sheet-paging">
			{if array_key_exists('first', $paging.page)}<span class="cerb-paging" data-page="{$paging.page.first}">&lt;&lt;</span>{/if}
			{if array_key_exists('prev', $paging.page)}<span class="cerb-paging" data-page="{$paging.page.prev}">&lt;{'common.previous_short'|devblocks_translate|capitalize}</span>{/if}
			(Showing {if $paging.page.rows.from==$paging.page.rows.to}{$paging.page.rows.from}{else}{$paging.page.rows.from}-{$paging.page.rows.to}{/if}
			 of {$paging.page.rows.of})
			{if array_key_exists('next', $paging.page)}<span class="cerb-paging" data-page="{$paging.page.next}">{'common.next'|devblocks_translate|capitalize}&gt;</span>{/if}
			{if array_key_exists('last', $paging.page)}<span class="cerb-paging" data-page="{$paging.page.last}">&gt;&gt;</span>{/if}
		</div>
		{/if}
	</div>
</div>

{$script_uid = uniqid('script')}
<script type="text/javascript" id="{$script_uid}" nonce="{$session->nonce}">
{
	let $script = document.querySelector('#{$script_uid}');
	let $sheet = $script.closest('[data-cerb-sheet-container]');
	let $prompt = $sheet.closest('.cerb-interaction-popup--form-elements-sheet');
	
	$$.disableSelection($prompt);
	
	$prompt.addEventListener('click', function(e) {
		e.stopPropagation();
		
		let $target = e.target;
		
		if($target.hasAttribute('data-page')) {
			let page = $target.getAttribute('data-page');

			let evt = $$.createEvent('cerb-sheet--page-changed', { "page": page });
			$prompt.dispatchEvent(evt);
			
		} else {
			{if $is_selection_enabled}
			let $tbody = $target.closest('tbody');
			
			if(!$tbody)
				return;
			
			if('a' === e.target.nodeName.toLowerCase())
				return;

			// If removing selected, add back hover

			let $checkbox = $tbody.querySelector('input[type=radio], input[type=checkbox]');

			// If our target was something other than the input toggle
			if($checkbox !== e.target) {
				$checkbox.checked = !$checkbox.checked;
			}

			let is_multiple = 'checkbox' === $checkbox.attributes.type.value.toLowerCase();

			$sheet.dispatchEvent(
				$$.createEvent(
					'cerb-sheet--selection',
					{
						ui: {
							item: $checkbox
						},
						is_multiple: is_multiple,
						selected: $checkbox.checked
					}
				)
			);

			let row_selections = [];

			let $checkboxes = $tbody.closest('table.cerb-sheet').querySelectorAll('input[type=radio]:checked ,input[type=checkbox]:checked');

			$$.forEach($checkboxes, function(index, $e) {
				row_selections.push($e.value);
			});

			$sheet.dispatchEvent(
				$$.createEvent('cerb-sheet--selections-changed', { row_selections: row_selections, is_multiple: is_multiple })
			);
			{/if}
		}
	});
}
</script>