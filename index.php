<?php
	include "login.php";

	$logged_in = false;
	$connect = null;

	$dsn = null;
	$user = null;
	$pass = null;
	$login_error_message = null;

	if(isset($_COOKIE["login_DSN"]) && isset($_COOKIE["login_user"]) && isset($_COOKIE["login_pass"])) {
		$dsn = unpack_cookie($_COOKIE["login_DSN"]);
		$user = unpack_cookie($_COOKIE["login_user"]);
		$pass = unpack_cookie($_COOKIE["login_pass"]);
	} else {
		if($_POST) {
			$dsn = $_POST["database"];
			$user = $_POST["username"];
			$pass = $_POST["password"];
		}
	}

	if($dsn !==null && $user !== null && $pass !== null) {
		$result = login($dsn, $user, $pass);
		if ($result[0]) {
			$logged_in = true;
			$connect = $result[1];
		} else {
			//we may use $result[1] as error message here
			$login_error_message = $result[1];
		}

		if ($logged_in) {
			if (setcookie("login_DSN", pack_cookie($dsn)) === false
			|| setcookie("login_user", pack_cookie($user)) === false
			|| setcookie("login_pass", pack_cookie($pass)) === false) {
				//TODO: something was before Cookie: header
				echo "<h1>WARNING SOMETHING WRONG WITH COOKIES</h1>";
			}
		}
	}

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
			if($logged_in) {
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
						if($connect !== false) {
							$query = $_POST["query"];

							echo "<pre class='executed_query'>".htmlspecialchars($query)."</pre>";

							echo "<div class='query_results'>";

							$result = odbc_exec($connect, $query);
							while(odbc_fetch_row($result)) {
								$fields_count = odbc_num_fields($result);
								for($i=1; $i<=$fields_count; ++$i) {
									echo "<b>".odbc_result($result, $i)."</b> ";
								}
								echo "<br/>";
							}

							echo "</div>";

							odbc_close($connect);
						}
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
						if($login_error_message !== null) {
							echo "<div class='error_message'>".$login_error_message."</div>";
						}
					?>
				</div>
		<?php
			}
		?>
	</body>
</html>