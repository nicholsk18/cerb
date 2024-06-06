<fieldset>
	<legend class="cerb-menu">
		<a class="menu">{if isset($subtotal_fields.{$view->renderSubtotals})}{$subtotal_fields.{$view->renderSubtotals}->db_label|capitalize}{else}{'common.subtotals'|devblocks_translate|capitalize}{/if}</a> &#x25be;
	</legend>
	<ul class="cerb-popupmenu cerb-float" style="margin-top:-5px;">
		{foreach from=$subtotal_fields item=field_model key=field_key}
		<li><a data-cerb-worklist-subtotal-key="{$field_key}">{$field_model->db_label|capitalize}</a></li>
		{/foreach}
	</ul>

	<table cellspacing="0" cellpadding="2" border="0" width="100%">
	{foreach from=$subtotal_counts item=category}
		<tr>
			<td style="padding-right:10px;" nowrap="nowrap" valign="top">
				{if $category.filter.query}
					<a data-cerb-worklist-subtotal-query-add="{$category.filter.query}" data-cerb-worklist-subtotal-query-field="{$category.filter.field}" data-cerb-worklist-subtotal-query-replace>
					<span style="font-weight:bold;" title="{$category.label}">{$category.label|truncate:32}</span>
					</a>
				{elseif $category.filter.field}
					<a data-cerb-worklist-subtotal-filter-add="{$category.filter.field}" data-cerb-worklist-subtotal-filter-oper="{$category.filter.oper}" data-cerb-worklist-subtotal-filter-value="{if is_array($category.filter.values)}{DevblocksPlatform::services()->url()->arrayToQueryString($category.filter.values)}{/if}">
					<span style="font-weight:bold;" title="{$category.label}">{$category.label|truncate:32}</span>
					</a>
				{else}
					<span style="font-weight:bold;" title="{$category.label}">{$category.label|truncate:32}</span>
				{/if}
			</td>
			<td align="right" nowrap="nowrap" valign="top">
				<div class="badge">{$category.hits}</div>
			</td>
		</tr>
		{if isset($category.children) && !empty($category.children)}
		{foreach from=$category.children item=subcategory}
		<tr>
			<td style="padding-left:10px;padding-right:10px;" nowrap="nowrap" valign="top">
				{if $subcategory.filter.query}
					<a data-cerb-worklist-subtotal-query-add="{$subcategory.filter.query}" data-cerb-worklist-subtotal-query-field="{$subcategory.filter.field}">
					<span>{$subcategory.label|truncate:32}</span>
					</a>
				{elseif $subcategory.filter.field}
					<a data-cerb-worklist-subtotal-filter-add="{$subcategory.filter.field}" data-cerb-worklist-subtotal-filter-oper="{$subcategory.filter.oper}" data-cerb-worklist-subtotal-filter-value="{if is_array($subcategory.filter.values)}{DevblocksPlatform::services()->url()->arrayToQueryString($subcategory.filter.values)}{/if}">
					<span>{$subcategory.label|truncate:32}</span>
					</a>
				{else}
					<span>{$subcategory.label|truncate:32}</span>
				{/if}
			</td>
			<td align="right" nowrap="nowrap" valign="top">
				<div class="badge badge-lightgray">{$subcategory.hits}</div>
			</td>
		</tr>
		{/foreach}
		{/if}
	{/foreach}
	</table>
	
</fieldset>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $sidebar = $('#view{$view_id}_sidebar');
	let $legend = $sidebar.find('fieldset:first legend');

	$sidebar.find('[data-cerb-worklist-subtotal-key]').on('click', function(e) {
		e.stopPropagation();

		let field_key = $(this).attr('data-cerb-worklist-subtotal-key');

		$sidebar.fadeTo('normal', 0.2);

		genericAjaxGet('','c=internal&a=invoke&module=worklists&action=subtotal&category=' + encodeURIComponent(field_key) + '&view_id={$view_id}', function(html) {
			$sidebar
				.html(html)
				.fadeTo('normal',1.0)
				.find('FIELDSET:first TABLE:first TD:first A:first')
					.focus()
			;
		});
	});

	$sidebar.find('[data-cerb-worklist-subtotal-query-add]').on('click', function(e) {
		e.stopPropagation();
		let filter_query = $(this).attr('data-cerb-worklist-subtotal-query-add');
		let filter_field = $(this).attr('data-cerb-worklist-subtotal-query-field');
		let filter_replace = $(this).attr('data-cerb-worklist-subtotal-query-replace');
		ajax.viewAddQuery('{$view_id}', filter_query, filter_field, null !== filter_replace);
	});

	$sidebar.find('[data-cerb-worklist-subtotal-filter-add]').on('click', function(e) {
		e.stopPropagation();
		let filter_field = $(this).attr('data-cerb-worklist-subtotal-filter-add');
		let filter_oper = $(this).attr('data-cerb-worklist-subtotal-filter-oper');
		let filter_value = $(this).attr('data-cerb-worklist-subtotal-filter-value');

		let filter_values = new URLSearchParams(filter_value);
		let o = { };

		for(var pair of filter_values.entries()) {
			o[pair[0]] = pair[1];
		}

		ajax.viewAddFilter('{$view_id}', filter_field, filter_oper, o, filter_field);
	});

	$legend
		.hoverIntent({
			sensitivity:10,
			interval:300,
			over:function() {
				$(this).next('ul:first').show();
			},
			timeout:0,
			out:function(e) { }
		})
		.closest('fieldset')
			.hover(
				function(e) { },
				function() {
					$(this).find('ul:first').hide();
				}
			)
		.find('> ul.cerb-popupmenu > li')
			.click(function(e) {
				e.stopPropagation();
				if(!$(e.target).is('li'))
					return;
	
				$(this).find('a').trigger('click');
			})
		;
		
	$legend
		.closest('fieldset')
		.find('TBODY > TR')
		.css('cursor','pointer')
		.hover(
			function() {
				$(this).css('background-color','var(--cerb-color-background-contrast-240)');
			},
			function() {
				$(this).css('background','none');
			}
		)
		.click(function(e) {
			e.stopPropagation();
			
			if($(e.target).is('a')) {
				return;
			}
	
			$(this).find('a').trigger('click');
		})
		// Intercept link clicks so the TR doesn't handle them (but onclick does)
		.find('a')
		.click(function(e) {
			e.stopPropagation();
		})
		;
});
</script>