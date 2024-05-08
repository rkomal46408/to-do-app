<?php
// Setting up the database connection
$host = 'sql213.infinityfree.com';  // Your database host
$dbname = 'if0_36485857_todo';  // Your database name, ensure this matches exactly with what's listed in your hosting control panel
$username = 'if0_36485857';  // Your database username
$password = 'dxLiJrVmOOi';  // Your database password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    //echo "Connected successfully";
} catch (PDOException $e) {
    die("Could not connect to the database $dbname :" . $e->getMessage());
}
?>
