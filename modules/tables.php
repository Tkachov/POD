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

	if($action === "list") @include_once("tables/list.php");
	else if($action === "edit") @include_once("tables/edit.php");
	else if($action === "add_entry") @include_once("tables/add_entry.php");
	else if($action === "view") @include_once("tables/view.php");
?>
