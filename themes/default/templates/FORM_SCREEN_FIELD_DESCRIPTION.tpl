{+START,IF,{$NOT,{$MATCH_KEY_MATCH,_WILD:quiz}}}
	{+START,IF_PASSED,DESCRIPTION}{+START,IF_NON_EMPTY,{DESCRIPTION}}
		{$REQUIRE_JAVASCRIPT,core_form_interfaces}
		<img data-tpl="formScreenFieldDescription" data-cms-rich-tooltip="{ haveLinks: true }" class="leave_native_tooltip help-icon{+START,IF_PASSED_AND_TRUE,LEFT} left{+END}{+START,IF_PASSED_AND_TRUE,RIGHT} right{+END}" title="{DESCRIPTION=}" alt="{$STRIP_HTML*,{DESCRIPTION}}" src="{$IMG*,icons/24x24/buttons/help}" srcset="{$IMG*,icons/48x48/buttons/help} 2x" />
	{+END}{+END}
{+END}

{+START,IF,{$MATCH_KEY_MATCH,_WILD:quiz}}
	{DESCRIPTION}
{+END}
