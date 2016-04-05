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
	$query = "DELETE FROM ".$table_name." WHERE ROWID = :rowid;";
	$statement = oci_parse($client->get_connection(), $query);
	if($statement === false) die($query."\n\n".get_oci_error());

	oci_bind_by_name($statement, ":rowid", $rowid); //TODO TYPE
	$result = oci_execute($statement);
	if($result === false) die($query."\n\n".get_oci_error());
	echo "true";
?>