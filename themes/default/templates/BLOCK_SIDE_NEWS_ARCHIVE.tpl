{$REQUIRE_CSS,news}
{$REQUIRE_JAVASCRIPT,core_rich_media}

{$,Try and set to year of currently viewed item or otherwise the current year}
{$SET,news_archive_year,{$?,{$IS_EMPTY,{$_GET,year}},{$?,{$IS_EMPTY,{$METADATA,created}},{$FROM_TIMESTAMP,Y},{$PREG_REPLACE,-.*$,,{$METADATA,created}}},{$_GET,year}}}

<section class="box box___block_side_news_archive" data-toggleable-tray>
	<div class="box_inner">
		<h3>{TITLE*}</h3>

		<ul class="compact_list">
			{+START,LOOP,YEARS}
				{$SET,is_current_year,{$EQ,{YEAR},{$GET,news_archive_year}}}

				{+START,IF_NON_EMPTY,{TIMES}}
					<li class="accordion_trayitem js-tray-accordion-item">
						<a class="toggleable_tray_button js-btn-tray-accordion" href="#!"><img {+START,IF,{$NOT,{$GET,is_current_year}}} alt="{!EXPAND}: {$STRIP_TAGS,{TITLE}}" title="{!EXPAND}" src="{$IMG*,1x/trays/expand}" srcset="{$IMG*,2x/trays/expand} 2x"{+END}{+START,IF,{$GET,is_current_year}} alt="{!CONTRACT}: {$STRIP_TAGS,{TITLE}}" title="{!CONTRACT}" src="{$IMG*,1x/trays/contract}" srcset="{$IMG*,1x/trays/contract} 2x"{+END} /></a>

						<span class="js-btn-tray-accordion"><strong>{YEAR}</strong></span>:

						<div class="toggleable_tray accordion_trayitem js-tray-accordion-item-body"{+START,IF,{$NOT,{$GET,is_current_year}}} style="display: none" aria-expanded="false"{+END}>
							<ul class="compact_list associated_details">
								{+START,LOOP,TIMES}
									<li>
										<a href="{URL*}">{MONTH_STRING}</a>
									</li>
								{+END}
							</ul>
						</div>
					</li>
				{+END}
			{+END}
		</ul>
	</div>
</section>
