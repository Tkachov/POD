<!-- TODO: edit mode -->
<!-- TODO: $target -->
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
	echo "<input type='".($target==""?"text":"hidden")."' placeholder='table name' name='table_name' value='".totally_escape($target)."' class='create_table_table_name_field'/>";
	if($target != "") {
		echo "<h1>".totally_escape($target)."</h1>";
		echo "<input type='hidden' name='mode' value='editing'/>";
	}
	?>
	<input type='hidden' name='fields_count' id='fields_count' value='0'/>
	<input type='hidden' name='foreign_keys_count' id='foreign_keys_count' value='0'/>

	<table id="table_columns" class="results_table">
		<tr>
			<th>Column Name</th>
			<th>Type</th>
			<th style="max-width: 50pt;">Precision</th>
			<th style="max-width: 50pt;">Length</th>
			<th style="min-width: 55pt; max-width: 55pt;">Not NULL</th>
			<th style="max-width: 50pt;">Unique</th>
			<th style="max-width: 50pt;">Primary</th>
			<!--
			<th>Identity</th>
			-->
		</tr>
	</table>

	<a href="javascript:create_table();" class="button right"><?php echo ($target==""?"Create table":"Edit table"); ?></a>
	<a href="javascript:add_column();" class="button right">Add column</a>
	<a href="javascript:add_foreign_key('');" class="button right" style="width: 150pt;">Add foreign key</a>

	<br/>

	<!-- TODO "foreign key references" -->

	<h1 style="clear: both;">Foreign keys:</h1>
	<div class="entries" id="foreign_keys">
	</div>

	<script>
		<?php
		$q = "SELECT table_name, column_name FROM ALL_TAB_COLUMNS WHERE table_name IN (SELECT table_name FROM user_tables)";
		$colnames = odbc_exec($client->get_connection(), $q);
		$arr = array();
		while(odbc_fetch_row($colnames)) {
			if(!array_key_exists(odbc_result($colnames, 1), $arr))
				$arr[odbc_result($colnames, 1)] = array();
			$arr[odbc_result($colnames, 1)][] = odbc_result($colnames, 2);
		}
		echo "var keysets = ".json_encode($arr).";\n";
		?>

		function add_option(element, value, name) {
			var e = document.createElement("option");
			e.value = value;
			e.innerHTML = name;
			element.appendChild(e);
		}

		function add_columns_options(element) {
			if(element.disabled && element.innerHTML != "") return;
			element.innerHTML = "";
			var N = parseInt(document.getElementById("fields_count").value) + 1;
			for(var i=1; i<N; ++i) {
				var name = document.getElementById("column"+i+"_name").value;
				add_option(element, name, name);
			}
		}

		function add_other_columns_options(index) {
			var element = document.getElementById("foreign_key_other_columns_"+index);
			element.innerHTML = "";

			var table_name = document.getElementById("foreign_key_table_"+index).value;
			for(var i in keysets[table_name]) {
				var name = keysets[table_name][i];
				add_option(element, name, name);
			}
		}

		function update_columns_options() {
			var M = parseInt(document.getElementById("foreign_keys_count").value) + 1;
			for(var i=1; i<M; ++i)
				add_columns_options(document.getElementById("foreign_key_columns_"+i));
		}

		function fk_cb_change(index) {
			var cb = document.getElementById("foreign_key_delete_"+index);
			cb.value = (cb.checked?"true":"false");

			var f = document.getElementById("foreign_key_changed_"+index);
			f.value = "true";
		}

		function add_foreign_key(constraint_name) {
			var N = parseInt(document.getElementById("foreign_keys_count").value) + 1;
			document.getElementById("foreign_keys_count").value = N;

			var child = document.createElement("div");
			child.id = "foreign_key_" + N;
			var form = document.createElement("p");
			form.innerHTML = "Foreign key of ";
			child.appendChild(form);

			var cstrt = document.createElement("input");
			cstrt.type = "hidden";
			cstrt.name = "foreign_key_constraint_name[" + N + "]";
			cstrt.id = "foreign_key_constraint_name_" + N;
			cstrt.value = constraint_name;
			form.appendChild(cstrt);

			var chgd = document.createElement("input");
			chgd.type = "hidden";
			chgd.name = "foreign_key_changed[" + N + "]";
			chgd.id = "foreign_key_changed_" + N;
			chgd.value = (constraint_name == '' ? "true" : "false");
			form.appendChild(chgd);

			var this_columns = document.createElement("select");
			this_columns.multiple = "multiple";
			this_columns.name = "foreign_key_columns[" + N + "][]";
			this_columns.id = "foreign_key_columns_" + N;
			this_columns.disabled = (constraint_name != '');
			add_columns_options(this_columns);
			form.appendChild(this_columns);

			form.innerHTML += " references the following column(s) of table ";

			var table_selector = document.createElement("select");
			table_selector.name = "foreign_key_table[" + N + "]";
			table_selector.id = "foreign_key_table_" + N;
			table_selector.value = "";
			add_option(table_selector, "", "&lt;select table&gt;");
			for (var key in keysets) {
				add_option(table_selector, key, key);
			}
			table_selector.disabled = (constraint_name != '');
			form.appendChild(table_selector);

			form.innerHTML += ": ";

			var that_columns = document.createElement("select");
			that_columns.multiple = "multiple";
			that_columns.name = "foreign_key_other_columns[" + N + "][]";
			that_columns.id = "foreign_key_other_columns_" + N;
			that_columns.disabled = (constraint_name != '');
			form.appendChild(that_columns);

			if (constraint_name != '') {
				var cb = create_checkbox('foreign_key_delete_'+N, 'foreign_key_delete['+N+']', function() {fk_cb_change(N);});

				var e = document.createElement("label");
				e.innerHTML += "<br/><br/>Delete this constraint: ";
				e.appendChild(cb);
				form.appendChild(e);
			}

			var container = document.getElementById("foreign_keys");
			container.appendChild(child);

			table_selector = document.getElementById("foreign_key_table_"+N);
			table_selector.onchange = function() { add_other_columns_options(N); };
		}

		function setup_foreign_key(index, table_name, from_columns, to_columns) {
			var this_columns = document.getElementById("foreign_key_columns_"+index);
			var opts = this_columns.options;
			for(var opt, j = 0; opt = opts[j]; j++) {
				opt.selected = (from_columns.indexOf(opt.value) != -1);
			}

			var table_selector = document.getElementById("foreign_key_table_"+index);
			table_selector.value = table_name;

			add_other_columns_options(index);

			var that_columns = document.getElementById("foreign_key_other_columns_"+index);
			opts = that_columns.options;
			for(var opt, j = 0; opt = opts[j]; j++) {
				opt.selected = (to_columns.indexOf(opt.value) != -1);
			}
		}

	<?php
		if($target!="") {
			$q = get_foreign_keys_constraints_query($target);
			$colnames = odbc_exec($client->get_connection(), $q);
			$arr = array();
			while(odbc_fetch_row($colnames)) {
				if(!array_key_exists(odbc_result($colnames, 1), $arr)) {
					$arr[odbc_result($colnames, 1)] = array();
					$arr[odbc_result($colnames, 1)]['from'] = array();
					$arr[odbc_result($colnames, 1)]['to_table'] = odbc_result($colnames, 3);
					$arr[odbc_result($colnames, 1)]['to'] = array();
				}
				$arr[odbc_result($colnames, 1)]['from'][] = odbc_result($colnames, 2);
				$arr[odbc_result($colnames, 1)]['to'][] = odbc_result($colnames, 4);
			}

			$idx = 1;
			echo "window.onload = function() {";
			foreach($arr as $constraint => $desc) {
				echo "add_foreign_key('".$constraint."');";
				echo "setup_foreign_key(".$idx.", '".$desc['to_table']."', ".json_encode($desc['from']).", ".json_encode($desc['to']).");";
				$idx += 1;
			}
			echo "};";
		}
	?>
	</script>

	<!-- EOF FOREIGN -->
