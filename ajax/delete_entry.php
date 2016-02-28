<?php
	//check auth
	include_once "../functions/client.php";
	include_once "../functions/utils.php";
	$client = new client();
	if(!$client->logged_in()) die("false");

	//check POST
	$table_name = null;
	$rowid = null;

	if($_POST) {
		$table_name = totally_escape($_POST["target"]);
		$rowid = totally_escape($_POST["rowid"]);
	}

	if($table_name == null || $rowid == null) die("false");

	//TODO check table_name is one word

	//prepare statement
	$query = "DELETE FROM ".$table_name." WHERE ROWID = ?;";
	$statement = odbc_prepare($client->get_connection(), $query);
	if($statement === false) die($query."\n\n".get_odbc_error());

	$items = array($rowid);
	$result = odbc_execute($statement, $items);
	if($result === false) die($query."\n\n".get_odbc_error());
	echo "true";
?>