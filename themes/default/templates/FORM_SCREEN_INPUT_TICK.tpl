{$REQUIRE_JAVASCRIPT,core_form_interfaces}
{+START,IF,{$EQ,{NAME},validated}}<span class="validated-checkbox{+START,IF,{CHECKED}} checked{+END}"></span>{+END}
<input type="checkbox" {+START,IF,{READ_ONLY}} data-click-pd="1"{+END} class="input_tick" id="{NAME*}" name="{NAME*}" data-tpl="formScreenInputTick" data-tpl-params="{+START,PARAMS_JSON,NAME}{_*}{+END}" tabindex="{TABINDEX*}" value="{+START,IF_PASSED,VALUE}{VALUE*}{+END}{+START,IF_NON_PASSED,VALUE}1{+END}"{+START,IF,{CHECKED}} checked="checked"{+END}{+START,IF,{DISABLED}} disabled="disabled"{+END} />
<input name="tick_on_form__{NAME*}" value="0" type="hidden" />
