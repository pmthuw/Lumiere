<?php
// Get parameters from URL
$category = isset($_GET['category']) ? htmlspecialchars($_GET['category']) : '';
$search = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
$priceMinRaw = trim((string)($_GET['priceMin'] ?? ''));
$priceMaxRaw = trim((string)($_GET['priceMax'] ?? ''));
$priceMin = ($priceMinRaw !== '') ? max(0, (int)$priceMinRaw) : null;
$priceMax = ($priceMaxRaw !== '') ? max(0, (int)$priceMaxRaw) : null;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 6;

if ($priceMin !== null && $priceMax !== null && $priceMin > $priceMax) {
  [$priceMin, $priceMax] = [$priceMax, $priceMin];
}

require_once __DIR__ . '/../../setup_db.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
  ?>
  <section id="products">
    <div class="section-container">
      <p style="text-align:center;color:var(--muted);padding:2rem;border:1px solid var(--border);border-radius:1rem;">
        Không thể kết nối cơ sở dữ liệu để tải sản phẩm. Vui lòng kiểm tra MySQL và thử lại.
      </p>
    </div>
  </section>
  <?php
  return;
}

$hasCategoryStatus = false;
try {
  $categoryStatusCheck = $pdo->query("SHOW COLUMNS FROM categories LIKE 'status'");
  $hasCategoryStatus = (bool)$categoryStatusCheck->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $hasCategoryStatus = false;
}

if ($hasCategoryStatus) {
  $categoryStmt = $pdo->query("SELECT name FROM categories WHERE status = 'active' ORDER BY name");
} else {
  $categoryStmt = $pdo->query("SELECT name FROM categories ORDER BY name");
}
$activeCategories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);

$hasStatusColumn = false;
try {
  $statusCheckStmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'status'");
  $hasStatusColumn = (bool)$statusCheckStmt->fetch(PDO::FETCH_ASSOC);
  if (!$hasStatusColumn) {
    $pdo->exec("ALTER TABLE products ADD COLUMN status ENUM('active','hidden') NOT NULL DEFAULT 'active'");
    $hasStatusColumn = true;
  }
} catch (Throwable $e) {
  $hasStatusColumn = false;
}

// Build query
$sql = "SELECT * FROM products WHERE 1=1";
$params = [];

if ($hasStatusColumn) {
  $sql .= " AND status = 'active'";
}

if (!empty($category)) {
    $sql .= " AND category = ?";
    $params[] = $category;
}

if (!empty($search)) {
    $sql .= " AND (name LIKE ? OR brand LIKE ? OR notes LIKE ?)";
    $searchTerm = '%' . $search . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($priceMin !== null || $priceMax !== null) {
  $minValue = $priceMin ?? 0;
  $maxValue = $priceMax ?? 999999999;
    $sql .= " AND price BETWEEN ? AND ?";
  $params[] = $minValue;
  $params[] = $maxValue;
}

// Count total
$countSql = preg_replace('/SELECT \*/i', 'SELECT COUNT(*) as total', explode(' ORDER BY', $sql)[0]);
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalProducts = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalProducts / $perPage);

