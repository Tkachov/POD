<?php
include_once "client.php";

function format_num_rows($num) {
	echo "<!-- format rows: " . $num . " -->";
	if($num == "" || $num == 0) return "empty";
	if($num == 1) return "1 entry";
	return $num . " entries";
}

function get_num_rows(client $client, $table_name) {
	//TODO: check client's cookie (setting) not to do all these selects
	echo "<!-- get num rows -->";

	if(false) return "";

	echo "<!-- not false -->";

	$query = "select count(*) from ".$table_name.";";
	echo "<!-- query: " . $query . " -->";
	$res = odbc_exec($client->get_connection(), $query);
	echo "<!-- result: " . $res . " -->";
	if($res === false) return "unknown";
	else return format_num_rows(odbc_result($res, 1));
}
?>