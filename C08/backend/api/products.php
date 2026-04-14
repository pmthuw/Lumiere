<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../setup_db.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    echo json_encode(['error' => 'Database connection failed']);
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
        $hasReceiptPricingTables = false;
        try {
            $hasReceiptsTable = (bool)$pdo->query("SHOW TABLES LIKE 'receipts'")->fetchColumn();
            $hasReceiptItemsTable = (bool)$pdo->query("SHOW TABLES LIKE 'receipt_items'")->fetchColumn();
            $hasReceiptPricingTables = $hasReceiptsTable && $hasReceiptItemsTable;
        } catch (Throwable $e) {
            $hasReceiptPricingTables = false;
        }

        $priceJoinSql = '';
        $avgCostExpr = 'COALESCE(p.avg_import_price, 0)';


        $sql = "SELECT p.*, {$avgCostExpr} AS computed_avg_import_price FROM products p {$priceJoinSql}";
        if ($hasStatusColumn) {
            $sql .= " WHERE p.status = 'active'";
        }
        $sql .= " ORDER BY p.id";

        $stmt = $pdo->query($sql);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Transform data to match frontend format
        $transformed = array_map(function($product) {
            $displayPrice = (int)round((float)($product['computed_avg_import_price'] ?? 0) * (1 + ((float)($product['profit_rate'] ?? 0) / 100)));

            return [
                'id' => (int)$product['id'],
                'name' => $product['name'],
                'category' => $product['category'],
                'price' => $displayPrice,
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