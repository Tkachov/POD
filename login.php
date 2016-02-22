<?php
    function login($DSN, $user, $pass) {
        //returns:
        //  {false, "error message"} on fail
        //  {true, $connect} on success

        $connect = odbc_connect($DSN, $user, $pass);
        if($connect === false) {
            return array(false, odbc_error().": ".odbc_errormsg());
        } else {
            return array(true, $connect);
        }
    }

    function pack_cookie($cookie_value) {
        if($cookie_value === null || $cookie_value == "")
            return "0";
        return $cookie_value;
    }

    function unpack_cookie($cookie_value) {
        if($cookie_value == "0")
            return "";
        return $cookie_value;
    }
?>