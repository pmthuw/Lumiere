<?php
// Header navigation component
?>
<!-- ══════════ HEADER ══════════ -->
<header class="site-header">
  <div class="header-inner">
    <a class="brand" href="index.php">LUMIERE</a>
    <nav class="main-nav">
      <a href="index.php" class="active">Trang chủ</a>
      <a href="index.php#products">Sản phẩm</a>
    </nav>
    <div class="header-actions">
      <div class="header-greeting" id="header-greeting"></div>
      <button class="icon-btn" onclick="openSearch()" title="Tìm kiếm">
        <i class="fa-solid fa-magnifying-glass"></i>
      </button>
      <button class="icon-btn" onclick="openCart()" title="Giỏ hàng">
        <i class="fa-solid fa-bag-shopping"></i>
        <span class="cart-badge" id="cart-badge">0</span>
      </button>
      <div class="user-menu-wrap">
        <button
          class="icon-btn"
          onclick="toggleUserMenu()"
          title="Tài khoản"
        >
          <i class="fa-regular fa-user"></i>
        </button>
        <div class="user-dropdown" id="user-dropdown">
          <div id="guest-menu">
            <a href="#" onclick="openAuth('login')">Đăng nhập</a>
            <a href="#" onclick="openAuth('register')">Đăng ký</a>
          </div>
          <div id="user-menu-logged" style="display: none">
            <div class="user-dropdown-title" id="dropdown-title"></div>
            <div class="divider"></div>
            <button onclick="openProfile()">Hồ sơ của tôi</button>
            <button onclick="openOrders()">Đơn hàng của tôi</button>
            <button class="btn-logout" onclick="logout()">Đăng xuất</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</header>
