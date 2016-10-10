<?php
if (strpos($_SERVER["HTTP_HOST"], "localhost") !== FALSE) {
    $dbc_parts = [
        "scheme" => "mysql",
        "host" => "localhost",
        "dbname" => "khoa002_test_db",
        "user" => "root",
        "pass" => "",
        "port" => "3306"
    ];
} else {
    $dbc_parts = parse_url($_ENV["JAWSDB_URL"]);
    $dbc_parts["dbname"] = substr($dbc_parts["path"], 1);
}

$dbh = new PDO("mysql:host={$dbc_parts["host"]};port={$dbc_parts["port"]};dbname={$dbc_parts["dbname"]}", $dbc_parts["user"], $dbc_parts["pass"]);
die(var_dump($dbh));
?>