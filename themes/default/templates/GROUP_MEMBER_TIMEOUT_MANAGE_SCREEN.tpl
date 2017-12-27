<div data-tpl="groupMemberTimeoutManageScreen">
	{TITLE}

	{$REQUIRE_JAVASCRIPT,ajax_people_lists}

	{+START,INCLUDE,HANDLE_CONFLICT_RESOLUTION}{+END}
	{+START,IF_PASSED,WARNING_DETAILS}
		{WARNING_DETAILS}
	{+END}

	<p>
		{!DOC_MANAGE_GROUP_MEMBER_TIMEOUTS}
	</p>

	<form title="{!PRIMARY_PAGE_FORM}" method="post" action="{URL*}" autocomplete="off">
		{$INSERT_SPAMMER_BLACKHOLE}

		<div class="wide-table-wrap"><table class="columned_table results-table wide-table autosized_table">
			<thead>
				<tr>
					<th>
						{!MEMBER}
					</th>
					<th>
						{!USERGROUP}
					</th>
					<th>
						{!GROUP_MEMBER_TIMEOUT_DATE}
					</th>
				</tr>
			</thead>
			<tbody>
				{+START,LOOP,TIMEOUTS}
					<tr>
						<td>
							<label class="accessibility_hidden" for="gmt_username_{_loop_key*}">{!USERNAME}</label>
							<input {+START,IF,{$MOBILE}} autocorrect="off"{+END} autocomplete="off" size="20" maxlength="255" class="input_username_required js-focus-update-ajax-member-list js-keyup-update-ajax-member-list" type="text" id="gmt_username_{_loop_key*}" name="gmt_username_{_loop_key*}" value="{USERNAME*}" />
						</td>

						<td>
							<label class="accessibility_hidden" for="gmt_group_id_{_loop_key*}">{!USERGROUP}</label>
							<input name="gmt_old_group_id_{_loop_key*}" value="{GROUP_ID*}" type="hidden" />
							<select id="gmt_group_id_{_loop_key*}" name="gmt_group_id_{_loop_key*}">
								{+START,LOOP,GROUPS}
									<option value="{_loop_key*}"{+START,IF,{$EQ,{GROUP_ID},{_loop_key}}} selected="selected"{+END}>{_loop_var*}</option>
								{+END}
							</select>
						</td>

						<td>
							{DATE_INPUT}
						</td>
					</tr>
				{+END}

				<tr>
					<td>
						<label class="accessibility_hidden" for="gmt_username_new">{!USERNAME}</label>
						<input {+START,IF,{$MOBILE}} autocorrect="off"{+END} autocomplete="off" size="20" maxlength="255" class="input_username_required js-focus-update-ajax-member-list js-keyup-update-ajax-member-list" type="text" id="gmt_username_new" name="gmt_username_new" />
					</td>

					<td>
						<label class="accessibility_hidden" for="gmt_group_id_new">{!USERGROUP}</label>
						<select id="gmt_group_id_new" name="gmt_group_id_new">
							<option value="">---</option>
							{+START,LOOP,GROUPS}
								<option value="{_loop_key*}">{_loop_var*}</option>
							{+END}
						</select>
					</td>

					<td>
						{DATE_INPUT}
					</td>
				</tr>
			</tbody>
		</table></div>

		<p class="proceed_button">
			<input accesskey="u" data-disable-on-click="1" class="button_screen buttons--save" type="submit" value="{!SAVE}" />
		</p>
	</form>
</div>
