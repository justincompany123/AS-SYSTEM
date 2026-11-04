
<?php

require_once __DIR__ . '/config/database.php';

try {
    $result = $pdo->query("SELECT version()")->fetch();

    echo "Successfully connected to Supabase!";
    echo "<br>";
    echo "PostgreSQL database is working.";

} catch (PDOException $e) {
    echo "Database test failed.";
}
