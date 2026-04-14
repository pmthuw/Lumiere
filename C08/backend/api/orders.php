<?php
declare(strict_types=1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
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

$userEmail = trim((string)($_GET['user'] ?? $_GET['user_email'] ?? ''));
if ($userEmail === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Thiếu email người dùng']);
    exit;
}

try {
    $orderStmt = $pdo->prepare(
        'SELECT o.id, o.order_number, o.customer_email, o.total_amount, o.status, o.payment_method, o.created_at
         FROM orders o
         LEFT JOIN users u ON u.id = o.user_id
         WHERE o.customer_email = :email OR u.email = :email
         ORDER BY o.created_at DESC, o.id DESC'
    );
    $orderStmt->execute(['email' => $userEmail]);
    $orders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$orders) {
        echo json_encode(['success' => true, 'orders' => []]);
        exit;
    }

    $itemStmt = $pdo->prepare(
        'SELECT product_id, product_name, quantity, unit_price, total_price
         FROM order_items
         WHERE order_id = ?
         ORDER BY id ASC'
    );

    $result = [];
    foreach ($orders as $order) {
        $orderId = (int)$order['id'];
        $itemStmt->execute([$orderId]);
        $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

        $result[] = [
            'id' => $orderId,
            'order_number' => (string)($order['order_number'] ?? $orderId),
            'customer_email' => (string)($order['customer_email'] ?? ''),
            'status' => (string)($order['status'] ?? 'pending'),
            'payment_method' => (string)($order['payment_method'] ?? ''),
            'created_at' => (string)($order['created_at'] ?? ''),
            'total_amount' => (int)($order['total_amount'] ?? 0),
            'items' => array_map(
                static fn(array $item): array => [
                    'id' => (int)($item['product_id'] ?? 0),
                    'name' => (string)($item['product_name'] ?? 'Sản phẩm'),
                    'qty' => (int)($item['quantity'] ?? 1),
                    'price' => (int)($item['unit_price'] ?? 0),
                    'total' => (int)($item['total_price'] ?? 0),
                ],
                $items
            ),
        ];
    }

    echo json_encode(['success' => true, 'orders' => $result]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Không thể tải đơn hàng: ' . $e->getMessage()]);
}
