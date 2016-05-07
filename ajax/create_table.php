<?php
	//check auth
	include_once "../functions/client.php";
	include_once "../functions/utils.php";
	$client = new client();
	if(!$client->logged_in()) die("false");

	//check POST
	$table_name = null;
	$fields_count = 0;
	$foreign_keys_count = 0;

	if($_POST) {
		$table_name = totally_escape($_POST["table_name"]);
		$fields_count = $_POST["fields_count"];
		$foreign_keys_count = $_POST["foreign_keys_count"];
	}

	if($table_name == null) die("false: bad table_name");

	//prepare statement
	$types_arr = sql_types_array();

	//TODO: test table_name is one word or something

	if(odbc_exec($client->get_connection(), "COMMIT;") === false) die(get_odbc_error());
	if(odbc_exec($client->get_connection(), "SET TRANSACTION NAME 'create_table_fields_transaction';") === false) die(get_odbc_error());
	$rollback_needed = false;
	$rollback_error_message = "";

	$query = "CREATE TABLE ".totally_escape($table_name)." (\n";
	/*
	for($i=1; $i<$fields_count; ++$i) $query .= "?, ";
	$query .= "?);";
	*/

	$has_precision = array("NUMBER", "FLOAT", "INTERVAL YEAR TO MONTH", "INTERVAL DAY TO SECOND");
	$has_length = array(
		"NUMBER" => 38, "VARCHAR2" => 4000, "CHAR" => 2000, "TIMESTAMP" => -1,
		"INTERVAL DAY TO SECOND" => -1, "TIMESTAMP WITH TIME ZONE" => -1, "TIMESTAMP WITH LOCAL TIME ZONE" => -1,
		"RAW" => -1, "NCHAR" => 2000, "NVARCHAR2" => 4000);

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

		$add_precision = (in_array($_POST["column_type"][$i], $has_precision));
		$add_length = (array_key_exists($_POST["column_type"][$i], $has_length));

		if($add_precision && (!isset($_POST["column_precision"]) || !isset($_POST["column_precision"][$i])))
			die("false"); //TODO test it's number
		if($add_length && (!isset($_POST["column_length"]) || !isset($_POST["column_length"][$i])))
			die("false"); //TODO test it's number & <=max value

		if($add_length && $add_precision) {
			$query .= "(".$_POST["column_precision"][$i].",".$_POST["column_length"][$i].")";
		} else if($add_length) {
			$query .= "(".$_POST["column_length"][$i].")";
		} else if($add_precision) {
			$query .= "(".$_POST["column_precision"][$i].")";
		}

		if(isset($_POST["column_not_null"]) && isset($_POST["column_not_null"][$i]) && $_POST["column_not_null"][$i]=="true")
			$query .= " NOT NULL";

		$is_unique = false;
		$is_primary = false;

		if(isset($_POST["column_unique"]) && isset($_POST["column_unique"][$i]) && $_POST["column_unique"][$i]=="true")
			$is_unique = true;

		if(isset($_POST["column_primary"]) && isset($_POST["column_primary"][$i]) && $_POST["column_primary"][$i]=="true")
			$is_primary = true;

		if($is_primary)
			; //$query .= " PRIMARY KEY"; //complex primary key is handled down there
		else if($is_unique)
			$query .= " UNIQUE";
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
	if(odbc_exec($client->get_connection(), $query) === false) {
		$rollback_needed = true;
		$rollback_error_message = get_odbc_error();
	}

	//add complex primary key
	if($rollback_needed === false) {
		$fields_list = "";

		for ($i = 1; $i <= $fields_count; ++$i) {
			if(!isset($_POST["column_name"]) || !isset($_POST["column_name"][$i]) || $_POST["column_name"][$i] == "")
				continue;

			if(isset($_POST["column_primary"]) && isset($_POST["column_primary"][$i]) && $_POST["column_primary"][$i]=="true")
				if($fields_list == "") {
					$fields_list = totally_escape($_POST["column_name"][$i]);
				} else {
					$fields_list .= ", ".totally_escape($_POST["column_name"][$i]);
				}
		}

		if($fields_list != "") {
			if(odbc_exec($client->get_connection(), "ALTER TABLE ".totally_escape($table_name)." ADD CONSTRAINT ".totally_escape($table_name)."_primary_key_constraint PRIMARY KEY (".$fields_list.");") === false) {
				$rollback_needed = true;
				$rollback_error_message = get_odbc_error();
			}
		}
	}

	//add foreign keys
	if($rollback_needed === false) {
		/*
		 * foreign_key_constraint_name[" + N + "]";
			foreign_key_changed[" + N + "]";
			foreign_key_columns[" + N + "][]";
			foreign_key_table[" + N + "]";
			foreign_key_other_columns[" + N + "][]";
			foreign_key_delete['+N+']';
		 */

		for ($i = 1; $i <= $foreign_keys_count; ++$i) {
			if(!isset($_POST["foreign_key_changed"]) || !isset($_POST["foreign_key_changed"][$i]) || $_POST["foreign_key_changed"][$i]!="true")
				continue;

			//when creating table we can only create new foreign keys constraints

			/*
			 * ALTER TABLE "NEWTABLE" ADD
			FOREIGN KEY ("DT")
			REFERENCES "MARKS" ("MARK_DATE")
			 */

			$fkq = "ALTER TABLE \"".totally_escape($table_name)."\" ADD FOREIGN KEY (";
			if(isset($_POST["foreign_key_columns"]) || !isset($_POST["foreign_key_columns"][$i])) {
				$list = "";
				foreach ($_POST["foreign_key_columns"][$i] as $cname)
					if($list == "") $list = "\"" . $cname . "\"";
					else $list .= ", \"" . $cname . "\"";
				$fkq .= $list;
			}
			$fkq .= ") REFERENCES \"".totally_escape($_POST["foreign_key_table"][$i])."\" (";
			if(isset($_POST["foreign_key_other_columns"]) || !isset($_POST["foreign_key_other_columns"][$i])) {
				$list = "";
				foreach ($_POST["foreign_key_other_columns"][$i] as $cname)
					if($list == "") $list = "\"" . $cname . "\"";
					else $list .= ", \"" . $cname . "\"";
				$fkq .= $list;
			}
			$fkq .= ")";
			if(odbc_exec($client->get_connection(), $fkq) === false) {
				$rollback_needed = true;
				$rollback_error_message = $fkq."\n\n".get_odbc_error();
				break;
			}
		}
	}

	//check if rollback needed
	if($rollback_needed === true) {
		odbc_exec($client->get_connection(), "DROP TABLE ".totally_escape($table_name)); //we don't care whether it's successful
		//but as table is not dropped after rollback, that might fix our problem
	}

	if($rollback_needed === true) {
		if(odbc_exec($client->get_connection(), "ROLLBACK;") === false) {
			die("Error occurred. Was unable rollback the transaction:\n\n".$rollback_error_message."\n\n".get_odbc_error());
		}
		die("Error occurred. Transaction was rollbacked.\n\n".$rollback_error_message);
	}

	if(odbc_exec($client->get_connection(), "COMMIT;") === false) {
		$err = get_odbc_error();
		if(odbc_exec($client->get_connection(), "ROLLBACK;") === false) {
			die("Was unable to both commit and rollback the transaction:\n\n".$err."\n\n".get_odbc_error());
		}
		die("Was unable to both commit the transaction. It was rollbacked.\n\n".$err);
	}

	echo "true";
?>