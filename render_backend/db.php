<?php
$databaseUrl = getenv("DATABASE_URL");

if (!$databaseUrl) {
    http_response_code(503);
    exit('Service temporarily unavailable. Please try again later.');
}

$url = parse_url($databaseUrl);

$host = $url["host"];
$port = $url["port"] ?? 5432;
$dbname = ltrim($url["path"], "/");
$user = $url["user"];
$pass = $url["pass"];

$query = [];
if (isset($url["query"])) {
    parse_str($url["query"], $query);
}

$sslmode = $query["sslmode"] ?? "require";

$dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=$sslmode";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    http_response_code(503);
    exit('Service temporarily unavailable. Please try again later.');
}
?>
