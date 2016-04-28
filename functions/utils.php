<?php
include_once "functions/client.php";

function get_odbc_error() {
	return odbc_error().": ".odbc_errormsg();
}

function sql_types_array() {
	return array("NUMBER", "VARCHAR2", "DATE", "TIMESTAMP", "CLOB", "BLOB", "BFILE", "CHAR", "FLOAT", "INTERVAL YEAR TO MONTH", "INTERVAL DAY TO SECOND", "TIMESTAMP WITH TIME ZONE", "TIMESTAMP WITH LOCAL TIME ZONE", "BINARY_FLOAT", "BINARY_DOUBLE", "RAW", "LONG RAW", "NCHAR", "NVARCHAR2", "NCLOB");
}

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

function get_columns_info_query($table_name) {
	$q = "SELECT column_name, data_type, data_precision, data_length, nullable, CONSTRAINT_TYPE, column_id FROM ALL_TAB_COLUMNS acol LEFT JOIN (select CONSTRAINT_TYPE, COLUMN_NAME as c2, cols.TABLE_NAME as t2 from user_constraints uc inner join USER_IND_COLUMNS cols ON (uc.index_name = cols.index_name and uc.table_name = cols.table_name)) ON (column_name = c2 and table_name=t2) where table_name='".strtoupper(totally_escape($table_name))."' ORDER BY column_id ASC";
	return $q;
}
?>