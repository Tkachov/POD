<div class="table_panel">
	<h1><?php echo $target; ?></h1>
	<span><?php echo get_num_rows($client, $target); ?></span>
	<!-- TODO get edit table button working -->
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
	function delete_entry(rowid) {
		if(!button_active) return;
		button_active = false;
		message('save_message', "gray_message", "Deleting...");

		AJAX_POST(
			"ajax/delete_entry.php",
			{"target": <?php echo "\"".$target."\""; ?>, "rowid": rowid},
			function(a) {
				button_active = true;
				if(a == "true") {
					message('save_message', "info_message", "Deleted");
					hideIn5('save_message');
					var el = document.getElementById("row_"+rowid);
					el.parentNode.removeChild(el);
				}
				else message('save_message', "error_message", "Not deleted");
				console.log(a);
			},
			function(e) {
				button_active = true;
				message('save_message', "error_message", "Failed to send the data");
			}
		);
	}
</script>