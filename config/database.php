<?php

$host = "sql308.infinityfree.com";
$dbname = "if0_43333198_as_system_db";
$username = "if0_43333198";
$password = "your_infinityfree_password";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

    $pdo->setAttribute(
        PDO::ATTR_DEFAULT_FETCH_MODE,
        PDO::FETCH_ASSOC
    );

} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Unable to connect to the database. Please try again later.");
}