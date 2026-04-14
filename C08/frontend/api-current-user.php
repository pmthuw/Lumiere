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

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$sessionUser = $_SESSION['customer_user'] ?? null;
$legacySessionUser = $_SESSION['user'] ?? null;

function firstNonEmpty(string ...$values): string
{
    foreach ($values as $value) {
        $trimmed = trim($value);
        if ($trimmed !== '') {
            return $trimmed;
        }
    }
    return '';
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
        echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $pdo->prepare(
        'SELECT id, full_name, username, email, phone, address, ward, district, city, status
         FROM users
         WHERE id = :id
         LIMIT 1'
    );
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION['customer_logged_in'] = false;
        unset($_SESSION['customer_user']);
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy tài khoản.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $name = trim((string)($user['full_name'] ?? ''));
    $parts = preg_split('/\s+/', $name);
    $firstname = $parts ? (string)array_pop($parts) : '';
    $lastname = $parts ? implode(' ', $parts) : '';

    $payload = [
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

    // Ưu tiên dữ liệu hồ sơ vừa đăng ký/lưu ở session, sau đó đồng bộ ngược lại DB.
    $sessionFullName = firstNonEmpty(
        is_array($sessionUser) ? (string)($sessionUser['fullname'] ?? '') : '',
        is_array($sessionUser) ? (string)($sessionUser['full_name'] ?? '') : '',
        is_array($legacySessionUser) ? (string)($legacySessionUser['full_name'] ?? '') : ''
    );
    $sessionPhone = firstNonEmpty(
        is_array($sessionUser) ? (string)($sessionUser['phone'] ?? '') : '',
        is_array($legacySessionUser) ? (string)($legacySessionUser['phone'] ?? '') : ''
    );
    $sessionAddress = firstNonEmpty(
        is_array($sessionUser) ? (string)($sessionUser['address'] ?? '') : '',
        is_array($legacySessionUser) ? (string)($legacySessionUser['address'] ?? '') : ''
    );
    $sessionWard = firstNonEmpty(
        is_array($sessionUser) ? (string)($sessionUser['ward'] ?? '') : '',
        is_array($legacySessionUser) ? (string)($legacySessionUser['ward'] ?? '') : ''
    );
    $sessionDistrict = firstNonEmpty(
        is_array($sessionUser) ? (string)($sessionUser['district'] ?? '') : '',
        is_array($legacySessionUser) ? (string)($legacySessionUser['district'] ?? '') : ''
    );
    $sessionCity = firstNonEmpty(
        is_array($sessionUser) ? (string)($sessionUser['city'] ?? '') : '',
        is_array($legacySessionUser) ? (string)($legacySessionUser['city'] ?? '') : ''
    );

    $sessionMergedFullName = firstNonEmpty((string)$payload['fullname'], $sessionFullName);
    $sessionMergedPhone = firstNonEmpty((string)$payload['phone'], $sessionPhone);
    $sessionMergedAddress = firstNonEmpty((string)$payload['address'], $sessionAddress);
    $sessionMergedWard = firstNonEmpty((string)$payload['ward'], $sessionWard);
    $sessionMergedDistrict = firstNonEmpty((string)$payload['district'], $sessionDistrict);
    $sessionMergedCity = firstNonEmpty((string)$payload['city'], $sessionCity);

    $sessionDidChange = (
        $sessionMergedFullName !== (string)$payload['fullname'] ||
        $sessionMergedPhone !== (string)$payload['phone'] ||
        $sessionMergedAddress !== (string)$payload['address'] ||
        $sessionMergedWard !== (string)$payload['ward'] ||
        $sessionMergedDistrict !== (string)$payload['district'] ||
        $sessionMergedCity !== (string)$payload['city']
    );

    if ($sessionDidChange) {
        $payload['fullname'] = $sessionMergedFullName;
        $payload['phone'] = $sessionMergedPhone;
        $payload['address'] = $sessionMergedAddress;
        $payload['ward'] = $sessionMergedWard;
        $payload['district'] = $sessionMergedDistrict;
        $payload['city'] = $sessionMergedCity;

        $nameParts = preg_split('/\s+/', trim((string)$payload['fullname']));
        $payload['firstname'] = $nameParts ? (string)array_pop($nameParts) : '';
        $payload['lastname'] = $nameParts ? implode(' ', $nameParts) : '';

        $syncStmt = $pdo->prepare(
            'UPDATE users
             SET full_name = :full_name,
                 phone = :phone,
                 address = :address,
                 ward = :ward,
                 district = :district,
                 city = :city,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id
             LIMIT 1'
        );
        $syncStmt->execute([
            ':full_name' => (string)$payload['fullname'],
            ':phone' => (string)$payload['phone'],
            ':address' => (string)$payload['address'],
            ':ward' => (string)$payload['ward'],
            ':district' => (string)$payload['district'],
            ':city' => (string)$payload['city'],
            ':id' => (int)$payload['id'],
        ]);
    }

    $userEmail = trim((string)($payload['email'] ?? ''));
    if ($userEmail !== '') {
        $orderStmt = $pdo->prepare(
            'SELECT customer_name, customer_phone, shipping_address, ward, district, city
             FROM orders
             WHERE customer_email = :email
             ORDER BY id DESC
             LIMIT 1'
        );
        $orderStmt->execute([':email' => $userEmail]);
        $lastOrder = $orderStmt->fetch(PDO::FETCH_ASSOC) ?: null;

        if (is_array($lastOrder)) {
            $newPhone = firstNonEmpty((string)($payload['phone'] ?? ''), (string)($lastOrder['customer_phone'] ?? ''));
            $newAddress = firstNonEmpty((string)($payload['address'] ?? ''), (string)($lastOrder['shipping_address'] ?? ''));
            $newWard = firstNonEmpty((string)($payload['ward'] ?? ''), (string)($lastOrder['ward'] ?? ''));
            $newDistrict = firstNonEmpty((string)($payload['district'] ?? ''), (string)($lastOrder['district'] ?? ''));
            $newCity = firstNonEmpty((string)($payload['city'] ?? ''), (string)($lastOrder['city'] ?? ''));
            $newFullName = firstNonEmpty((string)($payload['fullname'] ?? ''), (string)($lastOrder['customer_name'] ?? ''));

            $didChange = (
                $newPhone !== (string)($payload['phone'] ?? '') ||
                $newAddress !== (string)($payload['address'] ?? '') ||
                $newWard !== (string)($payload['ward'] ?? '') ||
                $newDistrict !== (string)($payload['district'] ?? '') ||
                $newCity !== (string)($payload['city'] ?? '') ||
                $newFullName !== (string)($payload['fullname'] ?? '')
            );

            if ($didChange) {
                $payload['phone'] = $newPhone;
                $payload['address'] = $newAddress;
                $payload['ward'] = $newWard;
                $payload['district'] = $newDistrict;
                $payload['city'] = $newCity;
                $payload['fullname'] = $newFullName;

                $nameParts = preg_split('/\s+/', trim((string)$newFullName));
                $payload['firstname'] = $nameParts ? (string)array_pop($nameParts) : '';
                $payload['lastname'] = $nameParts ? implode(' ', $nameParts) : '';

                $updateStmt = $pdo->prepare(
                    'UPDATE users
                     SET full_name = :full_name,
                         phone = :phone,
                         address = :address,
                         ward = :ward,
                         district = :district,
                         city = :city,
                         updated_at = CURRENT_TIMESTAMP
                     WHERE id = :id
                     LIMIT 1'
                );
                $updateStmt->execute([
                    ':full_name' => (string)$payload['fullname'],
                    ':phone' => (string)$payload['phone'],
                    ':address' => (string)$payload['address'],
                    ':ward' => (string)$payload['ward'],
                    ':district' => (string)$payload['district'],
                    ':city' => (string)$payload['city'],
                    ':id' => (int)$payload['id'],
                ]);
            }
        }
    }

    $_SESSION['customer_logged_in'] = true;
    $_SESSION['customer_user'] = $payload;

    echo json_encode(['success' => true, 'user' => $payload], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi máy chủ. Vui lòng thử lại.'], JSON_UNESCAPED_UNICODE);
}
