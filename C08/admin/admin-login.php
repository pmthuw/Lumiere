<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/../setup_db.php';

$defaultCred = [
    'username' => 'admin',
    'password' => '12345',
    'fullname' => 'Quản trị viên',
    'email' => 'admin@lumiere.vn',
    'status' => 'active',
];
$errorMessage = '';
if (!empty($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['admin_user'] ?? '');
    $pass = $_POST['admin_pass'] ?? '';
    $found = null;

    // Kiểm tra từ database trước
    if (isset($pdo) && ($pdo instanceof PDO)) {
        try {
            $stmt = $pdo->prepare('SELECT id, username, email, password_hash, full_name, status FROM admin_users WHERE username = ? AND status = ?');
            $stmt->execute([$user, 'active']);
            $dbUser = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($dbUser) {
                $storedPassword = (string)($dbUser['password_hash'] ?? '');
                $isValid = $storedPassword !== '' && hash_equals($storedPassword, $pass);
                if ($isValid) {
                    $found = [
                        'username' => $dbUser['username'],
                        'fullname' => $dbUser['full_name'] ?? $dbUser['username'],
                        'email' => $dbUser['email'],
                        'status' => $dbUser['status'],
                    ];
                }
            }
        } catch (Throwable $e) {
            // Fallback nếu DB lỗi
        }
    }

    // Fallback kiểm tra default cred nếu không tìm thấy trong DB
    if (!$found && $user === $defaultCred['username'] && $pass === $defaultCred['password']) {
        $found = $defaultCred;
    }

    if ($found) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user'] = [
            'username' => $found['username'],
            'fullname' => $found['fullname'] ?? $found['username'],
        ];
        header('Location: index.php');
        exit;
    }
    $errorMessage = 'Tên đăng nhập hoặc mật khẩu không đúng!';
}
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>LUMIERE | Admin Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link
      href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,700;1,500&family=Jost:wght@300;400;500;600&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="../frontend/styles/admin.css" />
    <style>
      body {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
        overflow: hidden;
      }
      .login-bg {
        position: fixed;
        inset: 0;
        background:
          radial-gradient(
            ellipse at 20% 50%,
            rgba(201, 169, 110, 0.08) 0%,
            transparent 60%
          ),
          radial-gradient(
            ellipse at 80% 20%,
            rgba(201, 169, 110, 0.05) 0%,
            transparent 50%
          ),
          #06060a;
      }
      .login-bg::before {
        content: "";
        position: fixed;
        inset: 0;
        background-image:
          repeating-linear-gradient(
            0deg,
            transparent,
            transparent 39px,
            rgba(255, 255, 255, 0.015) 39px,
            rgba(255, 255, 255, 0.015) 40px
          ),
          repeating-linear-gradient(
            90deg,
            transparent,
            transparent 39px,
            rgba(255, 255, 255, 0.015) 39px,
            rgba(255, 255, 255, 0.015) 40px
          );
      }
      .login-wrap {
        position: relative;
        z-index: 2;
        width: 100%;
        max-width: 440px;
        padding: 1rem;
        animation: fadeUp 0.6s ease both;
      }
      @keyframes fadeUp {
        from {
          opacity: 0;
          transform: translateY(20px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }
      .login-card {
        background: rgba(14, 14, 20, 0.95);
        border: 1px solid rgba(201, 169, 110, 0.2);
        border-radius: 2rem;
        padding: 3rem 2.5rem;
        box-shadow:
          0 40px 100px rgba(0, 0, 0, 0.6),
          0 0 0 1px rgba(201, 169, 110, 0.05) inset;
      }
      .login-logo {
        text-align: center;
        margin-bottom: 2.5rem;
      }
      .login-logo .brand {
        font-family: "Playfair Display", serif;
        font-size: 2.2rem;
        letter-spacing: 0.2em;
        color: var(--gold);
        display: block;
      }
      .login-logo .subtitle {
        font-size: 0.7rem;
        letter-spacing: 0.3em;
        text-transform: uppercase;
        color: var(--muted);
        margin-top: 0.4rem;
        display: block;
      }
      .login-divider {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 2rem;
      }
      .login-divider::before,
      .login-divider::after {
        content: "";
        flex: 1;
        height: 1px;
        background: rgba(201, 169, 110, 0.2);
      }
      .login-divider span {
        color: var(--gold);
        font-size: 0.8rem;
      }
      .login-title {
        font-family: "Playfair Display", serif;
        font-size: 1.6rem;
        margin-bottom: 0.4rem;
        text-align: center;
      }
      .login-sub {
        text-align: center;
        color: var(--muted);
        font-size: 0.85rem;
        margin-bottom: 2rem;
      }
      .form-group {
        margin-bottom: 1.25rem;
      }
      .form-group label {
        display: block;
        font-size: 0.72rem;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: var(--muted);
        margin-bottom: 0.5rem;
      }
      .form-group input {
        width: 100%;
        padding: 0.9rem 1.2rem;
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 1rem;
        color: var(--text);
        font-family: "Jost", sans-serif;
        font-size: 0.95rem;
        outline: none;
        transition:
          border-color 0.2s,
          box-shadow 0.2s;
      }
      .form-group input:focus {
        border-color: var(--gold);
        box-shadow: 0 0 0 3px rgba(201, 169, 110, 0.1);
      }
      .form-group input::placeholder {
        color: rgba(138, 128, 112, 0.6);
      }
      .btn-login {
        width: 100%;
        padding: 1rem;
        background: var(--gold);
        color: #080808;
        border: none;
        border-radius: 999px;
        font:
          600 0.85rem/1 "Jost",
          sans-serif;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        cursor: pointer;
        margin-top: 0.5rem;
        transition: all 0.25s;
      }
      .btn-login:hover {
        background: #d9bc82;
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(201, 169, 110, 0.3);
      }
      .error-msg {
        background: rgba(230, 80, 80, 0.1);
        border: 1px solid rgba(230, 80, 80, 0.3);
        color: #e65;
        border-radius: 0.75rem;
        padding: 0.75rem 1rem;
        font-size: 0.85rem;
        margin-top: 1rem;
        display: none;
        text-align: center;
      }
      .error-msg.show {
        display: block;
        animation: shake 0.3s ease;
      }
      @keyframes shake {
        0%,
        100% {
          transform: translateX(0);
        }
        25% {
          transform: translateX(-6px);
        }
        75% {
          transform: translateX(6px);
        }
      }
      .login-footer {
        text-align: center;
        margin-top: 2rem;
        font-size: 0.78rem;
        color: rgba(138, 128, 112, 0.45);
      }
      .login-hint {
        text-align: center;
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid var(--border);
        font-size: 0.78rem;
        color: var(--muted);
      }
      .login-hint strong {
        color: var(--gold);
      }
    </style>
  </head>
  <body>
    <div class="login-bg"></div>
    <div class="login-wrap">
      <div class="login-card">
        <div class="login-logo">
          <span class="brand">LUMIERE</span>
          <span class="subtitle">Admin Portal</span>
        </div>
        <div class="login-divider"><span>✦</span></div>
        <h1 class="login-title">Đăng nhập</h1>
        <p class="login-sub">Dành riêng cho quản trị viên</p>

        <form method="post" novalidate>
          <div class="form-group">
            <label for="admin-user">Tên đăng nhập</label>
            <input
              type="text"
              id="admin-user"
              name="admin_user"
              value="<?php echo htmlspecialchars($_POST['admin_user'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
              placeholder="Tên đăng nhập"
              autocomplete="username"
              required
            />
          </div>
          <div class="form-group">
            <label for="admin-pass">Mật khẩu</label>
            <input
              type="password"
              id="admin-pass"
              name="admin_pass"
              placeholder="Mật khẩu"
              autocomplete="current-password"
              required
            />
          </div>

          <button type="submit" class="btn-login">Đăng nhập →</button>
          <div class="error-msg<?php echo $errorMessage ? ' show' : ''; ?>" id="login-error">
            <?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?>
          </div>
        </form>
      </div>
      <div class="login-footer">© 2026 LUMIERE. Admin Panel v2.0</div>
    </div>

    <script>
      document.addEventListener('DOMContentLoaded', () => {
        const form = document.querySelector('form');
        form.addEventListener('submit', () => {
          document.getElementById('login-error').classList.remove('show');
        });
      });
    </script>
  </body>
</html>


