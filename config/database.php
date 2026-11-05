<?php

// =================================================
// ALTAR SERVERS MANAGEMENT SYSTEM
// SUPABASE POSTGRESQL CONNECTION
// =================================================

$host = getenv('DB_HOST') ?: 'aws-0-ap-northeast-1.pooler.supabase.com';
$port = getenv('DB_PORT') ?: '5432';
$dbname = getenv('DB_NAME') ?: 'postgres';
$username = getenv('DB_USER') ?: 'postgres.qqgyuovtkihvyxzylehc';
$password = getenv('090931198455justin');

if (!$password) {
    error_log("Database password is not configured.");
    die("Unable to connect to the database.");
}

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

    error_log("SUPABASE CONNECTION ERROR: " . $e->getMessage());

    die("Unable to connect to the database.");
}
