<?php
require_once __DIR__ . '/../setup_db.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    ?>
    <section class="admin-page<?php echo ($page === 'users') ? ' active' : ''; ?>" id="page-users">
        <div style="background: rgba(255, 0, 0, 0.1); border: 1px solid red; color: red; padding: 1rem; margin-bottom: 1rem; border-radius: 0.5rem;">
            Không thể kết nối cơ sở dữ liệu để tải quản lý tài khoản.
        </div>
    </section>
    <?php
    return;
}

$defaultPassword = '12345';

function ensureUserManagementSchema(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
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
        INDEX idx_username (username),
        INDEX idx_email (email),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_users (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL UNIQUE,
        email VARCHAR(255) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        original_password VARCHAR(255) DEFAULT NULL,
        full_name VARCHAR(255) NOT NULL,
        status ENUM('active', 'inactive') DEFAULT 'active',
        last_login TIMESTAMP NULL DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_username (username),
        INDEX idx_email (email),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $hasOriginalPasswordUsers = (bool)$pdo->query("SHOW COLUMNS FROM users LIKE 'original_password'")->fetch(PDO::FETCH_ASSOC);
    if (!$hasOriginalPasswordUsers) {
        $pdo->exec("ALTER TABLE users ADD COLUMN original_password VARCHAR(255) DEFAULT NULL AFTER password_hash");
    }

    $columns = [
        'phone' => "ALTER TABLE users ADD COLUMN phone VARCHAR(20) DEFAULT NULL AFTER original_password",
        'address' => "ALTER TABLE users ADD COLUMN address TEXT DEFAULT NULL AFTER phone",
        'ward' => "ALTER TABLE users ADD COLUMN ward VARCHAR(100) DEFAULT NULL AFTER address",
        'district' => "ALTER TABLE users ADD COLUMN district VARCHAR(100) DEFAULT NULL AFTER ward",
        'city' => "ALTER TABLE users ADD COLUMN city VARCHAR(100) DEFAULT NULL AFTER district",
    ];

    foreach ($columns as $name => $sql) {
        $exists = (bool)$pdo->query("SHOW COLUMNS FROM users LIKE '{$name}'")->fetch(PDO::FETCH_ASSOC);
        if (!$exists) {
            try {
                $pdo->exec($sql);
            } catch (Exception $e) {
                // Column might already exist or other error; continue
            }
        }
    }

    $hasOriginalPasswordAdmins = (bool)$pdo->query("SHOW COLUMNS FROM admin_users LIKE 'original_password'")->fetch(PDO::FETCH_ASSOC);
    if (!$hasOriginalPasswordAdmins) {
        $pdo->exec("ALTER TABLE admin_users ADD COLUMN original_password VARCHAR(255) DEFAULT NULL AFTER password_hash");
    }

    $pdo->exec("UPDATE users SET original_password = password_hash WHERE (original_password IS NULL OR original_password = '') AND password_hash <> ''");
    $pdo->exec("UPDATE admin_users SET original_password = password_hash WHERE (original_password IS NULL OR original_password = '') AND password_hash <> ''");
}

function normalizeScope(string $scope): string
{
    return $scope === 'admin' ? 'admin' : 'customer';
}

function isLockedStatus(string $status, string $scope): bool
{
    if ($scope === 'admin') {
        return $status !== 'active';
    }

    return in_array($status, ['locked', 'inactive'], true);
}

ensureUserManagementSchema($pdo);

$successMessage = null;
$errorMessage = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = '';
    if (isset($_POST['add_user'])) {
        $action = 'add';
    } elseif (isset($_POST['toggle_status'])) {
        $action = 'toggle';
    } elseif (isset($_POST['reset_password'])) {
        $action = 'reset';
    }

    if ($action === 'add') {
        $scope = normalizeScope((string)($_POST['account_scope'] ?? 'customer'));
        $fullname = trim((string)($_POST['fullname'] ?? ''));
        $username = trim((string)($_POST['username'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $password = trim((string)($_POST['password'] ?? ''));

        if ($fullname === '' || $username === '' || $email === '') {
            $errorMessage = 'Vui lòng điền đầy đủ thông tin bắt buộc.';
        } else {
            $plainPassword = $password !== '' ? $password : $defaultPassword;

            if ($scope === 'admin') {
                $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM admin_users WHERE username = ? OR email = ?');
                $checkStmt->execute([$username, $email]);
                if ((int)$checkStmt->fetchColumn() > 0) {
                    $errorMessage = 'Tên đăng nhập hoặc email admin đã được sử dụng.';
                } else {
                    $insert = $pdo->prepare("INSERT INTO admin_users (full_name, username, email, password_hash, original_password, status) VALUES (?, ?, ?, ?, ?, 'active')");
                    $insert->execute([$fullname, $username, $email, $plainPassword, $plainPassword]);
                    $successMessage = $password === ''
                        ? "Đã tạo tài khoản admin thành công. Mật khẩu khởi tạo: $defaultPassword"
                        : 'Đã tạo tài khoản admin thành công.';
                }
            } else {
                $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ? OR email = ?');
                $checkStmt->execute([$username, $email]);
                if ((int)$checkStmt->fetchColumn() > 0) {
                    $errorMessage = 'Tên đăng nhập hoặc email đã được sử dụng.';
                } else {
                    $insert = $pdo->prepare("INSERT INTO users (full_name, username, email, password_hash, original_password, status) VALUES (?, ?, ?, ?, ?, 'active')");
                    $insert->execute([$fullname, $username, $email, $plainPassword, $plainPassword]);
                    $successMessage = $password === ''
                        ? "Đã tạo tài khoản thành công. Mật khẩu khởi tạo: $defaultPassword"
                        : 'Đã tạo tài khoản thành công.';
                }
            }
        }
    }

    if ($action === 'toggle') {
        $id = (int)($_POST['user_id'] ?? 0);
        $scope = normalizeScope((string)($_POST['account_scope'] ?? 'customer'));

        if ($id > 0) {
            if ($scope === 'admin') {
                $stmt = $pdo->prepare('SELECT status FROM admin_users WHERE id = ?');
                $stmt->execute([$id]);
                $current = (string)($stmt->fetchColumn() ?: '');
                if ($current !== '') {
                    $newStatus = $current === 'active' ? 'inactive' : 'active';
                    $pdo->prepare('UPDATE admin_users SET status = ? WHERE id = ?')->execute([$newStatus, $id]);
                    $successMessage = $newStatus === 'active' ? 'Đã mở khóa tài khoản admin.' : 'Đã khóa tài khoản admin.';
                }
            } else {
                $stmt = $pdo->prepare('SELECT status FROM users WHERE id = ?');
                $stmt->execute([$id]);
                $current = (string)($stmt->fetchColumn() ?: '');
                if ($current !== '') {
                    $newStatus = isLockedStatus($current, 'customer') ? 'active' : 'locked';
                    $pdo->prepare('UPDATE users SET status = ? WHERE id = ?')->execute([$newStatus, $id]);
                    $successMessage = $newStatus === 'active' ? 'Đã mở khóa tài khoản.' : 'Đã khóa tài khoản.';
                }
            }
        }
    }

    if ($action === 'reset') {
        $id = (int)($_POST['user_id'] ?? 0);
        $scope = normalizeScope((string)($_POST['account_scope'] ?? 'customer'));

        if ($id <= 0) {
            $errorMessage = 'User ID không hợp lệ.';
        } else {
            $table = $scope === 'admin' ? 'admin_users' : 'users';
            $stmt = $pdo->prepare("SELECT original_password, password_hash FROM {$table} WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                $errorMessage = 'Không tìm thấy người dùng để reset mật khẩu.';
            } else {
                $resetPassword = trim((string)($row['original_password'] ?? ''));
                if ($resetPassword === '') {
                    $resetPassword = $defaultPassword;
                }

                $currentPassword = (string)($row['password_hash'] ?? '');
                $updateStmt = $pdo->prepare("UPDATE {$table} SET password_hash = ? WHERE id = ?");
                $updateOk = $updateStmt->execute([$resetPassword, $id]);

                if (!$updateOk) {
                    $errorMessage = 'Có lỗi khi cập nhật mật khẩu.';
                } elseif ($currentPassword === $resetPassword) {
                    $successMessage = 'Mật khẩu đã ở đúng mật khẩu gốc.';
                } else {
                    $successMessage = 'Đã reset mật khẩu về mật khẩu lúc đăng ký.';
                }
            }
        }
    }
}

$customerUsers = $pdo->query("SELECT id, full_name, username, email, status, created_at, 'customer' AS account_scope FROM users ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$adminUsers = $pdo->query("SELECT id, full_name, username, email, status, created_at, 'admin' AS account_scope FROM admin_users ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

$accounts = array_merge($adminUsers, $customerUsers);
usort($accounts, static function (array $a, array $b): int {
    return strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? ''));
});

function accountScopeLabel(string $scope): string
{
    return $scope === 'admin' ? 'Admin' : 'Người dùng';
}

function statusLabel(string $status, string $scope): string
{
    if ($scope === 'admin') {
        return $status === 'active' ? 'Hoạt động' : 'Đã khóa';
    }

    return in_array($status, ['active'], true) ? 'Hoạt động' : 'Đã khóa';
}

function statusBadge(string $status, string $scope): string
{
    if ($scope === 'admin') {
        return $status === 'active' ? 'badge-success' : 'badge-danger';
    }

    return in_array($status, ['active'], true) ? 'badge-success' : 'badge-danger';
}
?>
<section class="admin-page<?php echo ($page === 'users') ? ' active' : ''; ?>" id="page-users">
    <div class="page-header">
        <div class="page-header-left">
            <span class="eyebrow">✦ Hệ thống</span>
            <h1>Quản lý tài khoản người dùng và admin</h1>
        </div>
    </div>

    <?php if ($successMessage): ?>
        <div style="background: rgba(0, 255, 0, 0.1); border: 1px solid green; color: green; padding: 1rem; margin-bottom: 1rem; border-radius: 0.5rem;">
            <?php echo htmlspecialchars($successMessage); ?>
        </div>
    <?php endif; ?>

    <?php if ($errorMessage): ?>
        <div style="background: rgba(255, 0, 0, 0.1); border: 1px solid red; color: red; padding: 1rem; margin-bottom: 1rem; border-radius: 0.5rem;">
            <?php echo htmlspecialchars($errorMessage); ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1.9fr 1fr; gap: 1.5rem; align-items: start;">
        <div class="table-wrap">
            <div class="table-toolbar">
                <h3>Danh sách tài khoản</h3>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Loại</th>
                        <th>Họ tên</th>
                        <th>Tên đăng nhập</th>
                        <th>Email</th>
                        <th>Trạng thái</th>
                        <th>Ngày tạo</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($accounts)): ?>
                        <tr>
                            <td colspan="7" class="td-muted" style="text-align:center;">Chưa có tài khoản nào.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($accounts as $acc): ?>
                            <?php $scope = (string)($acc['account_scope'] ?? 'customer'); ?>
                            <?php $locked = isLockedStatus((string)($acc['status'] ?? ''), $scope); ?>
                            <tr>
                                <td><span class="badge badge-muted"><?php echo htmlspecialchars(accountScopeLabel($scope)); ?></span></td>
                                <td class="td-name"><?php echo htmlspecialchars((string)($acc['full_name'] ?? '')); ?></td>
                                <td class="td-muted"><?php echo htmlspecialchars((string)($acc['username'] ?? '')); ?></td>
                                <td class="td-muted"><?php echo htmlspecialchars((string)($acc['email'] ?? '')); ?></td>
                                <td><span class="badge <?php echo statusBadge((string)($acc['status'] ?? ''), $scope); ?>"><?php echo htmlspecialchars(statusLabel((string)($acc['status'] ?? ''), $scope)); ?></span></td>
                                <td class="td-muted"><?php echo date('d/m/Y H:i', strtotime((string)($acc['created_at'] ?? 'now'))); ?></td>
                                <td>
                                    <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="user_id" value="<?php echo (int)($acc['id'] ?? 0); ?>" />
                                            <input type="hidden" name="account_scope" value="<?php echo htmlspecialchars($scope); ?>" />
                                            <button type="submit" name="reset_password" class="btn btn-ghost btn-sm">Reset mật khẩu gốc</button>
                                        </form>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="user_id" value="<?php echo (int)($acc['id'] ?? 0); ?>" />
                                            <input type="hidden" name="account_scope" value="<?php echo htmlspecialchars($scope); ?>" />
                                            <button type="submit" name="toggle_status" class="btn btn-sm <?php echo !$locked ? 'btn-danger' : 'btn-ghost'; ?>">
                                                <?php echo !$locked ? 'Khóa' : 'Mở khóa'; ?>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="table-wrap" style="padding: 1.75rem;">
            <h3 style="font-family: 'Playfair Display', serif; font-size: 1.1rem; margin-bottom: 1.5rem;">Thêm tài khoản mới</h3>

            <form method="post">
                <div class="form-row">
                    <div class="form-group">
                        <label>Loại tài khoản</label>
                        <select class="form-control" name="account_scope">
                            <option value="customer">Người dùng</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Họ tên</label>
                        <input class="form-control" name="fullname" type="text" required />
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Tên đăng nhập</label>
                        <input class="form-control" name="username" type="text" required />
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Email</label>
                        <input class="form-control" name="email" type="email" required />
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Mật khẩu (để trống để dùng mặc định)</label>
                        <input class="form-control" name="password" type="text" placeholder="Mật khẩu tùy chọn" />
                    </div>
                </div>
                <button type="submit" name="add_user" class="btn btn-gold">Thêm tài khoản</button>
            </form>

            <div style="margin-top: 1.5rem; font-size: 0.9rem; color: var(--muted);">
                <p>Mật khẩu mặc định khi khởi tạo: <strong><?php echo htmlspecialchars($defaultPassword); ?></strong></p>
                <p>Reset mật khẩu sẽ đưa về mật khẩu gốc lúc tạo tài khoản.</p>
                <p>Đã bỏ chức năng chỉnh sửa tài khoản theo yêu cầu.</p>
            </div>
        </div>
    </div>
</section>
