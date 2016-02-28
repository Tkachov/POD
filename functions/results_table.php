<?php
function make_results_table($result, $show_rowid, $table_name) {
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
				$rowid_index = -1;
				for($i=1; $i<=$fields_count; ++$i) {
					$field_name = odbc_field_name($result, $i);
					if($field_name == "ROWID" && !$show_rowid) {
						$rowid_index = $i-1;
						continue;
					}
					echo "<th>".$field_name."</th>";
				}
?>
			</tr>
<?php
			function treat_result($res) {
				if($res=='') return "&nbsp;";
				return htmlspecialchars($res);
			}

			$res = array();
			$current_rowid = null;
			while(odbc_fetch_into($result, $res)) {
				$current_rowid = treat_result($res[$rowid_index]);
				echo "<tr id='row_".$current_rowid."'>";
				$controls = true;
				for($i=0; $i<$fields_count; ++$i) {
					if($i == $rowid_index) continue;

					echo "<td>".treat_result($res[$i]);
					if($controls && $table_name != null) {
						$controls = false;
						echo "<div class='row_control'>";
						echo "<a class='button delete' href='javascript:delete_entry(\"". $current_rowid . "\");'></a>";
						echo "<a class='button edit' href='?tables&action=add_entry&target=" . $table_name . "&rowid=" . $current_rowid . "'></a>";
						echo "</div>";
					}
					echo "</td>";
				}

				echo "</tr>";
			}
?>
		</table>
<?php
	}
}
?>