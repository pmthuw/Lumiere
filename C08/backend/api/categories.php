<?php
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

try {
    $hasStatus = false;
    try {
        $statusCheck = $pdo->query("SHOW COLUMNS FROM categories LIKE 'status'");
        $hasStatus = (bool)$statusCheck->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $hasStatus = false;
    }

    if ($hasStatus) {
        $stmt = $pdo->query("SELECT name FROM categories WHERE status = 'active' ORDER BY name");
    } else {
        $stmt = $pdo->query("SELECT name FROM categories ORDER BY name");
    }

    echo json_encode([
        'success' => true,
        'categories' => $stmt->fetchAll(PDO::FETCH_COLUMN),
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Không thể tải danh mục: ' . $e->getMessage()]);
}