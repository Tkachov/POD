<?php
	$tabs = array("tables" => "Tables", "compose_report" => "Compose report", "execute_query" => "Execute query");

	$tab = key($tabs);
	$action = "list";
	$target = "";
	$rowid = "";

	foreach($_GET as $k => $v) {
		$k = totally_escape($k);

		if($k === "action")
			$action = totally_escape($v);
		else if($k === "target")
			$target = totally_escape($v);
		else if($k === "rowid")
			$rowid = totally_escape($v);
		else {
			foreach ($tabs as $tab_name => $tab_title)
				if($k === $tab_name) $tab = $k;
		}
	}

	$found = false;
	foreach($tabs as $tab_name => $tab_title)
		if($tab == $tab_name) {
			$found = true;
			break;
		}

	if(!$found) $tab = key($tabs);
?>

<div class="container">
	<div class="panel">
		<div>
			<?php
				foreach($tabs as $tab_name => $tab_title)
					echo "<a href=\"?".$tab_name."\" class=\"panel_link".($tab==$tab_name?" selected":"")."\">".$tab_title."</a>\n";
			?>
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
	<?php @include_once($tab.".php"); ?>
</div>