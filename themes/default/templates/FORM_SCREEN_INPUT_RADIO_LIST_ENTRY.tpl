<p>
	<label for="j_{NAME|*}_{VALUE|*}"><input tabindex="{TABINDEX*}" class="input_radio" type="radio" id="j_{NAME|*}_{VALUE|*}" name="{NAME*}" value="{VALUE*}"{+START,IF,{CHECKED}} checked="checked"{+END} /> {TEXT}</label>
</p>

{+START,IF_NON_EMPTY,{DESCRIPTION}}
	<div class="associated-details radio-description">{DESCRIPTION*}</div>
{+END}
