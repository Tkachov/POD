<?php
function make_results_table($result) {
	if($result === false) {
?>
		<div class="error_message">
			<?php echo odbc_error().": ". odbc_errormsg(); ?>
		</div>
<?php
	} else {
?>
		<!-- TODO add edit/delete buttons for each row -->
		<!-- TODO field editing on click [?] -->
		<table class="results_table">
			<tr>
<?php
				$fields_count = odbc_num_fields($result);
				for($i=1; $i<=$fields_count; ++$i) {
					echo "<th>".odbc_field_name($result, $i)."</th>";
				}
?>
			</tr>
<?php
			while(odbc_fetch_row($result)) {
				echo "<tr>";
				for($i=1; $i<=$fields_count; ++$i) {
					echo "<td>".odbc_result($result, $i)."</td>";
				}
				echo "</tr>";
			}
?>
		</table>
<?php
	}
}
?>