<?php
$host = getenv('DB_HOST') ?: '127.0.0.1';
$user = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD');
if ($password === false || $password === null) {
    $password = getenv('DB_PASS') ?: '';
}

$envDbName = getenv('DB_NAME');
$dbCandidates = array_values(array_unique(array_filter([
    $envDbName !== false ? trim((string)$envDbName) : '',
    'perfume_store',
    'lumiere',
])));

$envPort = getenv('DB_PORT');
$portCandidates = [];
if ($envPort !== false && $envPort !== null && trim((string)$envPort) !== '') {
    $portCandidates[] = (int)$envPort;
}
$portCandidates[] = 3307;
$portCandidates[] = 3306;
$portCandidates = array_values(array_unique(array_filter($portCandidates, static fn($p) => $p > 0)));

$pdo = null;
$setupDbError = null;

foreach ($dbCandidates as $dbname) {
    foreach ($portCandidates as $port) {
        try {
            $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $setupDbError = null;
            break 2;
        } catch (PDOException $e) {
            $setupDbError = $e->getMessage();
        }
    }
}

if ($pdo instanceof PDO) {
    // Default categories
    $defaultCategories = [
        ['name' => 'Nữ', 'description' => 'Sản phẩm dành cho nữ'],
        ['name' => 'Nam', 'description' => 'Sản phẩm dành cho nam'],
        ['name' => 'Unisex', 'description' => 'Sản phẩm unisex'],
        ['name' => 'Limited', 'description' => 'Sản phẩm limited edition']
    ];

    // Create categories table if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        description TEXT,
        status ENUM('active','hidden') NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $hasCategoryStatus = (bool)$pdo->query("SHOW COLUMNS FROM categories LIKE 'status'")->fetch(PDO::FETCH_ASSOC);
    if (!$hasCategoryStatus) {
        $pdo->exec("ALTER TABLE categories ADD COLUMN status ENUM('active','hidden') NOT NULL DEFAULT 'active' AFTER description");
    }

    // Insert default categories if table is empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM categories");
    if ($stmt->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
        foreach ($defaultCategories as $cat) {
            $stmt->execute([$cat['name'], $cat['description']]);
        }
    }
}
?>