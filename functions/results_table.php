<?php
function make_results_table($result, $show_rowid, $table_name) {
	if($result === false) {
?>
		<div class="error_message">
			<?php
				if(oci_error()["code"]==null || oci_error()["message"]==null)
					echo "Your SQL statement probably ends with a semi-colon.";
				else
				 	echo oci_error()["code"] . ": " . oci_error()["message"];
			?>
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
				$fields_count = oci_num_fields($result);
				$rowid_index = -1;
				for($i=1; $i<=$fields_count; ++$i) {
					$field_name = oci_field_name($result, $i);
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

			function tr2($statement, $row, $index) {
				//if(oci_field_is_null($statement, $index+1)) return "<b>NULL</b>"; //YOU WAS SUPPOSED TO SAVE THE JEDI, NOT DESTROY THEM!
				if($row[$index] == "") return "&nbsp;";
				return htmlspecialchars($row[$index]);
			}

			$res = array();
			$current_rowid = null;

			while (($res = oci_fetch_array($result, OCI_BOTH + OCI_RETURN_NULLS))) {
				$current_rowid = treat_result($res[$rowid_index]);
				echo "<tr id='row_".$current_rowid."'>";
				$controls = true;
				for($i=0; $i<$fields_count; ++$i) {
					if($i == $rowid_index) continue;

					echo "<td>".tr2($result, $res, $i);
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