<!doctype html>
<html>
	<head>
		<title>POD</title>
		<meta charset="utf-8"/>
		<link rel="stylesheet" type="text/css" href="style.css"/>
		<style>
			.query_form {
				width: 80%;
				margin: 0 auto;
				padding: 0;
			}

			.query_form > input {
				width: 150px;
				padding: 2px;
				margin: 2px;				
			}

			.query_form > input[type="submit"] {
				width: 100px;
			}

			.query_form > input[name="query"] {
				width: calc(100% - 2 * 5px - 3 * 150px - 3 * 2 * 5px - 100px - 2 * 7px - 6px);
			}
		</style>
	</head>
	<body>
		<div class="container">
			<div class="panel">
				<div>
					<form action="" method="post" class="query_form">
						<input type="text" name="database" placeholder="database"/>
						<input type="text" name="username" placeholder="username"/>
						<input type="password" name="password" placeholder="********"/>
						<input type="text" name="query" placeholder="SELECT * FROM table;"/>
						<input type="submit" value="Execute query"/>
					</form>
				</div>
			</div>
		</div>

		<div class="content">
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
		</div>
	</body>
</html>