</form>
<div style="height: 60pt; clear: both;"></div>
<script>
	function cb_change(index) {
		 var cb = document.getElementById("column"+index+"_not_null");
		 cb.value = (cb.checked?"true":"false");
	}

	function cb_change2(index) {
		var cb = document.getElementById("column"+index+"_unique");
		cb.value = (cb.checked?"true":"false");
	}

	function cb_change3(index) {
		var cb = document.getElementById("column"+index+"_primary");
		cb.value = (cb.checked?"true":"false");

		var cb2 = document.getElementById("column"+index+"_unique");
		cb2.disabled = cb.checked;
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
	function create_table() {
		if(!button_active) return;
		button_active = false;
		message('save_message', "gray_message", <?php echo ($target==""?"\"Creating...\"":"\"Changing...\""); ?>);

		AJAX_POST(<?php echo ($target==""?"\"ajax/create_table.php\"":"\"ajax/edit_table_fields.php\""); ?>, $('#table_form').serialize(),
			function(a) {
				button_active = true;
				if(a == "true") message('save_message', "info_message", <?php echo ($target==""?"\"Created\"":"\"Changed\""); ?>);
				else message('save_message', "error_message", <?php echo ($target==""?"\"Not created\"":"\"Not changed\""); ?>);
				console.log(a);
			},
			function(e) {
				button_active = true;
				message('save_message', "error_message", "Failed to send the data");
			}
		);
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

	function create_text_input(placeholder, id, name, onchange) {
		var input = document.createElement('input');
		input.type = 'text';
		input.placeholder = placeholder;
		input.id = id;
		input.name = name;
		input.onchange = onchange;
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
			create_text_input('name', 'column'+N+'_name', 'column_name['+N+']', function() {update_columns_options();}),
			create_type_select('column'+N+'_type', 'column_type['+N+']', function() {select_change(N);}),
			create_number_input('column'+N+'_precision', 'column_precision['+N+']'),
			create_number_input('column'+N+'_length', 'column_length['+N+']'),
			create_checkbox('column'+N+'_not_null', 'column_not_null['+N+']', function() {cb_change(N);}),
			create_checkbox('column'+N+'_unique', 'column_unique['+N+']', function() {cb_change2(N);}),
			create_checkbox('column'+N+'_primary', 'column_primary['+N+']', function() {cb_change3(N);})
		]));
		select_change(N);
	}

	function setup_column(index, name, type, precision, length, scale, not_null, constraint) {
		var use_scale = false;
		if(type == "NUMBER") use_scale = true;

		document.getElementById('column'+index+'_name').value = name;
		document.getElementById('column'+index+'_type').value = type;
		document.getElementById('column'+index+'_precision').value = precision;
		document.getElementById('column'+index+'_length').value = (use_scale?scale:length);
		document.getElementById('column'+index+'_not_null').checked = not_null;
		document.getElementById('column'+index+'_not_null').value = (not_null?'true':'false');

		var primary = (constraint == 'P');
		document.getElementById('column'+index+'_primary').checked = primary;
		document.getElementById('column'+index+'_primary').value = (primary?'true':'false');

		var unique = (constraint == 'U' || constraint == 'P');
		document.getElementById('column'+index+'_unique').checked = unique;
		document.getElementById('column'+index+'_unique').value = (unique?'true':'false');
		document.getElementById('column'+index+'_unique').disabled = primary;

		select_change(index);
	}

<?php if($target=="") { ?>
	for(var i=0; i<5; ++i) add_column();
<?php
	} else {
		$q = get_columns_info_query($target);
		$colnames = odbc_exec($client->get_connection(), $q);
		$idx = 1;
		while(odbc_fetch_row($colnames)) {
			echo "\tadd_column();\n";
			echo "\tsetup_column(".$idx.", \"".odbc_result($colnames, 1)."\", \"".odbc_result($colnames, 2)."\", \"".odbc_result($colnames, 3)."\", \"".odbc_result($colnames, 4)."\", \"".odbc_result($colnames, 5)."\", \"".odbc_result($colnames, 6)."\" == \"N\", \"".odbc_result($colnames, 7)."\");\n";
			//if == N => NOT NULL present (true)
			$idx += 1;
		}
	}
?>
</script>