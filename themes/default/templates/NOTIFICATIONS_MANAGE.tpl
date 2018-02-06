<p>
	{$?,{$MATCH_KEY_MATCH,_SEARCH:admin_notifications},{!NOTIFICATIONS_DEFINE_LOCKDOWN},{!NOTIFICATIONS_INTRO}}
</p>

<div class="wide-table-wrap"><table class="columned-table wide-table results-table notifications-form responsive-table responsive-table-bolded-first-column">
	<colgroup>
		<col class="notifications-field-name-column" />
		{+START,IF_PASSED_AND_TRUE,SHOW_PRIVILEGES}
			<col class="notifications-privileges-column" />
		{+END}
		{+START,LOOP,NOTIFICATION_TYPES_TITLES}
			<col class="notifications-tick-column" />
		{+END}
		{+START,IF,{ADVANCED_COLUMN}}
			<col class="notifications-advanced-column" />
		{+END}
	</colgroup>

	<thead>
		<tr>
			<th></th>
			{+START,IF_PASSED_AND_TRUE,SHOW_PRIVILEGES}
				<th>
					{$SET,url,{$FIND_SCRIPT_NOHTTP,gd_text}?trans_color={COLOR}&text={$ESCAPE,{!NOTIFICATION_PRIVILEGED},UL_ESCAPED}{$KEEP}}
					<img class="gd-text" data-gd-text="{}" src="{$GET*,url}" title="{!NOTIFICATION_PRIVILEGED}" alt="{!NOTIFICATION_PRIVILEGED}" />
				</th>
			{+END}
			{+START,LOOP,NOTIFICATION_TYPES_TITLES}
				<th>
					{$SET,url,{$FIND_SCRIPT_NOHTTP,gd_text}?trans_color={COLOR}&text={$ESCAPE,{LABEL},UL_ESCAPED}{$KEEP}}
					<img class="gd-text" data-gd-text="{}" src="{$GET*,url}" title="" alt="{LABEL*}" />
				</th>
			{+END}
			{+START,IF,{ADVANCED_COLUMN}}
				<th></th>
			{+END}
		</tr>
	</thead>

	<tbody>
		{+START,LOOP,NOTIFICATION_SECTIONS}
			<tr class="form-table-field-spacer">
				<th class="responsive-table-no-prefix table-heading-cell" colspan="{+START,IF_PASSED_AND_TRUE,SHOW_PRIVILEGES}{$ADD*,{NOTIFICATION_TYPES_TITLES},3}{+END}{+START,IF_NON_PASSED_OR_FALSE,SHOW_PRIVILEGES}{$ADD*,{NOTIFICATION_TYPES_TITLES},2}{+END}">
					<span class="faux-h2">{NOTIFICATION_SECTION*}</span>
				</th>
			</tr>

			{+START,LOOP,NOTIFICATION_CODES}
				<tr class="notification-code {$CYCLE*,zebra,zebra-0,zebra-1}">
					<th class="de-th">{NOTIFICATION_LABEL*}</th>

					{+START,IF_PASSED,PRIVILEGED}
						<td>{$?,{PRIVILEGED},{!YES},{!NO}}</td>
					{+END}

					{+START,INCLUDE,NOTIFICATION_TYPES}{+END}

					{+START,IF,{ADVANCED_COLUMN}}
						{+START,SET,advanced_link}
							{+START,IF,{SUPPORTS_CATEGORIES}}
								<span class="associated-link"><a data-open-as-overlay="{'target': '_self'}" href="{$PAGE_LINK*,_SEARCH:notifications:advanced:notification_code={NOTIFICATION_CODE}{$?,{$NEQ,{MEMBER_ID},{$MEMBER}},:keep_su={$USERNAME&,{MEMBER_ID}}}}">{+START,IF,{$DESKTOP}}<span class="inline-desktop">{!ADVANCED}</span>{+END}<span class="inline-mobile">{!MORE}</span></a></span>
							{+END}
						{+END}
					{+END}
					<td class="associated-details">{$TRIM,{$GET,advanced_link}}</td>
				</tr>
			{+END}
		{+END}
	</tbody>
</table></div>

{+START,IF_PASSED,AUTO_NOTIFICATION_CONTRIB_CONTENT}
	<h2>{!cns:AUTO_NOTIFICATION_CONTRIB_CONTENT}</h2>

	<p class="simple-neat-checkbox">
		<input {+START,IF,{AUTO_NOTIFICATION_CONTRIB_CONTENT}} checked="checked"{+END} type="checkbox" id="auto_monitor_contrib_content" name="auto_monitor_contrib_content" value="1" />
		<label for="auto_monitor_contrib_content"><span>{!cns:DESCRIPTION_AUTO_NOTIFICATION_CONTRIB_CONTENT}</span></label>
	</p>
{+END}

{+START,IF_PASSED,SMART_TOPIC_NOTIFICATION_CONTENT}
	<h2>{!cns:SMART_TOPIC_NOTIFICATION}</h2>

	<p class="simple-neat-checkbox">
		<input {+START,IF,{SMART_TOPIC_NOTIFICATION_CONTENT}} checked="checked"{+END} type="checkbox" id="smart_topic_notification_content" name="smart_topic_notification_content" value="1" />
		<label for="smart_topic_notification_content"><span>{!cns:DESCRIPTION_SMART_TOPIC_NOTIFICATION}</span></label>
	</p>
{+END}
