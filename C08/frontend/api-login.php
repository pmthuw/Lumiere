<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../setup_db.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Không thể kết nối cơ sở dữ liệu.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$rawBody = file_get_contents('php://input');
$payload = json_decode((string)$rawBody, true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$identifier = trim((string)($payload['identifier'] ?? ''));
$password = trim((string)($payload['password'] ?? ''));

if ($identifier === '' || $password === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stmt = $pdo->prepare(
        'SELECT id, full_name, username, email, password_hash, phone, address, ward, district, city, status
         FROM users
         WHERE username = :identifier
         LIMIT 1'
    );
    $stmt->execute([':identifier' => $identifier]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Tài khoản không tồn tại.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (in_array((string)($user['status'] ?? 'active'), ['locked', 'inactive'], true)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Tài khoản đã bị khóa hoặc tạm ngưng.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $storedPassword = (string)($user['password_hash'] ?? '');
    $isValid = $storedPassword !== '' && hash_equals($storedPassword, $password);

    if (!$isValid) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Mật khẩu không đúng.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $name = trim((string)($user['full_name'] ?? ''));
    $parts = preg_split('/\s+/', $name);
    $firstname = $parts ? array_pop($parts) : '';
    $lastname = $parts ? implode(' ', $parts) : '';

    $_SESSION['customer_logged_in'] = true;
    $_SESSION['customer_user'] = [
        'id' => (int)$user['id'],
        'username' => (string)($user['username'] ?? ''),
        'email' => (string)($user['email'] ?? ''),
        'fullname' => $name,
        'firstname' => $firstname,
        'lastname' => $lastname,
        'phone' => (string)($user['phone'] ?? ''),
        'address' => (string)($user['address'] ?? ''),
        'ward' => (string)($user['ward'] ?? ''),
        'district' => (string)($user['district'] ?? ''),
        'city' => (string)($user['city'] ?? ''),
        'status' => (string)($user['status'] ?? 'active'),
    ];

    echo json_encode([
        'success' => true,
        'message' => 'Đăng nhập thành công.',
        'user' => $_SESSION['customer_user'],
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi máy chủ. Vui lòng thử lại.'], JSON_UNESCAPED_UNICODE);
}
