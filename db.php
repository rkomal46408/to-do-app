<?php
// Setting up the database connection
$host = 'localhost';  // Your database host
$dbname = 'u740934665_todo';  // Your database name
$username = 'u740934665_todo';  // Your database username
$password = '@Todo321';  // Your database password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Could not connect to the database $dbname :" . $e->getMessage());
}
?>
