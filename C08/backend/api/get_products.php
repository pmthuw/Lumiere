<?php
/**
 * Product API with server-side filtering & pagination
 * Returns products as HTML or JSON based on request
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Database configuration
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

// Get filter parameters
$category = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';
$priceMin = $_GET['priceMin'] ?? 0;
$priceMax = $_GET['priceMax'] ?? 999999999;
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = (int)($_GET['perPage'] ?? 6);
$returnFormat = $_GET['format'] ?? 'json'; // 'json' or 'html'

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


// Build query
$sql = "SELECT p.*, {$avgCostExpr} AS computed_avg_import_price FROM products p {$priceJoinSql} WHERE 1=1";
$params = [];
$displayPriceExpr = "ROUND({$avgCostExpr} * (1 + (COALESCE(p.profit_rate, 0) / 100)))";

if ($hasStatusColumn) {
    $sql .= " AND p.status = 'active'";
}

if ($category) {
    $sql .= " AND p.category = ?";
    $params[] = $category;
}

if ($search) {
    $sql .= " AND (p.name LIKE ? OR p.brand LIKE ? OR p.notes LIKE ?)";
    $searchTerm = '%' . $search . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql .= " AND {$displayPriceExpr} BETWEEN ? AND ?";
$params[] = (int)$priceMin;
$params[] = (int)$priceMax;

// Count total results
$countSql = preg_replace('/^SELECT\s.+?\sFROM\s/si', 'SELECT COUNT(*) as total FROM ', $sql);
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];

// Order and limit
$sql .= " ORDER BY p.id LIMIT ?, ?";
$offset = ($page - 1) * $perPage;

// Execute query
$stmt = $pdo->prepare($sql);
foreach ($params as $idx => $value) {
    $stmt->bindValue($idx + 1, $value);
}
$stmt->bindValue(count($params) + 1, $offset, PDO::PARAM_INT);
$stmt->bindValue(count($params) + 2, $perPage, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($products as &$product) {
    $product['display_price'] = (int)round((float)($product['computed_avg_import_price'] ?? 0) * (1 + ((float)($product['profit_rate'] ?? 0) / 100)));
}
unset($product);

// Return JSON format (for API calls)
if ($returnFormat === 'json') {
    $transformed = array_map(function($product) {
        return [
            'id' => (int)$product['id'],
            'name' => $product['name'],
            'category' => $product['category'],
            'price' => (int)$product['display_price'],
            'desc' => $product['notes'] . ' - ' . $product['concentration'] . ' ' . $product['size'],
            'notes' => $product['notes'],
            'concentration' => $product['concentration'],
            'size' => $product['size'],
            'brand' => $product['brand'],
            'badge' => $product['badge'] ?: '',
            'image' => formatImageUrl($product['image'])
        ];
    }, $products);

    echo json_encode([
        'products' => $transformed,
        'total' => $total,
        'page' => $page,
        'perPage' => $perPage,
        'totalPages' => ceil($total / $perPage)
    ]);
    exit;
}

// Return HTML format (for server-side rendering)
if ($returnFormat === 'html') {
    if (empty($products)) {
        echo json_encode(['html' => '<p class="no-results">Không tìm thấy sản phẩm nào phù hợp</p>']);
    } else {
        ob_start();
        foreach ($products as $product) {
            echo renderProductCard($product);
        }
        $html = ob_get_clean();
        echo json_encode(['html' => $html]);
    }
    exit;
}

?>

<?php

function formatImageUrl(?string $image): string
{
    $image = trim((string)$image);
    if ($image === '') {
        return '/frontend/images/default.jpg';
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

    return '/frontend/' . ltrim($image, '/');
}

function renderProductCard($product): string
{
    $badge = !empty($product['badge']) ? '<span class="product-badge">' . htmlspecialchars($product['badge']) . '</span>' : '';
    $image = formatImageUrl($product['image']);
    $price = isset($product['display_price'])
        ? (int)$product['display_price']
        : (int)round((float)($product['computed_avg_import_price'] ?? 0) * (1 + ((float)($product['profit_rate'] ?? 0) / 100)));
    $priceFormatted = number_format($price, 0, '.', ',');
    
    return <<<HTML
<div class="product-card" data-id="{$product['id']}">
  {$badge}
  <div class="product-image">
    <img src="{$image}" alt="{$product['name']}" />
  </div>
  <div class="product-info">
    <h3 class="product-name">{$product['name']}</h3>
    <p class="product-brand">{$product['brand']}</p>
    <p class="product-desc">{$product['notes']}</p>
    <div class="product-footer">
      <span class="product-price">{$priceFormatted} ₫</span>
    <button class="btn-add-cart" onclick="addToCart({$product['id']}, '{$product['name']}', {$price})">
        <i class="fa-solid fa-bag-shopping"></i>
      </button>
    </div>
  </div>
</div>
HTML;
}
?>
