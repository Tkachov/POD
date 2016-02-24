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
		<!-- TODO think about NULL -->
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
			function treat_result($res) {
				if($res=='') return "&nbsp;";
				return htmlspecialchars($res);
			}

			$res = array();
			while(odbc_fetch_into($result, $res)) {
				echo "<tr>";
				for($i=0; $i<$fields_count; ++$i) {
					echo "<td>".treat_result($res[$i])."</td>";
				}
				echo "</tr>";
			}
?>
		</table>
<?php
	}
}
?>