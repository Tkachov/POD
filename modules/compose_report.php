<?php
	if($_POST && isset($_POST["target"]) && isset($_POST["show"]) && isset($_POST["rownum"])) {
		//show report

		function get_report(client $client, $table_name, $show, $rownum) {
			if($table_name == null) return "Bad table name.";
			//TODO check table_name is one word

			//compile query
			$colnames = odbc_exec($client->get_connection(), "SELECT column_name, data_type, data_length FROM ALL_TAB_COLUMNS WHERE table_name = '".strtoupper($table_name)."';");
			if($colnames === false) return "Unable to get table fields.";

			$query = "SELECT ";
			$i = 0;
			while(odbc_fetch_row($colnames)) {
				if(isset($show) && isset($show[$i]) && $show[$i] == true) {
					if($query != "SELECT ") $query .= ", ";
					$query .= odbc_result($colnames, 1);
				}
				$i += 1;
			}
			$query .= " FROM ".$table_name." WHERE rownum <= ?;";

			//prepare statement
			$statement = odbc_prepare($client->get_connection(), $query);
			if($statement === false) return $query."\n\n".get_odbc_error();

			$items = array();
			$items[] = (int)$rownum;

			$result = odbc_execute($statement, $items);
			if($result === false) return $query."\n\n".get_odbc_error();
			return $statement;
		}

		$result = get_report($client, totally_escape($_POST["target"]), $_POST["show"], $_POST["rownum"]);
		if(is_string($result)) echo "<div class=\"error_message\">".$result."</div>";
		else make_results_table($result, false, null);
?>
<?php
	} else {
		//show form
?>

<div id="form_message" style="display: none;"></div>

<form action="" method="post" class="report_form" id="report_form">
	<p>Compose report of <input type='number' name='rownum' value='50' min='1'/> rows from table
	<select id="report_target" name="target" onchange="show_fields();">
		<option value="">&lt;select table&gt;</option>
	<?php
	$query = "SELECT table_name FROM user_tables;";

	$result = odbc_exec($client->get_connection(), $query);
	if($result === false) {
		echo "<div class='error_message'>" . odbc_error() . ": " . odbc_errormsg() . "</div>";
		//TODO: form_message
	}

		if($result !== false) {
			while (odbc_fetch_row($result)) {
				$table_name = odbc_result($result, 1);
				echo "<option value='".$table_name."'>".$table_name."</option>";
			}
		}
	?>
	</select></p>

	<div id="fields"></div>

	<script>
		function show_fields() {
			var e = document.getElementById("report_target");
			if(e.value == "") {
				add_fields_elements("");
				return;
			}

			AJAX_POST("ajax/get_table_fields.php", {"target": e.value},
				function(a) {
					console.log(a);
					var o = get_json(a);
					if(o == false) message('form_message', "error_message", "Failed to parse table fields");
					else add_fields_elements(e.value, o);
				},
				function(e) {
					message('form_message', "error_message", "Failed to get table fields");
				}
			);
		}

		function add_fields_elements(target_table, fields_list) {
			var e = document.getElementById("report_target");
			if(e.value != target_table) return; //race conditions handler

			var div = document.getElementById("fields");
			while(div.hasChildNodes()) div.removeChild(div.firstChild);

			if(target_table == "") return;

			var p = document.createElement("p");
			p.innerHTML = "Include the following fields into the report:";
			div.appendChild(p);

			for(var i in fields_list) {
				var label = document.createElement("label");
				var field = document.createElement("input");
				var field_l = document.createElement("span");
				field.type='checkbox';
				field.value = "true";
				field.checked = true;
				field.name = "show["+i+"]";
				field.onchange = checkbox_changed;
				label.className = "report_field_selector";
				field_l.innerHTML = fields_list[i];
				label.appendChild(field);
				label.appendChild(field_l);
				div.appendChild(label);
			}

			var a = document.createElement("a");
			a.innerHTML = "Compose";
			a.className = "button right";
			a.href = "javascript:document.forms['report_form'].submit();";
			div.appendChild(a);
		}

		function checkbox_changed(e) {
			e = e.target;
			e.value = (e.checked?"true":"false");
		}
	</script>
</form>

<?php
	}
?>