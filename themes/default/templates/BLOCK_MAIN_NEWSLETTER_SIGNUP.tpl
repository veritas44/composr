{$REQUIRE_JAVASCRIPT,newsletter}

{+START,IF_PASSED,MSG}
	<p>{MSG}</p>
{+END}

<section class="box box___block_main_newsletter_signup" data-require-javascript="newsletter" data-tpl="blockMainNewsletterSignup" data-tpl-params="{+START,PARAMS_JSON,NID}{_*}{+END}"><div class="box_inner">
	<h3>{!NEWSLETTER}{$?,{$NEQ,{NEWSLETTER_TITLE},{!GENERAL}},: {NEWSLETTER_TITLE*}}</h3>

	<form class="js-form-submit-newsletter-check-email-field" title="{!NEWSLETTER}" action="{URL*}" method="post" autocomplete="off">
		{$INSERT_SPAMMER_BLACKHOLE}

		<p class="accessibility_hidden"><label for="baddress">{!EMAIL_ADDRESS}</label></p>

		<div class="constrain_field">
			<input class="wide_field" id="baddress" name="address{NID*}" placeholder="{!EMAIL_ADDRESS}" />
		</div>

		<p class="proceed_button">
			<input class="button_screen_item menu__site_meta__newsletters" type="submit" value="{!SUBSCRIBE}" />
		</p>
	</form>
</div></section>
