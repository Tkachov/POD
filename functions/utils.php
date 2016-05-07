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
	$q = "SELECT column_name, data_type, data_precision, data_length, data_scale, nullable, CONSTRAINT_TYPE, column_id FROM ALL_TAB_COLUMNS acol LEFT JOIN (select CONSTRAINT_TYPE, COLUMN_NAME as c2, cols.TABLE_NAME as t2 from user_constraints uc inner join USER_IND_COLUMNS cols ON (uc.index_name = cols.index_name and uc.table_name = cols.table_name)) ON (column_name = c2 and table_name=t2) where table_name='".strtoupper(totally_escape($table_name))."' ORDER BY column_id ASC";
	return $q;
}

function get_foreign_keys_constraints_query($table_name) {
	/*
	$q = "SELECT a.table_name, a.column_name, a.constraint_name, c.owner, c.r_owner, c_pk.table_name r_table_name, c_pk.constraint_name r_pk
  FROM all_cons_columns a
  JOIN all_constraints c ON a.owner = c.owner
AND a.constraint_name = c.constraint_name
  JOIN all_constraints c_pk ON c.r_owner = c_pk.owner
AND c.r_constraint_name = c_pk.constraint_name
 WHERE c.constraint_type = 'R'
AND a.table_name = '".strtoupper(totally_escape($table_name))."'";
	*/
	$q = "SELECT c_list.CONSTRAINT_NAME as NAME,
substr(c_src.COLUMN_NAME, 1, 20) as SRC_COLUMN,
c_dest.TABLE_NAME as DEST_TABLE,
substr(c_dest.COLUMN_NAME, 1, 20) as DEST_COLUMN
FROM ALL_CONSTRAINTS c_list, ALL_CONS_COLUMNS c_src, ALL_CONS_COLUMNS c_dest
WHERE c_list.CONSTRAINT_NAME = c_src.CONSTRAINT_NAME
AND c_list.R_CONSTRAINT_NAME = c_dest.CONSTRAINT_NAME
AND c_list.CONSTRAINT_TYPE = 'R'
AND c_src.TABLE_NAME = '".strtoupper(totally_escape($table_name))."'
GROUP BY c_list.CONSTRAINT_NAME, c_src.TABLE_NAME,
    c_src.COLUMN_NAME, c_dest.TABLE_NAME,    c_dest.COLUMN_NAME;";
	return $q;
}
?>