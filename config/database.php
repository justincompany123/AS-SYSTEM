
<?php

// =================================================
// ALTAR SERVERS MANAGEMENT SYSTEM
// SUPABASE POSTGRESQL CONNECTION
// =================================================

// SUPABASE SESSION POOLER

$host = "aws-0-ap-northeast-1.pooler.supabase.com";
$port = "5432";
$dbname = "postgres";

// Supabase database username
$username = "postgres.qqgyuovtkihvyxzylehc";

// Your actual Supabase database password
$password = "090931198455justin";

try {

    $pdo = new PDO(
        "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 15
        ]
    );

} catch (PDOException $e) {

    error_log(
        "SUPABASE CONNECTION ERROR: " . $e->getMessage()
    );

    if (
        isset($_SERVER['SERVER_NAME']) &&
        $_SERVER['SERVER_NAME'] === 'localhost'
    ) {
        die(
            "Database connection failed: " .
            htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
        );
    }

    die("Unable to connect to the database.");
}
