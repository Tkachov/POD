<?php
	$connect = odbc_connect($_POST["database"], $_POST["username"], $_POST["password"]);
	$query = $_POST["query"];

	$result = odbc_exec($connect, $query);
	while(odbc_fetch_row($result)) {
		$fields_count = odbc_num_fields($result);
		for($i=1; $i<=$fields_count; ++$i) {
			echo "<b>".odbc_result($result, $i)."</b> ";
		}
		echo "<br/>";
	}

	odbc_close($connect);
?>
