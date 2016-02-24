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
				<!-- TODO: make it create new table -->
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
	<h1>Edit mode</h1>
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
	<a href="javascript:add_entry();" class="button right">Add</a>
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

	<?php
		$query = "select * from ".$target." where rownum<50;";
		$result = odbc_exec($client->get_connection(), $query);
		make_results_table($result);
	?>
<?php
	}
?>