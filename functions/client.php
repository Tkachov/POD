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
		$connect = oci_connect($login_info->user, $login_info->pass, $login_info->DSN);
		if($connect === false) {
			$this->login_error_message = oci_error()["code"] . ": " . oci_error()["message"];
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
		if(setcookie("login_DSN", client_login_info::pack_cookie($this->DSN), 0, "/") === false
		|| setcookie("login_user", client_login_info::pack_cookie($this->user), 0, "/") === false
		|| setcookie("login_pass", client_login_info::pack_cookie($this->pass), 0, "/") === false) {
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
		if($_POST) return new client_login_info($_POST["database"], $_POST["username"], $_POST["password"]);
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