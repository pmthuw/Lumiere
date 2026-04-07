<?php
require_once __DIR__ . '/../setup_db.php';

$defaultPassword = '12345';
$pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','staff','customer') NOT NULL DEFAULT 'customer',
    status ENUM('active','locked') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Ensure compatibility with older schema names
$columnStmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'full_name'");
if ($columnStmt->rowCount() === 0) {
    if ($pdo->query("SHOW COLUMNS FROM users LIKE 'fullname'")->rowCount() > 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN full_name VARCHAR(255) NOT NULL AFTER id");
        $pdo->exec("UPDATE users SET full_name = fullname");
    }
}

$columnStmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'username'");
if ($columnStmt->rowCount() === 0) {
    if ($pdo->query("SHOW COLUMNS FROM users LIKE 'email'")->rowCount() > 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN username VARCHAR(100) NULL AFTER full_name");
        $pdo->exec("UPDATE users SET username = CASE
            WHEN email IS NOT NULL AND email <> '' THEN email
            ELSE CONCAT('user', id)
        END");
        $pdo->exec("UPDATE users SET username = CONCAT('user', id) WHERE username IS NULL OR username = ''");
        $pdo->exec("ALTER TABLE users MODIFY username VARCHAR(100) NOT NULL");
        $pdo->exec("ALTER TABLE users ADD UNIQUE INDEX idx_users_username (username)");
    }
}

$columnStmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'password_hash'");
if ($columnStmt->rowCount() === 0) {
    if ($pdo->query("SHOW COLUMNS FROM users LIKE 'password'")->rowCount() > 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN password_hash VARCHAR(255) NOT NULL AFTER email");
        $pdo->exec("UPDATE users SET password_hash = password");
    }
}

  // Ensure role enum supports staff and status supports locked/inactive compatibility
  try {
    $roleInfo = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'")->fetch(PDO::FETCH_ASSOC);
    $roleType = strtolower((string)($roleInfo['Type'] ?? ''));
    if (strpos($roleType, "'staff'") === false) {
      $pdo->exec("ALTER TABLE users MODIFY role ENUM('admin','staff','customer') NOT NULL DEFAULT 'customer'");
    }
  } catch (Throwable $e) {
    // Keep page usable if schema migration cannot be applied.
  }

  try {
    $statusInfo = $pdo->query("SHOW COLUMNS FROM users LIKE 'status'")->fetch(PDO::FETCH_ASSOC);
    $statusType = strtolower((string)($statusInfo['Type'] ?? ''));
    if (strpos($statusType, "'locked'") === false || strpos($statusType, "'inactive'") === false) {
      $pdo->exec("ALTER TABLE users MODIFY status ENUM('active','locked','inactive') NOT NULL DEFAULT 'active'");
    }
  } catch (Throwable $e) {
    // Keep page usable if schema migration cannot be applied.
  }

$successMessage = null;
$errorMessage = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = '';
    if (isset($_POST['add_user'])) {
        $action = 'add';
    } elseif (isset($_POST['update_user'])) {
        $action = 'update';
    } elseif (isset($_POST['toggle_status'])) {
        $action = 'toggle';
    } elseif (isset($_POST['reset_password'])) {
        $action = 'reset';
    }

    if ($action === 'add' || $action === 'update') {
        $fullname = trim($_POST['fullname'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
      $roleRaw = trim((string)($_POST['role'] ?? 'customer'));
      $role = in_array($roleRaw, ['admin', 'staff', 'customer'], true) ? $roleRaw : 'customer';
        $password = trim($_POST['password'] ?? '');
        $id = (int)($_POST['user_id'] ?? 0);

      if ($action === 'add' && (!$fullname || !$username || !$email)) {
        $errorMessage = 'Vui lòng điền đầy đủ thàng tin bắt buộc.';
        } elseif ($action === 'update' && (!$fullname || !$username || !$email || $id <= 0)) {
            $errorMessage = 'Dữ liệu không hợp lệ cho cập nhật tài khoản.';
        } else {
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?) AND id <> ?");
            $checkStmt->execute([$username, $email, $id]);
            if ($checkStmt->fetchColumn() > 0) {
                $errorMessage = 'Tên đăng nhập hoặc email đã được sử dụng.';
            } else {
                if ($action === 'add') {
                $plainPassword = $password !== '' ? $password : $defaultPassword;
                $hash = password_hash($plainPassword, PASSWORD_DEFAULT);
                    $insert = $pdo->prepare("INSERT INTO users (full_name, username, email, password_hash, role, status) VALUES (?, ?, ?, ?, ?, 'active')");
                    $insert->execute([$fullname, $username, $email, $hash, $role]);
                if ($password === '') {
                  $successMessage = "Đã tạo tài khoản thành công. Mật khẩu khởi tạo: $defaultPassword";
                } else {
                  $successMessage = 'Đã tạo tài khoản thành công.';
                }
                } else {
                    $fields = ['full_name = ?', 'username = ?', 'email = ?', 'role = ?'];
                    $params = [$fullname, $username, $email, $role, $id];
                    if ($password !== '') {
                        $fields[] = 'password_hash = ?';
                        $params = [$fullname, $username, $email, $role, password_hash($password, PASSWORD_DEFAULT), $id];
                    }
                    $update = $pdo->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?');
                    $update->execute($params);
                    $successMessage = 'Đã cập nhật tài khoản thành công.';
                }
            }
        }
    }

    if ($action === 'toggle') {
        $id = (int)($_POST['user_id'] ?? 0);
        if ($id > 0) {
            $userStmt = $pdo->prepare('SELECT status FROM users WHERE id = ?');
            $userStmt->execute([$id]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
          $currentStatus = (string)($user['status'] ?? 'active');
          $isLocked = in_array($currentStatus, ['locked', 'inactive'], true);
          $newStatus = $isLocked ? 'active' : 'locked';
                $pdo->prepare('UPDATE users SET status = ? WHERE id = ?')->execute([$newStatus, $id]);
                $successMessage = $newStatus === 'active' ? 'Đã mở khóa tài khoản.' : 'Đã khóa tài khoản.';
            }
        }
    }

    if ($action === 'reset') {
        $id = (int)($_POST['user_id'] ?? 0);
        if ($id > 0) {
            $hash = password_hash($defaultPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $stmt->execute([$hash, $id]);
            $successMessage = "Đã khởi tạo mật khẩu thành công: $defaultPassword";
        }
    }
}

$editUser = null;
if (isset($_GET['edit_id'])) {
    $editId = (int)$_GET['edit_id'];
    if ($editId > 0) {
        $stmt = $pdo->prepare('SELECT id, full_name AS fullname, username, email, role, status FROM users WHERE id = ?');
        $stmt->execute([$editId]);
        $editUser = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}

$users = $pdo->query('SELECT id, full_name AS fullname, username, email, role, status, created_at FROM users ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
function roleLabel($role) {
    return $role === 'admin' ? 'Quản lý' : ($role === 'staff' ? 'Nhân viên' : 'Khách hàng');
}
function roleBadge($role) {
    return $role === 'admin' ? 'badge-gold' : ($role === 'staff' ? 'badge-muted' : 'badge-muted');
}
function statusLabel($status) {
  return $status === 'active' ? 'Hoạt động' : 'Đã khóa';
}
function statusBadge($status) {
  return $status === 'active' ? 'badge-success' : 'badge-danger';
}
?>
        <!-- ─── USERS ─── -->
        <section class="admin-page<?php echo ($page === 'users') ? ' active' : ''; ?>" id="page-users">
          <div class="page-header">
            <div class="page-header-left">
              <span class="eyebrow">✦ Hệ thống</span>
              <h1>Quản lý khách hàng</h1>
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
                    <th>Họ tên</th>
                    <th>Tên đăng nhập</th>
                    <th>Email</th>
                    <th>Vai trò</th>
                    <th>Trạng thái</th>
                    <th>Ngày tạo</th>
                    <th>Thao tác</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($users)): ?>
                    <tr>
                      <td colspan="7" class="td-muted" style="text-align:center;">Chưa có tài khoản nào.</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($users as $user): ?>
                      <tr>
                        <td class="td-name"><?php echo htmlspecialchars($user['fullname']); ?></td>
                        <td class="td-muted"><?php echo htmlspecialchars($user['username']); ?></td>
                        <td class="td-muted"><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><span class="badge <?php echo roleBadge($user['role']); ?>"><?php echo roleLabel($user['role']); ?></span></td>
                        <td><span class="badge <?php echo statusBadge($user['status']); ?>"><?php echo statusLabel($user['status']); ?></span></td>
                        <td class="td-muted"><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                        <td>
                          <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                            <a class="btn btn-ghost btn-sm" href="index.php?page=users&amp;edit_id=<?php echo $user['id']; ?>">✏ Sửa</a>
                            <form method="post" style="display:inline;">
                              <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>" />
                              <button type="submit" name="reset_password" class="btn btn-ghost btn-sm">Khoi tao</button>
                            </form>
                            <form method="post" style="display:inline;">
                              <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>" />
                              <?php $isLocked = in_array((string)$user['status'], ['locked', 'inactive'], true); ?>
                              <button type="submit" name="toggle_status" class="btn btn-sm <?php echo !$isLocked ? 'btn-danger' : 'btn-ghost'; ?>">
                                <?php echo !$isLocked ? 'Khoa' : 'Mo'; ?>
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
              <?php if ($editUser): ?>
                <h3 style="font-family: 'Playfair Display', serif; font-size: 1.1rem; margin-bottom: 1.5rem;">Chỉnh sửa tài khoản</h3>
              <?php else: ?>
                <h3 style="font-family: 'Playfair Display', serif; font-size: 1.1rem; margin-bottom: 1.5rem;">Thêm tài khoản mới</h3>
              <?php endif; ?>

              <form method="post">
                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($editUser['id'] ?? ''); ?>" />
                <div class="form-row">
                  <div class="form-group">
                    <label>Họ tên</label>
                    <input class="form-control" name="fullname" type="text" value="<?php echo htmlspecialchars($editUser['fullname'] ?? ''); ?>" required />
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label>Tên đăng nhập</label>
                    <input class="form-control" name="username" type="text" value="<?php echo htmlspecialchars($editUser['username'] ?? ''); ?>" required />
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label>Email</label>
                    <input class="form-control" name="email" type="email" value="<?php echo htmlspecialchars($editUser['email'] ?? ''); ?>" required />
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label>Vai trò</label>
                    <select class="form-control" name="role">
                      <option value="customer" <?php echo (isset($editUser['role']) && $editUser['role'] === 'customer') ? 'selected' : ''; ?>>Khách hàng</option>
                      <option value="staff" <?php echo (isset($editUser['role']) && $editUser['role'] === 'staff') ? 'selected' : ''; ?>>Nhân viên</option>
                      <option value="admin" <?php echo (isset($editUser['role']) && $editUser['role'] === 'admin') ? 'selected' : ''; ?>>Quản lý</option>
                    </select>
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label>Mật khẩu <?php echo $editUser ? '(Để trống nếu không đổi)' : '(để trống sẽ dòng mật khẩu khởi tạo)'; ?></label>
                    <input class="form-control" name="password" type="text" placeholder="<?php echo $editUser ? 'Mật khẩu mới (nếu muốn)' : 'Mật khẩu (tùy chọn)'; ?>" />
                  </div>
                </div>
                <button type="submit" name="<?php echo $editUser ? 'update_user' : 'add_user'; ?>" class="btn btn-gold">
                  <?php echo $editUser ? 'Cập nhật tài khoản' : 'Thêm tài khoản'; ?>
                </button>
                <?php if ($editUser): ?>
                  <a href="index.php?page=users" class="btn btn-ghost">Hủy</a>
                <?php endif; ?>
              </form>

              <div style="margin-top: 1.5rem; font-size: 0.9rem; color: var(--muted);">
                <p>Mật khẩu mặc định khi khởi tạo: <strong><?php echo htmlspecialchars($defaultPassword); ?></strong></p>
                <p>Tại đây bạn có thể khóa/mở khóa tài khoản và khởi tạo lại mật khẩu.</p>
              </div>
            </div>
          </div>
        </section>

