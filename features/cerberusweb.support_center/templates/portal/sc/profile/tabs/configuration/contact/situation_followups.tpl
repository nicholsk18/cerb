<div class="drag">
<span class="ui-icon ui-icon-arrowthick-2-n-s" style="display:inline-block;vertical-align:middle;cursor:move;"></span>
<input type="text" name="contact_followup[{$uniq_id}][]" size="45" value="{$q}"> 
<select name="contact_followup_fields[{$uniq_id}][]">
	<option value="">-- {'portal.sc.cfg.append_to_message'|devblocks_translate} --</option>
	<optgroup label="{'common.custom_fields'|devblocks_translate}">	
		{foreach from=$ticket_fields item=f key=f_id}
		{$field_type = $f->type}
		<option value="{$f_id}" {if $f_id==$field_id}selected{/if}>
			{$f->name}
			{if isset($field_types.$field_type)}({$field_types.$field_type}){/if}
		</option>
		{/foreach}
	</optgroup>
</select>
<button type="button" data-cerb-button-remove><span class="glyphicons glyphicons-circle-minus"></span></button>
</div>
