<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Database configuration
$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = (int)(getenv('DB_PORT') ?: 3307);
$user = 'root';
$password = '';
$dbname = 'perfume_store';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

$hasStatusColumn = false;
try {
    $statusCheckStmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'status'");
    $hasStatusColumn = (bool)$statusCheckStmt->fetch(PDO::FETCH_ASSOC);
    if (!$hasStatusColumn) {
        $pdo->exec("ALTER TABLE products ADD COLUMN status ENUM('active','hidden') NOT NULL DEFAULT 'active'");
        $hasStatusColumn = true;
    }
} catch (Throwable $e) {
    $hasStatusColumn = false;
}

function apiImageUrl(?string $image): string
{
    $image = trim((string)$image);
    if ($image === '') {
        return '';
    }

    if (preg_match('/^(https?:)?\\/\\//i', $image) || str_starts_with($image, 'data:') || str_starts_with($image, '/')) {
        return $image;
    }

    if (str_starts_with($image, 'frontend/images/')) {
        return '/' . $image;
    }

    if (str_starts_with($image, 'images/')) {
        return '/frontend/' . $image;
    }

    return '/' . ltrim($image, '/');
}

// Get all products
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        if ($hasStatusColumn) {
            $stmt = $pdo->query("SELECT * FROM products WHERE status = 'active' ORDER BY id");
        } else {
            $stmt = $pdo->query("SELECT * FROM products ORDER BY id");
        }
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Transform data to match frontend format
        $transformed = array_map(function($product) {
            return [
                'id' => (int)$product['id'],
                'name' => $product['name'],
                'category' => $product['category'],
                'price' => (int)$product['price'],
                'desc' => $product['notes'] . ' - ' . $product['concentration'] . ' ' . $product['size'],
                'notes' => $product['notes'],
                'concentration' => $product['concentration'],
                'size' => $product['size'],
                'brand' => $product['brand'],
                'badge' => $product['badge'] ?: '',
                'image' => apiImageUrl($product['image'])
            ];
        }, $products);

        echo json_encode($transformed);
    } catch(PDOException $e) {
        echo json_encode(['error' => 'Query failed: ' . $e->getMessage()]);
    }
}
?>