<?php
	//check auth
	include_once "../functions/client.php";
	include_once "../functions/utils.php";
	$client = new client();
	if(!$client->logged_in()) die("false");

	//check POST
	$table_name = null;

	if($_POST) {
		$table_name = totally_escape($_POST["target"]);
	}

	if($table_name == null) die("false");

	//TODO check table_name is one word

	//select column names
	$colnames = odbc_exec($client->get_connection(), "SELECT column_name, data_type, data_length FROM ALL_TAB_COLUMNS WHERE table_name = '".strtoupper($table_name)."';");
	if($colnames === false) die("false");

	$q2 = "";
	while(odbc_fetch_row($colnames)) {
		if($q2 == "") $q2 .= '["'.odbc_result($colnames, 1).'"';
		else $q2 .= ', "'.odbc_result($colnames, 1).'"';
	}
	$q2 .= "]";

	echo $q2;
?>