<?php
	//check auth
	include_once "../functions/client.php";
	include_once "../functions/utils.php";
	$client = new client();
	if(!$client->logged_in()) die("false");

	//check POST
	$table_name = null;
	$fields_count = 0;

	if($_POST) {
		$table_name = totally_escape($_POST["table_name"]);
		$fields_count = $_POST["fields_count"];
	}

	if($table_name == null) die("false");

	//prepare statement
	$query = "INSERT INTO ".$table_name." VALUES(";
	for($i=1; $i<$fields_count; ++$i) $query .= "?, ";
	$query .= "?);";

	$statement = odbc_prepare($client->get_connection(), $query);
	if($statement === false) die(get_odbc_error());

	$items = array();
	for($i=1; $i<=$fields_count; ++$i) {
		if(isset($_POST["is_null"]) && isset($_POST["is_null"][$i]) && $_POST["is_null"][$i] == true) {
			$items[] = null;
		} else {
			$items[] = $_POST["value"][$i];
		}
	}

	$result = odbc_execute($statement, $items);
	if($result === false) die(get_odbc_error());
	echo "true";
?>