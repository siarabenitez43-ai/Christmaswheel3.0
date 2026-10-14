<?php
// db.php — mysqli connection (connects to xmas_wheel)

$host="localhost";
$port=3306;
$socket="";
$user="root";
$password="";
$dbname="xmas_wheel";

$con = new mysqli($host, $user, $password, $dbname, $port, $socket);
if ($con->connect_error) {
    die('Database connection failed: ' . $con->connect_error);
}
