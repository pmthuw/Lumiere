<?php
// Database configuration
$envHost = getenv('DB_HOST');
$envPort = getenv('DB_PORT');
$envDb = getenv('DB_NAME');
$envUser = getenv('DB_USER');
$envPassword = getenv('DB_PASSWORD');

$hostCandidates = array_values(array_unique(array_filter([
    $envHost !== false ? trim((string)$envHost) : '',
    '127.0.0.1',
    'localhost',
])));

$portCandidates = [];
if ($envPort !== false && trim((string)$envPort) !== '') {
    $portCandidates[] = (int)$envPort;
}
$portCandidates[] = 3306;
$portCandidates[] = 3307;
$portCandidates = array_values(array_unique(array_filter($portCandidates, static fn($p) => $p > 0)));

$dbCandidates = array_values(array_unique(array_filter([
    $envDb !== false ? trim((string)$envDb) : '',
    'lumiere',
    'perfume_store',
])));

$usernameCandidates = array_values(array_unique(array_filter([
    $envUser !== false ? trim((string)$envUser) : '',
    'root',
])));

$passwordCandidates = array_values(array_unique([
    $envPassword !== false ? (string)$envPassword : '',
    '',
]));

$pdo = null;
$lastError = 'Unknown database connection error.';

foreach ($hostCandidates as $host) {
    foreach ($portCandidates as $port) {
        foreach ($dbCandidates as $dbname) {
            foreach ($usernameCandidates as $username) {
                foreach ($passwordCandidates as $password) {
                    try {
                        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
                        $pdo = new PDO($dsn, $username, $password);
                        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                        break 5;
                    } catch (PDOException $e) {
                        $lastError = $e->getMessage();
                    }
                }
            }
        }
    }
}

if (!($pdo instanceof PDO)) {
    die('Database connection failed: ' . $lastError);
}
?>