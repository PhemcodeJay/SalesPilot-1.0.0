<?php
$hostname = "localhost";
$username = "root";
$password = "kokochulo@1987#";
$database = "sales_pilot";


$connection = mysqli_connect($hostname, $username, $password, $database);

if (!$connection) {
    exit("Error: " . mysqli_connect_error());
}