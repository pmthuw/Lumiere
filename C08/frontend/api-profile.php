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

$sessionUser = $_SESSION['customer_user'] ?? null;
$legacySessionUser = $_SESSION['user'] ?? null;

$rawBody = file_get_contents('php://input');
$payload = json_decode((string)$rawBody, true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = trim((string)($payload['action'] ?? ''));

function buildSessionUser(PDO $pdo, int $userId): ?array
{
    $stmt = $pdo->prepare(
        'SELECT id, full_name, username, email, phone, address, ward, district, city, status
         FROM users
         WHERE id = :id
         LIMIT 1'
    );
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        return null;
    }

    $name = trim((string)($user['full_name'] ?? ''));
    $parts = preg_split('/\s+/', $name);
    $firstname = $parts ? (string)array_pop($parts) : '';
    $lastname = $parts ? implode(' ', $parts) : '';

    return [
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
}

function resolveCurrentUserId(PDO $pdo, $sessionUser, $legacySessionUser): int
{
    $customerId = is_array($sessionUser) ? (int)($sessionUser['id'] ?? 0) : 0;
    if ($customerId > 0) {
        return $customerId;
    }

    $legacyId = is_array($legacySessionUser) ? (int)($legacySessionUser['id'] ?? 0) : 0;
    if ($legacyId > 0) {
        return $legacyId;
    }

    $email = '';
    $username = '';
    if (is_array($sessionUser)) {
        $email = trim((string)($sessionUser['email'] ?? ''));
        $username = trim((string)($sessionUser['username'] ?? ''));
    }
    if ($email === '' && is_array($legacySessionUser)) {
        $email = trim((string)($legacySessionUser['email'] ?? ''));
    }
    if ($username === '' && is_array($legacySessionUser)) {
        $username = trim((string)($legacySessionUser['username'] ?? ''));
    }

    if ($email === '' && $username === '') {
        return 0;
    }

    $query = 'SELECT id FROM users WHERE email = :email OR username = :username LIMIT 1';
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':email' => $email,
        ':username' => $username,
    ]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? (int)($row['id'] ?? 0) : 0;
}

try {
    $userId = resolveCurrentUserId($pdo, $sessionUser, $legacySessionUser);
    if ($userId <= 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Bạn chưa đăng nhập.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'update_info') {
        $lastname = trim((string)($payload['lastname'] ?? ''));
        $firstname = trim((string)($payload['firstname'] ?? ''));
        $phone = trim((string)($payload['phone'] ?? ''));
        $address = trim((string)($payload['address'] ?? ''));
        $district = trim((string)($payload['district'] ?? ''));
        $city = trim((string)($payload['city'] ?? ''));

        if ($lastname === '' || $firstname === '' || $phone === '' || $address === '' || $district === '' || $city === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (!preg_match('/^[0-9]{10}$/', $phone)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Số điện thoại phải có đúng 10 chữ số.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $fullName = trim($lastname . ' ' . $firstname);
        $stmt = $pdo->prepare(
            'UPDATE users
             SET full_name = :full_name,
                 phone = :phone,
                 address = :address,
                 district = :district,
                 city = :city,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute([
            ':full_name' => $fullName,
            ':phone' => $phone,
            ':address' => $address,
            ':district' => $district,
            ':city' => $city,
            ':id' => $userId,
        ]);

        $user = buildSessionUser($pdo, $userId);
        if (!$user) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy tài khoản.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $_SESSION['customer_user'] = $user;
        $_SESSION['customer_logged_in'] = true;
        echo json_encode(['success' => true, 'message' => 'Cập nhật thông tin thành công.', 'user' => $user], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'change_password') {
        $oldPassword = trim((string)($payload['old_password'] ?? ''));
        $newPassword = trim((string)($payload['new_password'] ?? ''));
        $confirmPassword = trim((string)($payload['confirm_password'] ?? ''));

        if ($oldPassword === '' || $newPassword === '' || $confirmPassword === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (strlen($newPassword) < 6) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Mật khẩu mới phải có ít nhất 6 ký tự.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($newPassword !== $confirmPassword) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Mật khẩu xác nhận không khớp.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $storedPassword = (string)($row['password_hash'] ?? '');

        if ($storedPassword === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy mật khẩu hiện tại trong hệ thống.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $isPasswordCorrect = ($storedPassword === $oldPassword);
        if (!$isPasswordCorrect) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Mật khẩu hiện tại không đúng.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $updateStmt = $pdo->prepare(
            'UPDATE users
             SET password_hash = :password_hash,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id
             LIMIT 1'
        );
        $updateStmt->execute([
            ':password_hash' => $newPassword,
            ':id' => $userId,
        ]);

        echo json_encode(['success' => true, 'message' => 'Đổi mật khẩu thành công.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ.'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi máy chủ. Vui lòng thử lại.'], JSON_UNESCAPED_UNICODE);
}
