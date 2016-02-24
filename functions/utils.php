<?php
include_once "functions/client.php";

function totally_escape($v) {
	return trim(htmlspecialchars(stripslashes($v)));
}

function format_num_rows($num) {
	if($num == "" || $num == 0) return "empty";
	if($num == 1) return "1 entry";
	return $num . " entries";
}

function get_num_rows(client $client, $table_name) {
	//TODO: check client's cookie (setting) not to do all these selects
	if(false) return "";

	$query = "select count(*) from ".$table_name.";";
	$res = odbc_exec($client->get_connection(), $query);
	if($res === false) return "unknown";
	else return format_num_rows(odbc_result($res, 1));
}
?>