// Fetch products
$sql .= " ORDER BY id LIMIT ?, ?";
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare($sql);
foreach ($params as $idx => $value) {
  $stmt->bindValue($idx + 1, $value);
}
$stmt->bindValue(count($params) + 1, $offset, PDO::PARAM_INT);
$stmt->bindValue(count($params) + 2, $perPage, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

function formatImagePath($image) {
    $image = trim((string)$image);
    if (empty($image)) return '/frontend/images/default.jpg';
    if (str_starts_with($image, 'http') || str_starts_with($image, '/')) return $image;
    if (str_starts_with($image, 'frontend/')) return '/' . $image;
    if (str_starts_with($image, 'images/')) return '/frontend/' . $image;
    return '/frontend/images/' . $image;
}

function formatPrice($price) {
    return number_format($price, 0, '.', ',');
}

function buildFilterQuery(array $params): string {
  $filtered = array_filter($params, static function ($value) {
    return $value !== '' && $value !== null;
  });
  return http_build_query($filtered);
}

$baseFilterParams = [
  'search' => $search,
  'category' => $category,
  'priceMin' => $priceMin,
  'priceMax' => $priceMax,
];
?>

<!-- ══════════ PRODUCTS SECTION ══════════ -->
<section id="products">
  <div class="section-container">
    <!-- SEARCH FORM -->
    <form method="GET" class="search-box" style="margin-bottom: 2rem">
      <div class="search-basic">
        <div class="search-input-wrap">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8" />
            <path d="m21 21-4.35-4.35" />
          </svg>
          <input
            class="search-input"
            id="search-input"
            type="text"
            name="search"
            placeholder="Tìm kiếm sản phẩm..."
            value="<?php echo $search; ?>"
          />
        </div>
        <button class="btn btn-gold" type="submit">Tìm kiếm</button>
      </div>

      <div class="advanced-panel open" id="advanced-panel">
        <div>
          <span class="filter-label">Phân loại</span>
          <select class="filter-select" name="category" onchange="this.form.submit()">
            <option value="">Tất cả phân loại</option>
            <?php foreach ($activeCategories as $catName): ?>
              <option value="<?php echo htmlspecialchars($catName); ?>" <?php echo $category === $catName ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($catName); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <span class="filter-label">Khoảng giá (VNĐ)</span>
          <div class="price-range">
            <input
              class="filter-input"
              type="number"
              name="priceMin"
              placeholder="Từ"
              min="0"
              value="<?php echo $priceMin !== null ? $priceMin : ''; ?>"
            />
            <input
              class="filter-input"
              type="number"
              name="priceMax"
              placeholder="Đến"
              min="0"
              value="<?php echo $priceMax !== null ? $priceMax : ''; ?>"
            />
          </div>
        </div>

        <div style="display: flex; align-items: flex-end; gap: 1rem;">
          <button class="btn btn-gold" type="submit" style="flex: 1;">Lọc</button>
          <a href="?page=1" class="btn btn-ghost" style="text-decoration: none; text-align: center; flex: 1;">Xóa bộ lọc</a>
        </div>
      </div>
    </form>

    <!-- SECTION HEADER -->
    <div class="section-header">
      <div class="section-title-block">
        <span class="eyebrow">Featured Products</span>
        <h2 class="section-title">Sản phẩm nổi bật</h2>
      </div>
      <span style="color: var(--muted); font-size: 0.85rem;" id="result-count">
        Hiển thị <?php echo count($products); ?> / <?php echo $totalProducts; ?> sản phẩm
      </span>
    </div>

    <!-- CATEGORY TABS -->
    <div class="category-tabs" id="category-tabs">
      <a href="?<?php echo htmlspecialchars(buildFilterQuery(array_merge($baseFilterParams, ['category' => '', 'page' => 1]))); ?>" class="tab-btn <?php echo empty($category) ? 'active' : ''; ?>" onclick="return true;">
        Tất cả
      </a>
      <?php foreach ($activeCategories as $catName): ?>
        <a href="?<?php echo htmlspecialchars(buildFilterQuery(array_merge($baseFilterParams, ['category' => $catName, 'page' => 1]))); ?>" class="tab-btn <?php echo $category === $catName ? 'active' : ''; ?>">
          <?php echo htmlspecialchars($catName); ?>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- PRODUCT GRID -->
    <div class="product-grid" id="product-grid">
      <?php if (empty($products)): ?>
        <p style="grid-column: 1/-1; text-align: center; color: var(--muted); padding: 3rem;">
          Không tìm thấy sản phẩm nào phù hợp với tiêu chí tìm kiếm.
        </p>
      <?php else: ?>
        <?php foreach ($products as $product): ?>
          <div class="product-card" data-id="<?php echo $product['id']; ?>">
            <?php if (!empty($product['badge'])): ?>
              <span class="product-badge"><?php echo htmlspecialchars($product['badge']); ?></span>
            <?php endif; ?>
            <div class="product-image">
              <img 
                src="<?php echo formatImagePath($product['image']); ?>" 
                alt="<?php echo htmlspecialchars($product['name']); ?>"
                onerror="this.src='/frontend/images/placeholder.jpg'"
              />
            </div>
            <div class="product-info">
              <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
              <p class="product-brand"><?php echo htmlspecialchars($product['brand']); ?></p>
              <p class="product-desc"><?php echo htmlspecialchars($product['notes']); ?></p>
              <div class="product-footer">
                <span class="product-price"><?php echo formatPrice($product['price']); ?> ₫</span>
                <button 
                  class="btn-add-cart" 
                  onclick="addToCart(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>', <?php echo $product['price']; ?>)"
                  title="Thêm vào giỏ hàng"
                >
                  <i class="fa-solid fa-bag-shopping"></i>
                </button>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <p class="page-info" id="page-info">
      Trang <?php echo $page; ?> / <?php echo $totalPages; ?>
    </p>

    <!-- PAGINATION -->
    <?php if ($totalPages > 1): ?>
      <div class="pagination" id="pagination">
        <?php if ($page > 1): ?>
          <a href="?<?php echo htmlspecialchars(buildFilterQuery(array_merge($baseFilterParams, ['page' => $page - 1]))); ?>" class="pagination-btn">
            ← Trang trước
          </a>
        <?php endif; ?>

        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
          <?php if ($p == $page): ?>
            <span class="pagination-btn active"><?php echo $p; ?></span>
          <?php else: ?>
            <a 
              href="?<?php echo htmlspecialchars(buildFilterQuery(array_merge($baseFilterParams, ['page' => $p]))); ?>" 
              class="pagination-btn"
            >
              <?php echo $p; ?>
            </a>
          <?php endif; ?>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
          <a href="?<?php echo htmlspecialchars(buildFilterQuery(array_merge($baseFilterParams, ['page' => $page + 1]))); ?>" class="pagination-btn">
            Trang sau →
          </a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
