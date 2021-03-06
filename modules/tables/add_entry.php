<?php
	$query = "SELECT column_name, data_type, data_length, data_precision, data_scale FROM ALL_TAB_COLUMNS WHERE table_name = '".strtoupper($target)."';";
	$result = odbc_exec($client->get_connection(), $query);
?>
<div id="save_message" style="display: none;"></div>
<table class="results_table">
	<tr>
		<?php
			$fields_count = 0;
			$types = array();
			while(odbc_fetch_row($result)) {
				$fields_count += 1;
				echo "<th>".odbc_result($result, 1)."</th>";
				if(odbc_result($result, 2) == "NUMBER") {
					$types[] = odbc_result($result, 2)."(".odbc_result($result, 4).",".odbc_result($result, 5).")"; //use precision + scale instead
				} else {
					$types[] = odbc_result($result, 2)."(".odbc_result($result, 3).")";
				}
			}
		?>
	</tr>
	<?php
		echo "<form id='entry_form'>";
		echo "<input type='hidden' name='table_name' value='".$target."'/>";
		echo "<input type='hidden' name='rowid' value='".$rowid."'/>";
		echo "<input type='hidden' name='fields_count' value='".$fields_count."'/>";
		echo "<tr>";
		for($i=1; $i<=$fields_count; ++$i) {
			echo "<td>";
			echo "<input type='checkbox' id='field".$i."_is_null' name='is_null[".$i."]' title='NULL' value='false' onchange='cb_change(".$i.");'/>";
			echo "<input type='text' placeholder='".$types[$i-1]."' id='field".$i."_value' name='value[".$i."]'/>";
			echo "</td>";
		}
		echo "</tr></form>";
	?>
</table>
<a href="javascript:add_entry();" class="button right"><?php echo ($rowid==""?"Add":"Save"); ?></a>
<script>
	function cb_change(index) {
		var cb = document.getElementById("field"+index+"_is_null");
		var et = document.getElementById("field"+index+"_value");
		et.disabled = cb.checked;
		if(cb.checked) et.value = "NULL";
		else et.value = "";
		cb.value = (cb.checked?"true":"false");
	}

	var button_active = true;
	function add_entry() {
		if(!button_active) return;
		button_active = false;
		message('save_message', "gray_message", "Saving...");

		AJAX_POST("ajax/add_entry.php", $('#entry_form').serialize(),
			function(a) {
				button_active = true;
				if(a == "true") message('save_message', "info_message", "Saved");
				else message('save_message', "error_message", "Not saved");
				console.log(a);
			},
			function(e) {
				button_active = true;
				message('save_message', "error_message", "Failed to send the data");
			}
		);
	}

	<?php
		if($rowid != "") {
			$query = "SELECT * FROM ".$target." WHERE ROWID = '".$rowid."';";
			$result = odbc_exec($client->get_connection(), $query);
			$res = array();
			$fields_count = odbc_num_fields($result);
			if(odbc_fetch_into($result, $res)) {
				for ($i = 1; $i <= $fields_count; ++$i) {
					if($res[$i - 1] == null) echo "document.getElementById('field" . $i . "_is_null').checked = true; cb_change(" . $i . ");";
					else echo "document.getElementById('field" . $i . "_value').value = \"" . htmlspecialchars($res[$i - 1]) . "\";";
				}
			}
		}
	?>
</script>