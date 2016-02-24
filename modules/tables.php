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
	} else if($action === "view") {
?>
	<!-- TODO: view mode -->
	<div class="table_panel">
		<h1><?php echo $target; ?></h1>
		<span><?php echo get_num_rows($client, $target); ?></span>
		<!-- TODO add entry & edit table buttons -->
		<a class="button delete" href=<?php echo "\"?tables&action=delete&target=".$target."\""; ?>></a>
		<a class="button edit" href=<?php echo "\"\""; ?>></a>
		<a class="button add" href=<?php echo "\"\""; ?>><div class="plus-sign"></div></a>
	</div>

	<?php
		$query = "select * from ".$target." where rownum<50;";
		$result = odbc_exec($client->get_connection(), $query);
		make_results_table($result);
	?>
<?php
	}
?>