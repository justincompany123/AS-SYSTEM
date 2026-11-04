
<?php

// ============================================
// AS-SYSTEM - SUPABASE DATABASE CONNECTION
// PostgreSQL / Session Pooler
// ============================================

$host = "aws-0-ap-northeast-1.pooler.supabase.com";
$port = "5432";
$dbname = "postgres";

// Copy the complete username from Supabase
$username = "YOUR_COMPLETE_SUPABASE_USERNAME";

// Enter your actual Supabase database password
$password = "YOUR_SUPABASE_DATABASE_PASSWORD";

try {

    $pdo = new PDO(
        "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

} catch (PDOException $e) {

    error_log("Supabase connection error: " . $e->getMessage());

    die("Unable to connect to the database. Please try again later.");

}
