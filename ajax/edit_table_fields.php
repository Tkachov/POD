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

	$has_precision = array("NUMBER", "FLOAT", "INTERVAL YEAR TO MONTH", "INTERVAL DAY TO SECOND");
	$has_length = array(
		"NUMBER" => 38, "VARCHAR2" => 4000, "CHAR" => 2000, "TIMESTAMP" => -1,
		"INTERVAL DAY TO SECOND" => -1, "TIMESTAMP WITH TIME ZONE" => -1, "TIMESTAMP WITH LOCAL TIME ZONE" => -1,
		"RAW" => -1, "NCHAR" => 2000, "NVARCHAR2" => 4000);

	//lol that's where we use transactions:
	//COMMIT; //ends any other transactions
	//SET TRANSACTION NAME 'sal_update'; //start transaction
	//...
	//ROLLBACK; //if fail
	//COMMIT; //if success

	function compile_type_name($type, $precision, $length) {
		global $has_precision;
		global $has_length;

		if(!isset($type)) return false;

		$add_precision = (in_array($type, $has_precision));
		$add_length = (array_key_exists($type, $has_length));

		if($add_precision && !isset($precision)) return false;
		if($add_length && !isset($length)) return false;

		if($add_length && $add_precision) {
			return $type."(" .$precision . "," . $length . ")";
		} else if($add_length) {
			return $type."(" .$length . ")";
		} else if($add_precision) {
			return $type."(" .$precision . ")";
		}

		return $type;
	}

	if(odbc_exec($client->get_connection(), "COMMIT;") === false) die(get_odbc_error());
	if(odbc_exec($client->get_connection(), "SET TRANSACTION NAME 'edit_table_fields_transaction';") === false) die(get_odbc_error());
	$rollback_needed = false;
	$rollback_error_message = "";

	//check if existing fields were not changed
	//"SELECT column_name, data_type, data_precision, data_length, nullable, CONSTRAINT_TYPE, column_id FROM ALL_TAB_COLUMNS acol LEFT JOIN (select CONSTRAINT_TYPE, COLUMN_NAME as c2 from user_constraints uc inner join USER_IND_COLUMNS cols ON uc.index_name = cols.index_name) ON column_name = c2 where table_name='".strtoupper(totally_escape($target))."' ORDER BY column_id ASC"
	$colnames = odbc_exec($client->get_connection(), get_columns_info_query(strtoupper($table_name)));
	$idx = 1;
	$drop_primary_key = false;
	$make_unique_fields_list = array();//""
	while(odbc_fetch_row($colnames)) {
		//odbc_result($colnames, 1) -- column_name
		//odbc_result($colnames, 2) -- type
		//odbc_result($colnames, 3) -- precision
		//odbc_result($colnames, 4) -- length
		//odbc_result($colnames, 5) //if == N => NOT NULL present (true)
		//odbc_result($colnames, 6) //constraint type ('P' for primary, 'U' for unique)

		//TODO: compare with column$idx_*
		//if name is empty, delete column

		if(!isset($_POST["column_name"]) || !isset($_POST["column_name"][$idx]) || $_POST["column_name"][$idx] == "") {
			//echo "drop column ".odbc_result($colnames, 1)."\n"; //debug
			if(odbc_exec($client->get_connection(), "ALTER TABLE ".$table_name." DROP COLUMN ".odbc_result($colnames, 1).";") === false) {
				$rollback_needed = true;
				$rollback_error_message = get_odbc_error();
				break;
			}
			//ALTER TABLE hr.admin_emp DROP (bonus, commission); //two in one
		} else {
			//ALTER TABLE hr.admin_emp RENAME COLUMN comm TO commission;
			$new_name = $_POST["column_name"][$idx];
			if(strcasecmp($new_name, odbc_result($colnames, 1)) != 0) {
				//echo "rename column ".odbc_result($colnames, 1)." into ".$new_name."\n"; //debug
				if(odbc_exec($client->get_connection(), "ALTER TABLE ".$table_name." RENAME COLUMN ".odbc_result($colnames, 1)." TO ".$new_name) === false) {
					$rollback_needed = true;
					$rollback_error_message = "ALTER TABLE ".$table_name." RENAME COLUMN ".odbc_result($colnames, 1)." TO ".$new_name."\n".get_odbc_error();
					break;
				}
			}

			//now using $new_name
			//ALTER TABLE customer MODIFY cust_name varchar2(100) not null;
			$old_full_type = compile_type_name(odbc_result($colnames, 2), odbc_result($colnames, 3), odbc_result($colnames, 4));
			$new_full_type = compile_type_name($_POST["column_type"][$idx], $_POST["column_precision"][$idx], $_POST["column_length"][$idx]);
			//echo "old vs new type: ".$old_full_type." ".$new_full_type."\n"; //debug
			if($old_full_type === false || $new_full_type === false) {
				$rollback_needed = true;
				$rollback_error_message = "Unable to determine type for column ".$new_name.".";
				break;
			}

			if(strcasecmp($old_full_type, $new_full_type) != 0) {
				//echo "changing type of ".$new_name."\n"; //debug
				if(odbc_exec($client->get_connection(), "ALTER TABLE ".$table_name." MODIFY ".$new_name." ".$new_full_type.";") === false) {
					$rollback_needed = true;
					$rollback_error_message = get_odbc_error();
					break;
				}
			}

			$was_not_null = (odbc_result($colnames, 5) == "N");
			$is_not_null = (isset($_POST["column_not_null"]) && isset($_POST["column_not_null"][$idx]) && $_POST["column_not_null"][$idx] == "true");
			if($was_not_null != $is_not_null) {
				//echo "changing NOT NULL of ".$new_name."\n"; //debug
				if($is_not_null) {
					if(odbc_exec($client->get_connection(), "ALTER TABLE ".$table_name." MODIFY ".$new_name." default '' NOT NULL;") === false) {
						$rollback_needed = true;
						$rollback_error_message = get_odbc_error();
						break;
					}
				} else {
					if(odbc_exec($client->get_connection(), "ALTER TABLE ".$table_name." MODIFY ".$new_name." NULL;") === false) {
						$rollback_needed = true;
						$rollback_error_message = get_odbc_error();
						break;
					}
				}
			}

			$old_constraint = odbc_result($colnames, 6);
			$new_constraint = '';
			if(isset($_POST["column_primary"]) && isset($_POST["column_primary"][$idx]) && $_POST["column_primary"][$idx]=="true")
				$new_constraint = 'P';
			else if(isset($_POST["column_unique"]) && isset($_POST["column_unique"][$idx]) && $_POST["column_unique"][$idx]=="true")
				$new_constraint = 'U';

			if(strcasecmp($old_constraint, $new_constraint) != 0) {
				//drop old
				if(strcasecmp($old_constraint, 'P')==0)
					$drop_primary_key = true;

				if(strcasecmp($old_constraint, 'U')==0) {
					$q = "ALTER TABLE " . $table_name . " DROP UNIQUE (" . $new_name . ");";
					if(odbc_exec($client->get_connection(), $q) === false) {
						$rollback_needed = true;
						$rollback_error_message = $new_name."\n".get_odbc_error();
						break;
					}
				}

				//modify new
				if(strcasecmp($new_constraint, 'P')==0) //primary key (because it can be complex) is handled down there
					$drop_primary_key = true;

				if(strcasecmp($new_constraint, 'U')==0) { //unique must be changed after primary key is dropped and set
					$make_unique_fields_list[] = $new_name;
					/*
					if($make_unique_fields_list == "")
						$make_unique_fields_list = $new_name;
					else
						$make_unique_fields_list .= ", ".$new_name;
					*/
				}
			}
		}

		$idx += 1;
	}

	//add new fields if there are any
	if($rollback_needed === false) {
		for ($i = $idx; $i <= $fields_count; ++$i) {
			if(!isset($_POST["column_name"]) || !isset($_POST["column_name"][$i]) || $_POST["column_name"][$i] == "")
				continue;

			if(!isset($_POST["column_type"]) || !isset($_POST["column_type"][$i]) || !in_array($_POST["column_type"][$i], $types_arr))
				die("false: bad column_type:\n" . $_POST["column_type"][$i] . " not in " . var_export($types_arr));

			//TODO: test column_name is one word or something

			$full_type =  $_POST["column_type"][$i];

			$add_precision = (in_array($_POST["column_type"][$i], $has_precision));
			$add_length = (array_key_exists($_POST["column_type"][$i], $has_length));

			if($add_precision && (!isset($_POST["column_precision"]) || !isset($_POST["column_precision"][$i])))
				die("false"); //TODO test it's number
			if($add_length && (!isset($_POST["column_length"]) || !isset($_POST["column_length"][$i])))
				die("false"); //TODO test it's number & <=max value

			if($add_length && $add_precision) {
				$full_type .= "(" . $_POST["column_precision"][$i] . "," . $_POST["column_length"][$i] . ")";
			} else if($add_length) {
				$full_type .= "(" . $_POST["column_length"][$i] . ")";
			} else if($add_precision) {
				$full_type .= "(" . $_POST["column_precision"][$i] . ")";
			} //TODO length and precision here are not sanitized

			$query = "ALTER TABLE ".$table_name." ADD (".totally_escape($_POST["column_name"][$i])." ".$full_type;
			if(isset($_POST["column_not_null"]) && isset($_POST["column_not_null"][$i]) && $_POST["column_not_null"][$i] == "true")
				$query .= " DEFAULT '' NOT NULL";

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

			$query .= ");";
			if(odbc_exec($client->get_connection(), $query) === false) {
				$rollback_needed = true;
				$rollback_error_message = get_odbc_error();
				break;
			}
		}
	}

	//add complex primary key
	if($rollback_needed === false) {
		if($drop_primary_key) {
			if(odbc_exec($client->get_connection(), "ALTER TABLE ".$table_name." DROP PRIMARY KEY;") === false) {
				$rollback_needed = true;
				$rollback_error_message = get_odbc_error();
			}
		}
	}

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

		if($drop_primary_key && $fields_list != "") {
			if(odbc_exec($client->get_connection(), "ALTER TABLE ".$table_name." ADD CONSTRAINT ".$table_name."_primary_key_constraint PRIMARY KEY (".$fields_list.");") === false) {
				$rollback_needed = true;
				$rollback_error_message = $fields_list."\n".get_odbc_error();
			}
		}
	}

	//
	if($rollback_needed === false) {
		/*
		if($make_unique_fields_list != "") {
			if(odbc_exec($client->get_connection(), "ALTER TABLE " . $table_name . " MODIFY UNIQUE (" . $make_unique_fields_list . ") UNIQUE ENABLE;") === false) {
				$rollback_needed = true;
				$rollback_error_message = $make_unique_fields_list."\n".get_odbc_error();
			}
		}
		*/

		foreach ($make_unique_fields_list as $field_name) {
			if(odbc_exec($client->get_connection(), "ALTER TABLE " . $table_name . " MODIFY " . $field_name . " UNIQUE;") === false) {
				$rollback_needed = true;
				$rollback_error_message = $field_name."\n".get_odbc_error();
				break;
			}
		}
	}


	//check if rollback needed
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