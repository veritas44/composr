<section class="box box___block_main_join_done"><div class="box_inner">
	<h3>{!_JOIN}</h3>

	{+START,IF_PASSED,MESSAGE}
		{MESSAGE}
	{+END}

	{+START,IF,{EMAIL_SENT}}
		<p>{!WHITEPAPER_EMAILED,{EMAIL_ADDRESS}}</p>
	{+END}

	{+START,IF,{LOGGED_IN}}
		{+START,IF,{$NOT,{EMAIL_SENT}}}
			{+START,IF,{HAS_EMAIL_TO_SEND}}
				{+START,IF_NON_EMPTY,{EMAIL_ADDRESS}}
					<form action="{$SELF_URL*}" method="post" onsubmit="var _this=this; ga_track(null,'{!DOWNLOAD_WHITEPAPER;}',null,function() { _this.submit(); }); return false;">
						{$INSERT_SPAMMER_BLACKHOLE}
						<input type="hidden" name="_send_document" value="1" />

						<p class="proceed_button">
							<input class="button_screen_item buttons__send" type="submit" value="{!DOWNLOAD_WHITEPAPER}" />
						</p>
					</form>
				{+END}

				{+START,IF_EMPTY,{EMAIL_ADDRESS}}
					<p>{!WHITEPAPER_NOT_EMAILED,{$PAGE_LINK*,site:members:view:redirect={$SELF_URL&}#tab__edit}}</p>
				{+END}
			{+END}

			{+START,IF,{$NOT,{HAS_EMAIL_TO_SEND}}}
				<p>{!LOGGED_IN_AS,{$USERNAME*}}</p>
			{+END}
		{+END}
	{+END}
</div></section>
