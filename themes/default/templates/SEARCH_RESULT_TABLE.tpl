<div class="wide-table-wrap"><table class="columned_table wide-table results-table autosized_table responsive-table">
	<thead>
		<tr>
			{+START,LOOP,HEADERS}
				<th>{_loop_var*}</th>
			{+END}
		</tr>
	</thead>

	<tbody>
		{+START,LOOP,ROWS}
			<tr>
				{+START,LOOP,R}
					<td>{_loop_var}</td>
				{+END}
			</tr>
		{+END}
	</tbody>
</table></div>
