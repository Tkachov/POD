<?php
	if($action === "delete") {
		$query = "DROP TABLE ".$target.";";

		$result = odbc_exec($client->get_connection(), $query);
		if($result === false) {
			echo "<div class='error_message'>" . odbc_error() . ": " . odbc_errormsg() . "</div>";
		} else {
			echo "<div class='info_message'>".$target." dropped.</div>";
		}

		$action = "list";
	}

	if($action !== "list" && $action !== "edit" && $action !== "view" && $action !== "add_entry") $action = "list";

	if($action === "list") {
		$query = "SELECT table_name FROM user_tables;"; //current user's tables
		//"SELECT owner, table_name FROM all_tables;" for all tables
		//"SELECT owner, table_name FROM dba_tables;" for ALL tables (requires DBA privilege/role)

		$result = odbc_exec($client->get_connection(), $query);
		if($result === false) {
			echo "<div class='error_message'>" . odbc_error() . ": " . odbc_errormsg() . "</div>";
		}
		?>

		<div class="entries">
			<div class="entry plus_sign">
				<a href="?tables&action=edit"><div class="plus-sign"></div></a>
			</div>

			<?php
			if($result !== false) {
				while (odbc_fetch_row($result)) {
					$table_name = odbc_result($result, 1);
					echo "<div class=\"entry\""/* id=\"".$post["directory"]."\"*/ . ">\n";
					echo "\t<a class=\"visibility_button\" href=\"?tables&action=delete&target=".$table_name."\"></a>\n";
					echo "\t<a href=\"?tables&action=view&target=".$table_name."\">";
					echo "\t\t<b>".$table_name."</b>\n";
					echo "\t\t<span class=\"date\">".get_num_rows($client, $table_name)."</span>\n";
					echo "\t</a>\n";
					echo "</div>\n";
				}
			}

			odbc_close($client->get_connection());
			?>
		</div>

<?php
	} else if($action === "edit") {
?>
	<!-- TODO: edit mode -->
	<?php
		/*
		CREATE TABLE head_students (
			ID INTEGER NOT NULL PRIMARY KEY,
			student_ID INTEGER NOT NULL UNIQUE,
			group_ID INTEGER NOT NULL UNIQUE,
			policy INTEGER NOT NULL CHECK(policy >= 100000 AND policy <= 999999),
			FOREIGN KEY (student_ID) REFERENCES students (ID),
			FOREIGN KEY (group_ID) REFERENCES groups (ID)
		);
		 */
	?>

	<div id="save_message" style="display: none;"></div>
	<form id='table_form'>
		<?php
			echo "<input type='".($target==""?"text":"hidden")."' placeholder='table name' name='table_name' value='".$target."' class='create_table_table_name_field'/>";
		?>
		<input type='hidden' name='fields_count' id='fields_count' value='0'/>

		<table id="table_columns" class="results_table">
			<tr>
				<th>Column Name</th>
				<th>Type</th>
				<th style="max-width: 50pt;">Precision</th>
				<th style="max-width: 50pt;">Length</th>
				<th style="max-width: 50pt;">Not NULL</th>
				<!--
				<th>Identity</th>
				-->
			</tr>
		</table>
		<!-- TODO primary key / unique -->
		<!-- TODO check clause -->
		<!-- TODO "foreign key references" -->
		<a href="javascript:create_table();" class="button right">Create table</a>
		<a href="javascript:add_column();" class="button right">Add column</a>
	</form>
	<div style="height: 60pt; clear: both;"></div>
	<script>
		function cb_change(index) {
			/*
			var cb = document.getElementById("field"+index+"_is_null");
			var et = document.getElementById("field"+index+"_value");
			et.disabled = cb.checked;
			if(cb.checked) et.value = "NULL";
			else et.value = "";
			cb.value = (cb.checked?"true":"false");
			*/
		}

		function select_change(index) {
			var has_precision = ["NUMBER", "FLOAT", "INTERVAL YEAR TO MONTH", "INTERVAL DAY TO SECOND"];
			var has_length = {
				"NUMBER": 38, "VARCHAR2": 4000, "CHAR": 2000, "TIMESTAMP": -1,
				"INTERVAL DAY TO SECOND": -1, "TIMESTAMP WITH TIME ZONE": -1, "TIMESTAMP WITH LOCAL TIME ZONE": -1, "RAW": -1, "NCHAR": 2000, "NVARCHAR2": 4000};

			var sl = document.getElementById('column'+index+'_type');
			var ni = document.getElementById('column'+index+'_precision');
			ni.disabled = (has_precision.indexOf(sl.value) == -1);

			ni = document.getElementById('column'+index+'_length');
			if(has_length.hasOwnProperty(sl.value)) {
				ni.disabled = false;
				ni.max = (has_length[sl.value] == -1 ? undefined : has_length[sl.value]);
			} else ni.disabled = true;
		}

		var button_active = true;

		function message(eid, cl, m) {
			var e = document.getElementById(eid);
			e.className = cl;
			e.innerHTML = m;
			if(m == "") e.style.display = "none"; else e.style.display = "block";
		}

		function create_table() {
			if(!button_active) return;
			button_active = false;
			message('save_message', "gray_message", "Creating...");

			var form = $('#table_form');
			$.ajax({
				type: "POST",
				url: "ajax/create_table.php",
				dataType: "html",
				data: form.serialize(),
				success: function(a) {
					button_active = true;
					if(a == "true") message('save_message', "info_message", "Created");
					else message('save_message', "error_message", "Not created");
					console.log(a);
				},
				error: function(e) {
					button_active = true;
					message('save_message', "error_message", "Failed to send the data");
				}
			});
		}

		function create_table_row(cells) {
			var row = document.createElement('tr'), cell;
			for(var c of cells) {
				cell = document.createElement('td');
				cell.appendChild(c);
				row.appendChild(cell);
			}
			return row;
		}

		function create_text_input(placeholder, id, name) {
			var input = document.createElement('input');
			input.type = 'text';
			input.placeholder = placeholder;
			input.id = id;
			input.name = name;
			return input;
		}

		function create_type_select(id, name, onchange) {
			var arr = <?php echo json_encode(sql_types_array()); ?>;
			var input = document.createElement('select');
			input.id = id;
			input.name = name;
			input.onchange = onchange;
			for(var v of arr) {
				var option = document.createElement('option');
				option.value = v;
				option.innerHTML = v;
				input.appendChild(option);
			}
			return input;
		}

		function create_number_input(id, name/*, onchange*/) {
			var input = document.createElement('input');
			input.type = 'number';
			input.id = id;
			input.name = name;
			input.value = 1;
			input.min = 1;
			//input.onchange = onchange;
			return input;
		}

		function create_checkbox(id, name, onchange) {
			var input = document.createElement('input');
			input.type = 'checkbox';
			input.id = id;
			input.name = name;
			input.value = 'false';
			input.onchange = onchange;
			return input;
		}

		function add_column() {
			var N = parseInt(document.getElementById("fields_count").value) + 1;
			document.getElementById("fields_count").value = N;
			var el = document.getElementById("table_columns");
			el.appendChild(create_table_row([
				create_text_input('name', 'column'+N+'_name', 'column_name['+N+']'),
				create_type_select('column'+N+'_type', 'column_type['+N+']', function() {select_change(N);}),
				create_number_input('column'+N+'_precision', 'column_precision['+N+']'),
				create_number_input('column'+N+'_length', 'column_length['+N+']'),
				create_checkbox('column'+N+'_not_null', 'column_not_null['+N+']', 'cb_change('+N+')')
			]));
			select_change(N);
		}

		for(var i=0; i<5; ++i) add_column();
	</script>
<?php
	} else if($action === "add_entry") {
?>
	<?php
		$query = "SELECT column_name, data_type, data_length FROM ALL_TAB_COLUMNS WHERE table_name = '".strtoupper($target)."';";
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
				$types[] = odbc_result($result, 2)."(".odbc_result($result, 3).")";
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

		function message(eid, cl, m) {
			var e = document.getElementById(eid);
			e.className = cl;
			e.innerHTML = m;
			if(m == "") e.style.display = "none"; else e.style.display = "block";
		}

		function add_entry() {
			if(!button_active) return;
			button_active = false;
			message('save_message', "gray_message", "Saving...");

			var form = $('#entry_form');
			$.ajax({
				type: "POST",
				url: "ajax/add_entry.php",
				dataType: "html",
				data: form.serialize(),
				success: function(a) {
					button_active = true;
					if(a == "true") message('save_message', "info_message", "Saved");
					else message('save_message', "error_message", "Not saved");
					console.log(a);
				},
				error: function(e) {
					button_active = true;
					message('save_message', "error_message", "Failed to send the data");
				}
			});
		}

		<?php
			$query = "SELECT * FROM ".$target." WHERE ROWID = '".$rowid."';";
			$result = odbc_exec($client->get_connection(), $query);
			$res = array();
			$fields_count = odbc_num_fields($result);
			if(odbc_fetch_into($result, $res)) {
				for($i=1; $i<=$fields_count; ++$i) {
					if($res[$i-1] == null) echo "document.getElementById('field".$i."_is_null').checked = true; cb_change(".$i.");";
					else echo "document.getElementById('field".$i."_value').value = \"".htmlspecialchars($res[$i-1])."\";";
				}
			}
		?>
	</script>
<?php
	} else if($action === "view") {
?>
	<!-- TODO: view mode -->
	<div class="table_panel">
		<h1><?php echo $target; ?></h1>
		<span><?php echo get_num_rows($client, $target); ?></span>
		<!-- TODO edit table buttons -->
		<a class="button delete" href=<?php echo "\"?tables&action=delete&target=".$target."\""; ?>></a>
		<a class="button edit" href=<?php echo "\"?tables&action=edit&target=".$target."\""; ?>></a>
		<a class="button add" href=<?php echo "\"?tables&action=add_entry&target=".$target."\""; ?>><div class="plus-sign"></div></a>
	</div>

	<div id="save_message" style="display: none;"></div>

	<?php
		$query = "select ROWID, a.* from ".$target." a where rownum<50;";
		$result = odbc_exec($client->get_connection(), $query);
		make_results_table($result, false, $target);
	?>

	<script>
		var button_active = true;

		function message(eid, cl, m) {
			var e = document.getElementById(eid);
			e.className = cl;
			e.innerHTML = m;
			if(m == "") e.style.display = "none"; else e.style.display = "block";
		}

		function delete_entry(rowid) {
			if(!button_active) return;
			button_active = false;
			message('save_message', "gray_message", "Saving...");

			$.ajax({
				type: "POST",
				url: "ajax/delete_entry.php",
				dataType: "html",
				data: {"target": <?php echo "\"".$target."\""; ?>, "rowid": rowid},
				success: function(a) {
					button_active = true;
					if(a == "true") {
						message('save_message', "info_message", "Deleted");
						//TODO hide on timeout
						var el = document.getElementById("row_"+rowid);
						el.parentNode.removeChild(el);
					}
					else message('save_message', "error_message", "Not deleted");
					console.log(a);
				},
				error: function(e) {
					button_active = true;
					message('save_message', "error_message", "Failed to send the data");
				}
			});
		}
	</script>
<?php
	}
?>