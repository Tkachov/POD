<div class="login_form">
	<div id="use_dsn">
		<form action="" method="post">
			<input type="text" name="database" placeholder="DSN"/>
			<input type="text" name="username" placeholder="username"/>
			<input type="password" name="password" placeholder="********"/>
			<input type="submit" value="Connect"/>
		</form>
		<a href="javascript:toggle_form();">I know IP and SID</a>
	</div>
	<div id="use_ip">
		<form action="" method="post">
			<input type="text" name="IP" placeholder="IP:port"/>
			<input type="text" name="database" placeholder="database SID"/>
			<input type="text" name="username" placeholder="username"/>
			<input type="password" name="password" placeholder="********"/>
			<input type="submit" value="Connect"/>
		</form>
		<a href="javascript:toggle_form();">I know DSN</a>
	</div>
	<script>
		function toggle_form() {
			var e2 = document.getElementById("use_dsn");
			var e1 = document.getElementById("use_ip");
			if(e1.style.display == "block") {
				e1.style.display = "none";
				e2.style.display = "block";
			} else {
				e1.style.display = "block";
				e2.style.display = "none";
			}
		}
	</script>

	<?php
	if($client->login_error_occurred()) {
		echo "<div class='error_message'>".$client->get_login_error_message()."</div>";
	}
	?>
</div>