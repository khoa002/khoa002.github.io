<?php
$conn_parsed = parse_url($_ENV["DATABASE_URL"]);

$dsn = "pgsql:"
     . "host={$conn_parsed["host"]};"
     . "dbname=" . substr($conn_parsed["path"], 1) . ";"
     . "user={$conn_parsed["user"]};"
     . "port={$conn_parsed["port"]};"
     . "password={$conn_parsed["pass"]};"
     . "sslmode=require";
// die(var_dump($dsn));
$db = new PDO($dsn);
die(var_dump($db));
// $pg_conn = pg_connect(pg_connection_string_from_database_url());
// $result = pg_query($pg_conn, "SELECT relname FROM pg_stat_user_tables WHERE schemaname='public'");
// print "<pre>\n";
// if (!pg_num_rows($result)) {
//     print("Your connection is working, but your database is empty.\nFret not. This is expected for new apps.\n");
// } else {
//     print "Tables in your database:\n";
//     while ($row = pg_fetch_row($result)) { print("- $row[0]\n"); }
// }
// print "\n";
?>