<form action="" method="post" class="query_form" id="query_form">
	<textarea name="query" placeholder="SELECT * FROM table;"></textarea>
	<a href="javascript:document.forms['query_form'].submit();" class="button">Execute query</a>
</form>

<?php
if($_POST && isset($_POST["query"])) {
	//TODO: odbc connect probably supports these DSNs: http://www.connectionstrings.com/oracle/
	//may be something like "Data Source=(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=MyHost)(PORT=MyPort))(CONNECT_DATA=(SERVICE_NAME=MyOracleSID)));User Id=myUsername;Password=myPassword;"

	$query = $_POST["query"];

	echo "<pre class='executed_query'>".htmlspecialchars($query)."</pre>";

	echo "<div class='query_results'>";

	$result = odbc_exec($client->get_connection(), $query);
	if($result === false) echo "failed | ".odbc_error() . ": " . odbc_errormsg();

	//if($first) {
		$fields_count = odbc_num_fields($result);
		for($i=1; $i<=$fields_count; ++$i) {
			echo "<b>".odbc_field_name($result, $i)."</b> ";
		}
		echo "<br/><hr/>";
	///}

	while(odbc_fetch_row($result)) {
		$fields_count = odbc_num_fields($result);
		for($i=1; $i<=$fields_count; ++$i) {
			echo "<b>".odbc_result($result, $i)."</b> ";
		}
		echo "<br/>";
	}

	echo "</div>";

	odbc_close($client->get_connection());
}
?>