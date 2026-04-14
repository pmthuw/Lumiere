<?php
$envHost = getenv('DB_HOST');
$envUser = getenv('DB_USER');
$envPassword = getenv('DB_PASSWORD');
$envDbName = getenv('DB_NAME');
$envPort = getenv('DB_PORT');

$hostCandidates = array_values(array_unique(array_filter([
    $envHost !== false ? trim((string)$envHost) : '',
    '127.0.0.1',
    'localhost',
])));

$userCandidates = array_values(array_unique(array_filter([
    $envUser !== false ? trim((string)$envUser) : '',
    'root',
])));

$passwordCandidates = array_values(array_unique([
    $envPassword !== false ? (string)$envPassword : '',
    '',
]));

$dbCandidates = array_values(array_unique(array_filter([
    $envDbName !== false ? trim((string)$envDbName) : '',
    'lumiere',
    'perfume_store',
])));

$portCandidates = [];
if ($envPort !== false && $envPort !== null && trim((string)$envPort) !== '') {
    $portCandidates[] = (int)$envPort;
}
$portCandidates[] = 3306;
$portCandidates[] = 3307;
$portCandidates = array_values(array_unique(array_filter($portCandidates, static fn($p) => $p > 0)));

$pdo = null;
$setupDbError = null;

foreach ($hostCandidates as $host) {
    foreach ($portCandidates as $port) {
        foreach ($dbCandidates as $dbname) {
            foreach ($userCandidates as $user) {
                foreach ($passwordCandidates as $password) {
                    try {
                        $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $password);
                        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                        $setupDbError = null;
                        break 5;
                    } catch (PDOException $e) {
                        $setupDbError = $e->getMessage();
                    }
                }
            }
        }
    }
}

if ($pdo instanceof PDO) {
    // Default categories
    $defaultCategories = [
        ['name' => 'Nữ', 'description' => 'Sản phẩm dành cho nữ'],
        ['name' => 'Nam', 'description' => 'Sản phẩm dành cho nam'],
        ['name' => 'Giới hạn', 'description' => 'Sản phẩm phiên bản giới hạn']
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

    $pdo->exec("UPDATE categories SET name = 'Giới hạn', description = 'Sản phẩm phiên bản giới hạn' WHERE name = 'Limited'");
    $pdo->exec("UPDATE products SET category = 'Giới hạn' WHERE category = 'Limited'");
    $pdo->exec("UPDATE products SET badge = 'Giới hạn' WHERE badge = 'Limited'");
}
?>