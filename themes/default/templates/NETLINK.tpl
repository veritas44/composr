<form title="{!JUMP} ({!FORM_AUTO_SUBMITS})" method="get" action="{$FIND_SCRIPT*,netlink}" autocomplete="off">
	<div>
		<div>
			<p class="accessibility_hidden"><label for="netlink_url">{!JUMP}</label></p>
			<select data-change-submit-form="" id="netlink_url" name="url" class="wide-field">
				{CONTENT}
			</select>
		</div>
	</div>
</form>
