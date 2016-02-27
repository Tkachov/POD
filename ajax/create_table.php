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

	if($table_name == null) die("false: bad table_name");

	//prepare statement
	$types_arr = sql_types_array();

	//TODO: test table_name is one word or something

	$query = "CREATE TABLE ".totally_escape($table_name)." (\n";
	/*
	for($i=1; $i<$fields_count; ++$i) $query .= "?, ";
	$query .= "?);";
	*/

	$first = true;
	for($i=1; $i<=$fields_count; ++$i) {
		if(!isset($_POST["column_name"]) || !isset($_POST["column_name"][$i]) || $_POST["column_name"][$i] == "")
			continue;

		if(!isset($_POST["column_type"]) || !isset($_POST["column_type"][$i]) || !in_array($_POST["column_type"][$i], $types_arr))
			die("false: bad column_type:\n".$_POST["column_type"][$i]." not in ".var_export($types_arr));

		if($first) $first = false;
		else $query .= ",\n";

		$query .= "    ";

		//TODO: test column_name is one word or something

		$query .= totally_escape($_POST["column_name"][$i])." ".$_POST["column_type"][$i];
		if(isset($_POST["column_is_null"]) && isset($_POST["column_is_null"][$i]) && $_POST["column_is_null"][$i]=="true")
			$query .= " NOT NULL";
		//TODO UNIQUE PRIMARY KEY and so on
	}

	$query .= "\n);";

/*
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
*/
	$result = odbc_exec($client->get_connection(), $query);
	if($result === false) die($query."\n\n".get_odbc_error());
	echo "true";
?>