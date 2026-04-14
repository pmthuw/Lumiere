<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../../setup_db.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    $hasWardColumn = (bool)$pdo->query("SHOW COLUMNS FROM orders LIKE 'ward'")->fetch(PDO::FETCH_ASSOC);
    if (!$hasWardColumn) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN ward VARCHAR(100) DEFAULT NULL AFTER shipping_address");
    }
} catch (Throwable $e) {
    // Continue without breaking order placement; ward field is optional.
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON payload']);
    exit;
}

$customerName = trim((string)($data['customer_name'] ?? ''));
$customerEmail = trim((string)($data['customer_email'] ?? ''));
$customerPhone = trim((string)($data['customer_phone'] ?? ''));
$shippingAddress = trim((string)($data['shipping_address'] ?? ''));
$ward = trim((string)($data['ward'] ?? ''));
$district = trim((string)($data['district'] ?? ''));
$city = trim((string)($data['city'] ?? ''));
$paymentMethod = trim((string)($data['payment_method'] ?? 'cod'));
$notes = trim((string)($data['notes'] ?? ''));
$userEmail = trim((string)($data['user_email'] ?? ''));
$items = $data['items'] ?? [];

if ($customerName === '' || $customerEmail === '' || $shippingAddress === '' || empty($items) || !is_array($items)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc để tạo đơn hàng']);
    exit;
}

if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Email khách hàng không hợp lệ']);
    exit;
}

if ($customerPhone !== '' && !preg_match('/^[0-9]{10}$/', preg_replace('/\s+/', '', $customerPhone))) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Số điện thoại không hợp lệ']);
    exit;
}

try {
    $pdo->beginTransaction();

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


    $resolvedUserId = null;
    if ($userEmail !== '') {
        $userStmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $userStmt->execute([$userEmail]);
        $resolvedUserId = $userStmt->fetchColumn();
        if ($resolvedUserId !== false) {
            $resolvedUserId = (int)$resolvedUserId;
        } else {
            $resolvedUserId = null;
        }
    }

    $lineItems = [];
    $totalAmount = 0;

    $productStmt = $pdo->prepare("SELECT p.id, p.name, p.brand,
        ROUND({$avgCostExpr} * (1 + (COALESCE(p.profit_rate, 0) / 100))) AS display_price
        FROM products p
        {$priceJoinSql}
        WHERE p.id = ? LIMIT 1");
    foreach ($items as $item) {
        $productId = (int)($item['id'] ?? 0);
        $qty = max(1, (int)($item['qty'] ?? 0));

        if ($productId <= 0) {
            continue;
        }

        $productStmt->execute([$productId]);
        $product = $productStmt->fetch(PDO::FETCH_ASSOC);
        if (!$product) {
            continue;
        }

        $unitPrice = (int)$product['display_price'];
        $lineTotal = $unitPrice * $qty;
        $totalAmount += $lineTotal;

        $lineItems[] = [
            'product_id' => (int)$product['id'],
            'product_name' => (string)$product['name'],
            'product_brand' => (string)($product['brand'] ?? '-'),
            'quantity' => $qty,
            'unit_price' => $unitPrice,
            'total_price' => $lineTotal,
        ];
    }

    if (empty($lineItems)) {
        $pdo->rollBack();
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Không có sản phẩm hợp lệ trong đơn']);
        exit;
    }

    $orderNumber = 'ORD-' . date('YmdHis') . '-' . random_int(100, 999);

    $insertOrder = $pdo->prepare("INSERT INTO orders (
        order_number, user_id, customer_name, customer_email, customer_phone,
        shipping_address, ward, district, city, total_amount, status, payment_method, notes
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?)");

    $insertOrder->execute([
        $orderNumber,
        $resolvedUserId,
        $customerName,
        $customerEmail,
        $customerPhone !== '' ? $customerPhone : null,
        $shippingAddress,
        $ward !== '' ? $ward : null,
        $district !== '' ? $district : null,
        $city !== '' ? $city : null,
        $totalAmount,
        $paymentMethod,
        $notes !== '' ? $notes : null,
    ]);

    $orderId = (int)$pdo->lastInsertId();

    $insertItem = $pdo->prepare("INSERT INTO order_items (
        order_id, product_id, product_name, product_brand, quantity, unit_price, total_price
    ) VALUES (?, ?, ?, ?, ?, ?, ?)");

    foreach ($lineItems as $line) {
        $insertItem->execute([
            $orderId,
            $line['product_id'],
            $line['product_name'],
            $line['product_brand'],
            $line['quantity'],
            $line['unit_price'],
            $line['total_price'],
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'order_id' => $orderId,
        'order_number' => $orderNumber,
        'total_amount' => $totalAmount,
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Không thể tạo đơn hàng: ' . $e->getMessage()]);
}
