{$REQUIRE_JAVASCRIPT,cns_forum}

<a id="poll_jump" rel="dovote"></a>
<form class="cns-topic-poll-form" title="{!VOTE}" action="{VOTE_URL*}" method="post" data-tpl="cnsTopicPoll" data-tpl-params="{+START,PARAMS_JSON,MINIMUM_SELECTIONS,MAXIMUM_SELECTIONS}{_*}{+END}" autocomplete="off">
	{$INSERT_SPAMMER_BLACKHOLE}

	<h3>{+START,FRACTIONAL_EDITABLE,{QUESTION},question,_SEARCH:topics:_edit_poll:{ID}}{QUESTION*}{+END}</h3>

	<div class="wide-table-wrap">
		<table class="spread_table autosized_table cns-topic-poll wide-table">
			<tbody>
				{ANSWERS}
			</tbody>
		</table>

		{+START,IF_NON_EMPTY,{BUTTON}}
			<div class="cns-poll-button">
				{BUTTON}
			</div>
		{+END}

		{+START,IF_NON_EMPTY,{PRIVATE}{NUM_CHOICES}}
			<div class="cns-poll-meta cns-column6">
				{PRIVATE}
				{NUM_CHOICES}
			</div>
		{+END}
	</div>
</form>
