<?php
	include "client.php";
	$client = new client();
?>
<!doctype html>
<html xmlns="http://www.w3.org/1999/html">
	<head>
		<title>POD</title>
		<meta charset="utf-8"/>
		<link rel="stylesheet" type="text/css" href="style.css"/>
	</head>
	<body>
		<?php
			if($client->logged_in()) {
				include "modules/panel.php";
			} else {
				include "modules/login_form.php";
			}
		?>
	</body>
</html>