<div class="side-calendar-wrap">
	<div class="wide-table-wrap-internal-borders"><table class="spread_table autosized_table wide-table side-calendar calendar-year-month-table">
		<colgroup>
			<col class="calendar-weekly-column" />
			<col class="calendar-weekly-column" />
			<col class="calendar-weekly-column" />
			<col class="calendar-weekly-column" />
			<col class="calendar-weekly-column" />
			<col class="calendar-weekly-column" />
			<col class="calendar-weekly-column" />
		</colgroup>
		<thead>
			<tr>
				{+START,IF,{$SSW}}
				<th title="{!SUNDAY}">{!FC_SUNDAY}</th>
				{+END}
				<th title="{!MONDAY}">{!FC_MONDAY}</th>
				<th title="{!TUESDAY}">{!FC_TUESDAY}</th>
				<th title="{!WEDNESDAY}">{!FC_WEDNESDAY}</th>
				<th title="{!THURSDAY}">{!FC_THURSDAY}</th>
				<th title="{!FRIDAY}">{!FC_FRIDAY}</th>
				<th title="{!SATURDAY}">{!FC_SATURDAY}</th>
				{+START,IF,{$NOT,{$SSW}}}
				<th title="{!SUNDAY}">{!FC_SUNDAY}</th>
				{+END}
			</tr>
		</thead>

		<tbody>
			{ENTRIES}
		</tbody>
	</table></div>
</div>
