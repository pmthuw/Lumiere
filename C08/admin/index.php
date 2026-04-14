<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: text/html; charset=UTF-8');
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: admin-login.php');
    exit;
}
$adminUser = $_SESSION['admin_user'] ?? ['username' => 'admin', 'fullname' => 'Quản trị viên'];
$page = $_GET['page'] ?? 'dashboard';
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>LUMIERE | Admin Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link
      href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,700;1,500&family=Jost:wght@300;400;500;600&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="../frontend/styles/admin.css" />
  </head>
  <body>
    <div class="admin-layout">
      <!-- ══════════ TOPBAR ══════════ -->
      <header class="admin-topbar">
        <span class="topbar-brand">LUMIERE</span>
        <span class="topbar-badge">Quản lý</span>
        <div class="topbar-spacer"></div>
        <div class="topbar-info">
          <span id="topbar-date"></span>
          <div class="topbar-avatar">A</div>
          <span>Quản lý</span>
          <button class="topbar-logout" onclick="adminLogout()">
            Đăng xuất
          </button>
        </div>
      </header>

      <!-- ══════════ SIDEBAR ══════════ -->
      <aside class="admin-sidebar">
        <span class="sidebar-section-label">Tổng quan</span>
        <a
          class="sidebar-link <?php echo ($page === 'dashboard') ? 'active' : ''; ?>"
          href="index.php?page=dashboard"
        >
          <span class="icon">◈</span> Dashboard
        </a>

        <span class="sidebar-section-label">Danh mục</span>
        <a class="sidebar-link <?php echo ($page === 'categories') ? 'active' : ''; ?>" href="index.php?page=categories">
          <span class="icon">◑</span> Loại sản phẩm
        </a>
        <a class="sidebar-link <?php echo ($page === 'products') ? 'active' : ''; ?>" href="index.php?page=products">
          <span class="icon">◻</span> Sản phẩm
        </a>

        <span class="sidebar-section-label">Nhập hàng & Giá</span>
        <a class="sidebar-link <?php echo ($page === 'receipts') ? 'active' : ''; ?>" href="index.php?page=receipts">
          <span class="icon">◧</span> Phiếu nhập hàng
        </a>
        <a class="sidebar-link <?php echo ($page === 'pricing') ? 'active' : ''; ?>" href="index.php?page=pricing">
          <span class="icon">◈</span> Quản lý giá bán
        </a>

        <span class="sidebar-section-label">Giao dịch</span>
        <a class="sidebar-link <?php echo ($page === 'orders') ? 'active' : ''; ?>" href="index.php?page=orders">
          <span class="icon">◫</span> Đơn hàng
        </a>

        <span class="sidebar-section-label">Báo cáo</span>
        <a class="sidebar-link <?php echo ($page === 'inventory') ? 'active' : ''; ?>" href="index.php?page=inventory">
          <span class="icon">◰</span> Tồn kho
        </a>
        <a class="sidebar-link <?php echo ($page === 'report') ? 'active' : ''; ?>" href="index.php?page=report">
          <span class="icon">◈</span> Báo cáo
        </a>

        <span class="sidebar-section-label">Hệ thống</span>
        <a class="sidebar-link <?php echo ($page === 'users') ? 'active' : ''; ?>" href="index.php?page=users">
          <span class="icon">◉</span> Quản lý người dùng
        </a>
      </aside>

      <!-- ══════════ MAIN ══════════ -->
      <main class="admin-main">
        <?php
        $allowedPages = [
            'dashboard' => 'admin-dashboard.php',
            'categories' => 'admin-categories.php',
            'products' => 'admin-products.php',
            'receipts' => 'admin-receipts.php',
            'pricing' => 'admin-pricing.php',
            'orders' => 'admin-orders.php',
            'inventory' => 'admin-inventory.php',
            'report' => 'admin-report.php',
            'users' => 'admin-users.php'
        ];
        if (array_key_exists($page, $allowedPages)) {
            include $allowedPages[$page];
        } else {
            include 'admin-dashboard.php';
        }
        ?>
      </main>
    </div>

    <?php include 'admin-modals.php'; ?>

    <script src="../frontend/js/admin.js"></script>
    <script>
      window.ADMIN_SESSION = <?php echo json_encode($adminUser, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
      // Categories now handled server-side, no need for dynamic population
      // window.addEventListener("DOMContentLoaded", () => {
      //   const catFilters = [document.getElementById("prod-cat-filter")];
      //   catFilters.forEach((sel) => {
      //     if (!sel) return;
      //     CATEGORIES.forEach((c) => {
      //       const o = document.createElement("option");
      //       o.value = c;
      //       o.textContent = c;
      //       sel.appendChild(o);
      //     });
      //   });
      // });
    </script>
  </body>
</html>

