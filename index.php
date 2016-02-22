<?php
	include "client.php";
	$client = new client();

	//do something depending on $logged_in
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
		?>
				<div class="container">
					<div class="panel">
						<div>
							<a href="">Index</a> <!-- TODO -->
							<a href="javascript:logout();" class="right">Logout</a>
						</div>
					</div>
				</div>

				<script>
					function delete_cookie( name ) {
						document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:01 GMT;';
					}

					function logout() {
						delete_cookie("login_DSN");
						delete_cookie("login_user");
						delete_cookie("login_pass");
						document.location = "";//.reload(true);
					}
				</script>

				<div class="content">
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
				</div>
		<?php
			} else {
		?>
				<div class="login_form">
					<form action="" method="post">
						<input type="text" name="database" placeholder="database"/>
						<input type="text" name="username" placeholder="username"/>
						<input type="password" name="password" placeholder="********"/>
						<input type="submit" value="Connect"/>
					</form>

					<?php
						if($client->login_error_occurred()) {
							echo "<div class='error_message'>".$client->get_login_error_message()."</div>";
						}
					?>
				</div>
		<?php
			}
		?>
	</body>
</html>