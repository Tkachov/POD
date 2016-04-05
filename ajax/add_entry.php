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
		for($i=1; $i<$fields_count; ++$i) $query .= ":p".$i.", ";
		$query .= ":p".$fields_count.")";
	} else {
		$colnames = oci_parse($client->get_connection(), "SELECT column_name, data_type, data_length FROM ALL_TAB_COLUMNS WHERE table_name = '".strtoupper($table_name)."'");
		oci_execute($colnames);
		$q2="";

		for($i=1; $i<=$fields_count; ++$i) {
			if(!oci_fetch_row($colnames)) die("false");
			if($i < $fields_count) $q2 .= oci_result($colnames, 1)." = :p".$i.",\n";
			else $q2 .= oci_result($colnames, 1)." = :p".$fields_count."\n";
		}

		$query = "UPDATE ".$table_name." SET ".$q2." WHERE ROWID = :rowid";
	}

	$statement = oci_parse($client->get_connection(), $query);
	if($statement === false) die($query."\n\n".get_oci_error());

	for($i=1; $i<=$fields_count; ++$i) {
		if(isset($_POST["is_null"]) && isset($_POST["is_null"][$i]) && $_POST["is_null"][$i] == true) {
			$value = null;
			oci_bind_by_name($statement, ":p".$i, $value); //TODO TYPE?
		} else {
			oci_bind_by_name($statement, ":p".$i, $_POST["value"][$i]); //TODO TYPES HERE
		}
	}
	if($rowid != null) oci_bind_by_name($statement, ":rowid", $rowid); //TODO TYPE

	$result = oci_execute($statement);
	if($result === false) die($query."\n\n".get_oci_error());
	echo "true";
?>