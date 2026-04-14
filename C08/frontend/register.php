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

function ensureRegisterSchema(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(255) NOT NULL,
        username VARCHAR(100) NOT NULL UNIQUE,
        email VARCHAR(255) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        original_password VARCHAR(255) DEFAULT NULL,
        phone VARCHAR(20) DEFAULT NULL,
        address TEXT DEFAULT NULL,
        ward VARCHAR(100) DEFAULT NULL,
        district VARCHAR(100) DEFAULT NULL,
        city VARCHAR(100) DEFAULT NULL,
        status ENUM('active','locked','inactive') NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_username (username),
        INDEX idx_email (email),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $columns = [
        'original_password' => "ALTER TABLE users ADD COLUMN original_password VARCHAR(255) DEFAULT NULL AFTER password_hash",
        'ward' => "ALTER TABLE users ADD COLUMN ward VARCHAR(100) DEFAULT NULL AFTER address",
    ];

    foreach ($columns as $name => $sql) {
        $exists = (bool)$pdo->query("SHOW COLUMNS FROM users LIKE '{$name}'")->fetch(PDO::FETCH_ASSOC);
        if (!$exists) {
            $pdo->exec($sql);
        }
    }
}

ensureRegisterSchema($pdo);

$lastname = trim((string)($payload['lastname'] ?? ''));
$firstname = trim((string)($payload['firstname'] ?? ''));
$fullName = trim($lastname . ' ' . $firstname);
$email = trim((string)($payload['email'] ?? ''));
$username = trim((string)($payload['username'] ?? ''));
$phone = trim((string)($payload['phone'] ?? ''));
$password = trim((string)($payload['password'] ?? ''));
$address = trim((string)($payload['address'] ?? ''));
$ward = trim((string)($payload['ward'] ?? ''));
$district = trim((string)($payload['district'] ?? ''));
$city = trim((string)($payload['city'] ?? ''));

if ($fullName === '' || $email === '' || $username === '' || $phone === '' || $password === '' || $address === '' || $ward === '' || $district === '' || $city === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Email không hợp lệ.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!preg_match('/^[0-9]{10}$/', $phone)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Số điện thoại phải có đúng 10 chữ số.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (strlen($username) < 4 || strlen($username) > 50) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Tên tài khoản phải có 4-50 ký tự.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!preg_match('/^[a-zA-Z0-9._-]+$/', $username)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Tên tài khoản chỉ chứa chữ, số, dấu . - và _.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (strlen($password) < 6) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Mật khẩu phải có ít nhất 6 ký tự.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$existsStmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ? OR username = ?');
$existsStmt->execute([$email, $username]);
if ((int)$existsStmt->fetchColumn() > 0) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Email hoặc tên tài khoản đã được sử dụng.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$insert = $pdo->prepare(
    'INSERT INTO users (full_name, username, email, password_hash, original_password, phone, address, ward, district, city, status)
     VALUES (:full_name, :username, :email, :password_hash, :original_password, :phone, :address, :ward, :district, :city, :status)'
);

$insert->execute([
    ':full_name' => $fullName,
    ':username' => $username,
    ':email' => $email,
    ':password_hash' => $password,
    ':original_password' => $password,
    ':phone' => $phone,
    ':address' => $address,
    ':ward' => $ward,
    ':district' => $district,
    ':city' => $city,
    ':status' => 'active',
]);

$userId = (int)$pdo->lastInsertId();
$user = [
    'id' => $userId,
    'lastname' => $lastname,
    'firstname' => $firstname,
    'full_name' => $fullName,
    'username' => $username,
    'email' => $email,
    'phone' => $phone,
    'address' => $address,
    'ward' => $ward,
    'district' => $district,
    'city' => $city,
    'status' => 'active',
];

$_SESSION['user'] = $user;
$_SESSION['customer_logged_in'] = true;
$_SESSION['customer_user'] = [
    'id' => $userId,
    'username' => $username,
    'email' => $email,
    'fullname' => $fullName,
    'firstname' => $firstname,
    'lastname' => $lastname,
    'phone' => $phone,
    'address' => $address,
    'ward' => $ward,
    'district' => $district,
    'city' => $city,
    'status' => 'active',
];

echo json_encode([
    'success' => true,
    'message' => 'Đăng ký thành công.',
    'user' => $user,
], JSON_UNESCAPED_UNICODE);
