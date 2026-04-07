<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="styles/style.css">
  <title>Sản phẩm | Bella Parfum</title>
</head>
<body>
  <header class="site-header">
    <div class="container header-inner">
      <a class="brand" href="index.php">LUMIERE</a>
      <nav class="main-nav">
        <a href="index.php">Trang chủ</a>
        <a href="product.php" class="active">Sản phẩm</a>
        <a href="login.php">Đăng nhập</a>
      </nav>
    </div>
  </header>

  <main>
    <section class="hero-banner">
      <div class="container hero-grid">
        <div class="hero-copy">
          <span class="eyebrow">Collection</span>
          <h1>Bộ sưu tập hương thơm xa hoa.</h1>
          <p>Khám phá những chai nước hoa đẳng cấp được chọn lọc cho phong cách thời thượng và sang trọng.</p>
          <div class="hero-actions">
            <a href="product.php" class="btn btn-primary">Xem toàn bộ</a>
            <a href="#products" class="btn btn-secondary">Sản phẩm nổi bật</a>
          </div>
        </div>
        <div class="hero-image">
          <div class="hero-image-card"></div>
        </div>
      </div>
    </section>

    <section id="products" class="featured-products">
      <div class="container section-intro">
        <span class="eyebrow" id="section-eyebrow">Sản phẩm</span>
        <h2 id="section-title">Những chai nước hoa được yêu thích</h2>
        <p>Thiết kế tinh tế và hương thơm độc đáo dành cho những người yêu cái đẹp.</p>
      </div>

      <div class="container" style="margin-bottom: 1rem;">
        <div class="category-tabs" id="category-tabs">
          <button class="tab-btn active" onclick="filterCategory('', this)">Tất cả</button>
          <button class="tab-btn" onclick="filterCategory('Nữ', this)">Nước hoa Nữ</button>
          <button class="tab-btn" onclick="filterCategory('Nam', this)">Nước hoa Nam</button>
          <button class="tab-btn" onclick="filterCategory('Unisex', this)">Unisex</button>
          <button class="tab-btn" onclick="filterCategory('Limited', this)">Limited</button>
        </div>
      </div>

      <div class="container" style="margin-bottom: 0.75rem; color: var(--muted); font-size: 0.9rem;" id="result-count"></div>
      <div class="container product-grid" id="product-grid"></div>
      <p class="container page-info" id="page-info"></p>
      <div class="container pagination" id="pagination" aria-label="Pagination"></div>
    </section>
  </main>

  <footer class="site-footer">
    <div class="container footer-grid">
      <div>
        <h3>Bella Parfum</h3>
        <p>Trải nghiệm nước hoa chính hãng với phong cách riêng của bạn.</p>
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

  <script src="js/main.js"></script>
</body>
</html>
