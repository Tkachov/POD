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