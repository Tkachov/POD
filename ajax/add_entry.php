<?php
	//check auth
	include_once "../functions/client.php";
	include_once "../functions/utils.php";
	$client = new client();
	if(!$client->logged_in()) die("false");

	//check POST
	$table_name = null;
	$fields_count = 0;
	$rowid = null;

	if($_POST) {
		$table_name = totally_escape($_POST["table_name"]);
		$fields_count = $_POST["fields_count"];
		$rowid = totally_escape($_POST["rowid"]);
	}

	if($table_name == null) die("false");

	//TODO check table_name is one word

	//prepare statement
	if($rowid == null) {
		$query = "INSERT INTO ".$table_name." VALUES(";
		for($i=1; $i<$fields_count; ++$i) $query .= "?, ";
		$query .= "?);";
	} else {
		$colnames = odbc_exec($client->get_connection(), "SELECT column_name, data_type, data_length FROM ALL_TAB_COLUMNS WHERE table_name = '".strtoupper($table_name)."';");
		$q2="";

		for($i=1; $i<=$fields_count; ++$i) {
			if(!odbc_fetch_row($colnames)) die("false");
			if($i < $fields_count) $q2 .= odbc_result($colnames, 1)." = ?,\n";
			else $q2 .= odbc_result($colnames, 1)." = ?\n";
		}

		$query = "UPDATE ".$table_name." SET ".$q2." WHERE ROWID = ?;";
	}

	$statement = odbc_prepare($client->get_connection(), $query);
	if($statement === false) die($query."\n\n".get_odbc_error());

	$items = array();
	for($i=1; $i<=$fields_count; ++$i) {
		if(isset($_POST["is_null"]) && isset($_POST["is_null"][$i]) && $_POST["is_null"][$i] == true) {
			$items[] = null;
		} else {
			$items[] = $_POST["value"][$i];
		}
	}
	if($rowid != null) $items[] = $rowid;

	$result = odbc_execute($statement, $items);
	if($result === false) die($query."\n\n".get_odbc_error());
	echo "true";
?>