<?php
	if($action !== "list" && $action !== "edit" && $action !== "view") $action = "list";

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

			<!-- TODO: "visibility" delete button icon -->
			<!-- TODO: make delete buttons delete table -->

			<?php

			if($result !== false) {
				while (odbc_fetch_row($result)) {
					echo "<div class=\"entry\""/* id=\"".$post["directory"]."\"*/ . ">\n";
					echo "\t<span class=\"visibility_button\" onclick=\"toggle('" . ""/*$post["directory"]*/ . "', '')\">V</span>\n";
					echo "\t<a href=\"?tables&action=view&target=".odbc_result($result, 1)."\">";
					echo "\t\t<b>" . odbc_result($result, 1) . "</b>\n";
					echo "\t\t<span class=\"date\">" . get_num_rows($client, odbc_result($result, 1)) . "</span>\n";
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
	} else if($action === "view") {
?>
	<!-- TODO: view mode -->
	<div class="table_panel">
		<h1><?php echo $target; ?></h1>
		<span><?php echo get_num_rows($client, $target); ?></span>
		<!-- TODO add entry/edit table/delete table buttons -->
	</div>

	<!-- TODO add edit/delete buttons for each row -->
	<!-- TODO field editing on click [?] -->

	<?php
		$query = "select * from ".$target." where rownum<50;";
		$result = odbc_exec($client->get_connection(), $query);
		if($result === false) {
	?>
		<div class="error_message">
			<?php echo odbc_error() . ": " . odbc_errormsg(); ?>
		</div>
	<?php
		} else {
	?>
		<table>
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
	?>
<?php
	}
?>