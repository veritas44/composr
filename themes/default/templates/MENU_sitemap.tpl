{$REQUIRE_CSS,menu__sitemap}
{$REQUIRE_JAVASCRIPT,core_menus}
{$REQUIRE_JAVASCRIPT,menu_sitemap}

{$SET,js_menu,{$AND,{$NOT,{$BROWSER_MATCHES,bot}},{$JS_ON}}}

{+START,IF,{$NOT,{$GET,js_menu}}}
	<nav class="menu_type__sitemap">
		<ul itemprop="significantLinks">
			{CONTENT}
		</ul>
	</nav>
{+END}

{+START,IF,{$GET,js_menu}}
	{$SET,menu_sitemap_id,menu_sitemap_{$RAND}}

	<nav id="{$GET*,menu_sitemap_id}" class="menu_type__sitemap">
		<div aria-busy="true" class="spaced">
			<div class="ajax_loading vertical_alignment">
				<img src="{$IMG*,loading}" title="{!LOADING}" alt="{!LOADING}" />
				<span>{!LOADING}</span>
			</div>
		</div>
	</nav>
{+END}

<script type="application/json" data-tpl-core-menus="menuSitemap">{+START,PARAMS_JSON,menu_sitemap_id}[{_/}, {CONTENT/}]{+END}</script>