<form action="" method="post" class="query_form" id="query_form">
	<textarea name="query" placeholder="SELECT * FROM table"></textarea>
	<a href="javascript:document.forms['query_form'].submit();" class="button">Execute query</a>
</form>

<?php
	if($_POST && isset($_POST["query"])) {
		$query = $_POST["query"];
		echo "<pre class='executed_query'>".htmlspecialchars($query)."</pre>";

		$result = oci_parse($client->get_connection(), $query);
		if($result === false || oci_execute($result) === false) $result = false;
		make_results_table($result, false, null);
	}
?>