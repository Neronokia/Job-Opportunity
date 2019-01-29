<?php
$servername = "localhost";
$username = "id8585127_nerijus";
$password = "nerijus45";
$dbname = "id8585127_stats";


$db = new mysqli($servername, $username, $password, $dbname);

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}