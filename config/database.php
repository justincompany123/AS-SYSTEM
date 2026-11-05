
<?php

// =====================================================
// AS-SYSTEM
// SUPABASE POSTGRESQL DATABASE CONNECTION
// =====================================================

// Read environment variables from Render
$host = getenv('DB_HOST')?: 'aws-0-ap-northeast-1.pooler.supabase.com';
$port = getenv('DB_PORT') ?: '5432';
$dbname = getenv('DB_NAME') ?: 'postgres';
$username = getenv('DB_USER')?: 'postgres.qqgyuovtkihvyxzylehc';
$password = getenv('DB_PASSWORD')?: 'justin09157365895';

// Check required configuration
if (!$host || !$username || !$password) {
    error_log(
        'AS-SYSTEM DATABASE ERROR: Missing DB_HOST, DB_USER, or DB_PASSWORD environment variable.'
    );

    die('Database configuration is incomplete.');
}

// Check PostgreSQL PDO extension
if (!in_array('pgsql', PDO::getAvailableDrivers(), true)) {
    error_log(
        'AS-SYSTEM DATABASE ERROR: PDO PostgreSQL driver is not installed.'
    );

    die('Database driver is unavailable.');
}

try {

    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode=require";

    $pdo = new PDO(
        $dsn,
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

    // Keep detailed errors in Render logs, not on the public website
    error_log(
        'AS-SYSTEM SUPABASE CONNECTION ERROR: ' . $e->getMessage()
    );

    die('Unable to connect to the database.');
}
