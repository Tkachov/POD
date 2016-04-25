<?php

class client {
	private $logged_in;
	private $connection;
	private $login_error_message;

	public function client() {
		$this->logged_in = false;
		$this->connection = null;
		$this->login_error_message = null;

		$login_info = client_login_info::create_info_from_cookies();
		if($login_info === null) $login_info = client_login_info::create_info_from_post();
		if($login_info !== null) $this->login($login_info);
	}

	private function login(client_login_info $login_info) {
		//TODO: odbc connect probably supports these DSNs: http://www.connectionstrings.com/oracle/
		//may be something like "Data Source=(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=MyHost)(PORT=MyPort))(CONNECT_DATA=(SERVICE_NAME=MyOracleSID)));User Id=myUsername;Password=myPassword;"

		$connect = odbc_connect($login_info->DSN, $login_info->user, $login_info->pass);
		if($connect === false) {
			$this->login_error_message = odbc_error() . ": " . odbc_errormsg();
		} else {
			$this->logged_in = true;
			$this->connection = $connect;
		}

		if($this->logged_in) $login_info->save_in_cookies();
	}

	public function logged_in() { return $this->logged_in; }
	public function login_error_occurred() { return ($this->login_error_message !== null); }

	public function get_connection() { return $this->connection; }
	public function get_login_error_message() { return $this->login_error_message; }
}

class client_login_info {
	var $DSN, $user, $pass;

	function client_login_info($DSN, $user, $pass) {
		$this->DSN = $DSN;
		$this->user = $user;
		$this->pass = $pass;
	}

	function save_in_cookies() {
		if(setcookie("login_DSN", client_login_info::pack_cookie($this->DSN)) === false
		|| setcookie("login_user", client_login_info::pack_cookie($this->user)) === false
		|| setcookie("login_pass", client_login_info::pack_cookie($this->pass)) === false) {
			//TODO: something was before Cookie: header
			echo "<h1>WARNING SOMETHING WRONG WITH COOKIES</h1>";
		}
	}

	static function create_info_from_cookies() {
		if(isset($_COOKIE["login_DSN"]) && isset($_COOKIE["login_user"]) && isset($_COOKIE["login_pass"])) {
			return new client_login_info(
				client_login_info::unpack_cookie($_COOKIE["login_DSN"]),
				client_login_info::unpack_cookie($_COOKIE["login_user"]),
				client_login_info::unpack_cookie($_COOKIE["login_pass"])
			);
		}

		return null;
	}

	static function create_info_from_post() {
		if($_POST) {
			if(isset($_POST["IP"])) {
				$idx = strpos($_POST["IP"], ":");
				if($idx === false) {
					$IP = $_POST["IP"];
					$port = "1521";
				} else {
					$IP = strpos($_POST["IP"], 0, $idx);
					$port = strpos($_POST["IP"], $idx+1);
				}
				$DSN = "DRIVER={Oracle 12g ODBC driver};DSN=(DESCRIPTION=(ADDRESS_LIST=(ADDRESS=(PROTOCOL=TCP)(HOST=".$IP.")(PORT=".$port.")))(CONNECT_DATA=(SID=".$_POST["database"].")));UserID=".$_POST["username"].";Password=".$_POST["password"].";";
				return new client_login_info($DSN, $_POST["username"], $_POST["password"]);
			}

			return new client_login_info($_POST["database"], $_POST["username"], $_POST["password"]);
		}

		return null;
	}

	static function pack_cookie($cookie_value) {
		if($cookie_value === null || $cookie_value == "")
			return "0";
		return $cookie_value;
	}

	static function unpack_cookie($cookie_value) {
		if($cookie_value == "0")
			return "";
		return $cookie_value;
	}
}

?>