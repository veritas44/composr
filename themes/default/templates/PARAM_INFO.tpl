<div class="wide-table-wrap">
	<table class="columned_table wide-table results-table autosized_table">
		<thead>
			<tr>
				<th>{!NAME}</th>
				<th>{!CURRENT}</th>
			</tr>
		</thead>

		<tbody>
			{+START,LOOP,MAP}
				<tr>
					<th>
						{{_loop_key*}}
					</th>
					<td>
						{+START,IF_PASSED,_loop_var} {$,Is not NULL}
							<div class="whitespace_visible">{$PREG_REPLACE,\s*$,,{$PREG_REPLACE,^\s*,,{_loop_var*}}}</div>
						{+END}
					</td>
				</tr>
			{+END}
		</tbody>
	</table>
</div>
