<?php
	include_once "functions/client.php";
	$client = new client();
?>
<!doctype html>
<html xmlns="http://www.w3.org/1999/html">
	<head>
		<title>POD</title>
		<meta charset="utf-8"/>
		<link rel="stylesheet" type="text/css" href="design/style.css"/>
	</head>
	<body>
		<?php
			if($client->logged_in()) {
				include_once "functions/results_table.php";
				include_once "functions/utils.php";
				include "modules/panel.php";
			} else {
				include "modules/login_form.php";
			}
		?>
	</body>
</html>