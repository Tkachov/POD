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
	<a href="javascript:create_table();" class="button right"><?php echo ($target==""?"Create table":"Edit table"); ?></a>
	<a href="javascript:add_column();" class="button right">Add column</a>
</form>
<div style="height: 60pt; clear: both;"></div>
<script>
	function cb_change(index) {
		 var cb = document.getElementById("column"+index+"_not_null");
		 //var et = document.getElementById("field"+index+"_value");
		 //et.disabled = cb.checked;
		 //if(cb.checked) et.value = "NULL";
		 //else et.value = "";
		 cb.value = (cb.checked?"true":"false");
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
			create_checkbox('column'+N+'_not_null', 'column_not_null['+N+']', function() {cb_change(N);})
		]));
		select_change(N);
	}

	function setup_column(index, name, type, precision, length, not_null) {
		document.getElementById('column'+index+'_name').value = name;
		document.getElementById('column'+index+'_type').value = type;
		document.getElementById('column'+index+'_precision').value = precision;
		document.getElementById('column'+index+'_length').value = length;
		document.getElementById('column'+index+'_not_null').checked = not_null;
		document.getElementById('column'+index+'_not_null').value = (not_null?'true':'false');

		select_change(index);
	}

<?php if($target=="") { ?>
	for(var i=0; i<5; ++i) add_column();
<?php
	} else {
		$colnames = odbc_exec($client->get_connection(), "SELECT column_name, data_type, data_precision, data_length, nullable, column_id FROM ALL_TAB_COLUMNS WHERE table_name = '".strtoupper(totally_escape($target))."' ORDER BY column_id ASC;");
		$idx = 1;
		while(odbc_fetch_row($colnames)) {
			echo "\tadd_column();\n";
			echo "\tsetup_column(".$idx.", \"".odbc_result($colnames, 1)."\", \"".odbc_result($colnames, 2)."\", \"".odbc_result($colnames, 3)."\", \"".odbc_result($colnames, 4)."\", \"".odbc_result($colnames, 5)."\" == \"N\");\n";
			//if == N => NOT NULL present (true)
			$idx += 1;
		}
	}
?>
</script>