<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../setup_db.php';

$errorMessage = '';
$identifier = trim($_POST['identifier'] ?? '');
$redirectTo = trim($_GET['redirect'] ?? $_POST['redirect'] ?? 'index.php');
if ($redirectTo === '' || str_contains($redirectTo, '://')) {
    $redirectTo = 'index.php';
}

if (!empty($_SESSION['customer_logged_in']) && !(isset($_GET['ok']) && $_GET['ok'] === '1')) {
    header('Location: ' . $redirectTo);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';

    if ($identifier === '' || $password === '') {
        $errorMessage = 'Vui lòng nhập tên đăng nhập và mật khẩu.';
  } elseif (!isset($pdo) || !($pdo instanceof PDO)) {
    $errorMessage = 'Không thể kết nối dữ liệu. Vui lòng thử lại.';
    } else {
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
                $errorMessage = 'Tài khoản không tồn tại.';
            } elseif (in_array((string)($user['status'] ?? 'active'), ['locked', 'inactive'], true)) {
                $errorMessage = 'Tài khoản đã bị khóa hoặc tạm ngưng.';
            } else {
              $storedPassword = (string)($user['password_hash'] ?? '');
              $isValid = $storedPassword !== '' && hash_equals($storedPassword, $password);

                if (!$isValid) {
                    $errorMessage = 'Mật khẩu không đúng.';
                } else {
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

                    header('Location: login.php?ok=1&redirect=' . urlencode($redirectTo));
                    exit;
                }
            }
        } catch (Throwable $e) {
            $errorMessage = 'Không thể kết nối dữ liệu. Vui lòng thử lại.';
        }
    }
}

$loggedInUser = $_SESSION['customer_user'] ?? null;
$loginSuccess = isset($_GET['ok']) && $_GET['ok'] === '1' && is_array($loggedInUser);
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Đăng nhập | Bella Parfum</title>
    <link rel="stylesheet" href="styles/style.css" />
  </head>
  <body>
    <header class="site-header">
      <div class="container header-inner">
        <a class="brand" href="index.php">LUMIERE</a>
        <nav class="main-nav">
          <a href="index.php">Trang chủ</a>
          <a href="index.php#products">Sản phẩm</a>
          <a href="login.php" class="active">Đăng nhập</a>
        </nav>
      </div>
    </header>

    <main>
      <section class="hero-banner">
        <div class="container hero-grid">
          <div class="hero-copy">
            <span class="eyebrow">Account</span>
            <h1>Đăng nhập để tiếp tục.</h1>
            <p>
              Truy cập nhanh để theo dõi đơn hàng và nhận ưu đãi dành riêng cho bạn.
            </p>
          </div>
          <div class="hero-image">
            <div class="hero-image-card"></div>
          </div>
        </div>
      </section>

      <section class="contact-section">
        <div class="container contact-grid">
          <div class="contact-info">
            <h2>Tài khoản LUMIERE</h2>
            <p>
              Trang đăng nhập này xử lý bằng PHP và kiểm tra trực tiếp từ cơ sở dữ liệu.
            </p>
          </div>
          <form class="contact-form" method="post" action="login.php<?php echo $redirectTo !== 'index.php' ? '?redirect=' . urlencode($redirectTo) : ''; ?>">
            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirectTo); ?>" />

            <?php if ($errorMessage !== ''): ?>
              <div style="margin-bottom:1rem;padding:0.75rem 0.9rem;border-radius:0.8rem;border:1px solid rgba(255,95,95,.5);background:rgba(255,95,95,.08);color:#ffd2d2;">
                <?php echo htmlspecialchars($errorMessage); ?>
              </div>
            <?php endif; ?>

            <?php if ($loginSuccess): ?>
              <div style="margin-bottom:1rem;padding:0.75rem 0.9rem;border-radius:0.8rem;border:1px solid rgba(62,206,137,.45);background:rgba(62,206,137,.08);color:#c9ffd7;">
                Đăng nhập thành công. Đang chuyển hướng...
              </div>
            <?php endif; ?>

            <div class="form-grid">
              <div class="form-group full">
                <label for="identifier">Email hoặc tên đăng nhập</label>
                <input
                  id="identifier"
                  type="text"
                  name="identifier"
                  placeholder="Nhập email hoặc username"
                  value="<?php echo htmlspecialchars($identifier); ?>"
                  required
                />
              </div>

              <div class="form-group full">
                <label for="password">Mật khẩu</label>
                <input id="password" type="password" name="password" placeholder="Nhập mật khẩu" required />
              </div>

              <div class="form-group full">
                <button class="btn btn-gold" style="width: 100%" type="submit">Đăng nhập</button>
              </div>
            </div>
          </form>
        </div>
      </section>
    </main>

    <footer class="site-footer">
      <div class="container footer-grid">
        <div>
          <h3>Bella Parfum</h3>
          <p>Trải nghiệm nước hoa cao cấp với phong cách đậm chất luxury.</p>
        </div>
        <div>
          <h4>Liên hệ</h4>
          <p>Email: support@bellaperfum.vn</p>
          <p>Hotline: 0909 123 456</p>
        </div>
        <div>
          <h4>Địa chỉ</h4>
          <p>123 Lê Lợi, Quận 1, TP.HCM</p>
        </div>
      </div>
      <div class="footer-bottom">
        <p>© 2026 Bella Parfum. Tất cả quyền được bảo lưu.</p>
      </div>
    </footer>

    <?php if ($loginSuccess && $loggedInUser): ?>
      <script>
        (function () {
          const next = <?php echo json_encode($redirectTo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
          window.location.replace(next || "index.php");
        })();
      </script>
    <?php endif; ?>
  </body>
</html>
