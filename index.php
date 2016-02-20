<!doctype html>
<html>
	<head>
		<title>POD</title>
		<meta charset="utf-8"/>
		<link rel="stylesheet" type="text/css" href="style.css"/>
		<style>
			.query_form {
				width: 80%;
				margin: auto auto;
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

			.panel {
				height: 40pt;
			}

			.content {
				padding-top: 50pt;
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
				if($_POST) {					
					$connect = odbc_connect($_POST["database"], $_POST["username"], $_POST["password"]);
					if($connect === false) {
						echo "<p>failed to connect</p>";
						echo "<p>".odbc_error().": ".odbc_errormsg()."</p>";
					} else {						
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
					}
				}
			?>
		</div>
	</body>
</html>