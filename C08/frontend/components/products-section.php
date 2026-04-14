<?php
// Get parameters from URL
$category = trim((string)($_GET['category'] ?? ''));
$search = trim((string)($_GET['search'] ?? ''));
$brand = trim((string)($_GET['brand'] ?? ''));
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
if ($category !== '' && !in_array($category, $activeCategories, true)) {
  $category = '';
}

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
$hasReceiptPricingTables = false;
try {
  $hasReceiptsTable = (bool)$pdo->query("SHOW TABLES LIKE 'receipts'")->fetchColumn();
  $hasReceiptItemsTable = (bool)$pdo->query("SHOW TABLES LIKE 'receipt_items'")->fetchColumn();
  $hasReceiptPricingTables = $hasReceiptsTable && $hasReceiptItemsTable;
} catch (Throwable $e) {
  $hasReceiptPricingTables = false;
}

$priceJoinSql = '';
$avgCostExpr = 'COALESCE(p.avg_import_price, 0)';


$sql = "SELECT p.*, {$avgCostExpr} AS computed_avg_import_price FROM products p {$priceJoinSql} WHERE 1=1";
$params = [];
$displayPriceExpr = "ROUND({$avgCostExpr} * (1 + (COALESCE(p.profit_rate, 0) / 100)))";

if ($hasStatusColumn) {
  $sql .= " AND p.status = 'active'";
}

if (!empty($category)) {
    $sql .= " AND p.category = ?";
    $params[] = $category;
}

if (!empty($search)) {
    $sql .= " AND (p.name LIKE ? OR p.brand LIKE ? OR p.notes LIKE ?)";
    $searchTerm = '%' . $search . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (!empty($brand)) {
  $sql .= " AND p.brand LIKE ?";
  $params[] = '%' . $brand . '%';
}

if ($priceMin !== null || $priceMax !== null) {
  $minValue = $priceMin ?? 0;
  $maxValue = $priceMax ?? 999999999;
    $sql .= " AND {$displayPriceExpr} BETWEEN ? AND ?";
  $params[] = $minValue;
  $params[] = $maxValue;
}

// Count total
$countSql = preg_replace('/^SELECT\s.+?\sFROM\s/si', 'SELECT COUNT(*) as total FROM ', $sql);
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalProducts = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalProducts / $perPage);

// Fetch products
$sql .= " ORDER BY p.id LIMIT ?, ?";
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare($sql);
foreach ($params as $idx => $value) {
  $stmt->bindValue($idx + 1, $value);
}
$stmt->bindValue(count($params) + 1, $offset, PDO::PARAM_INT);
$stmt->bindValue(count($params) + 2, $perPage, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($products as &$product) {
  $product['display_price'] = (int)round((float)($product['computed_avg_import_price'] ?? 0) * (1 + ((float)($product['profit_rate'] ?? 0) / 100)));
}
unset($product);

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

function e(string $value): string {
  return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function buildFilterUrl(array $params): string {
  $query = buildFilterQuery($params);
  if ($query === '') {
    return '?page=1#products';
  }
  return '?' . $query . '#products';
}

$baseFilterParams = [
  'search' => $search,
  'brand' => $brand,
  'category' => $category,
  'priceMin' => $priceMin,
  'priceMax' => $priceMax,
];
?>

<!-- ══════════ PRODUCTS SECTION ══════════ -->
<section id="products">
  <div class="section-container">
    <!-- SEARCH FORM -->
    <form id="products-search-form" method="GET" action="index.php#products" class="search-box" style="margin-bottom: 2rem">
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
            value="<?php echo e($search); ?>"
          />
        </div>
        <button class="btn btn-gold" type="submit">Tìm kiếm</button>
      </div>

      <div class="advanced-panel open" id="advanced-panel">
        <div class="filter-field filter-field-short">
          <span class="filter-label">Loại</span>
          <select id="filter-category" class="filter-select" name="category" onchange="this.form.submit()">
            <option value="">Tất cả phân loại</option>
            <?php foreach ($activeCategories as $catName): ?>
              <option value="<?php echo e((string)$catName); ?>" <?php echo $category === $catName ? 'selected' : ''; ?>>
                <?php echo e((string)$catName); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="filter-field filter-field-short">
          <span class="filter-label">Nhãn</span>
          <input
            id="filter-brand"
            class="filter-input"
            type="text"
            name="brand"
            placeholder="Ví dụ: Chanel, Dior..."
            value="<?php echo e($brand); ?>"
          />
        </div>

        <div class="filter-field filter-field-wide">
          <span class="filter-label">Khoảng giá (VNĐ)</span>
          <div class="price-range">
            <input
              id="price-min"
              class="filter-input"
              type="number"
              name="priceMin"
              placeholder="Từ"
              min="0"
              value="<?php echo $priceMin !== null ? $priceMin : ''; ?>"
            />
            <input
              id="price-max"
              class="filter-input"
              type="number"
              name="priceMax"
              placeholder="Đến"
              min="0"
              value="<?php echo $priceMax !== null ? $priceMax : ''; ?>"
            />
          </div>
        </div>

        <div class="filter-actions">
          <button class="btn btn-gold" type="submit" style="flex: 1;">Tìm kiếm nâng cao</button>
          <a href="?page=1#products" class="btn btn-ghost" style="text-decoration: none; text-align: center; flex: 1;">Xóa</a>
        </div>
      </div>
    </form>

    <!-- SECTION HEADER -->
    <div class="section-header">
      <div class="section-title-block">
        <span class="eyebrow">Sản phẩm nổi bật</span>
        <h2 class="section-title">Sản phẩm</h2>
      </div>
      <span style="color: var(--muted); font-size: 0.85rem;" id="result-count">
        Hiển thị <?php echo count($products); ?> / <?php echo $totalProducts; ?> sản phẩm
      </span>
    </div>

    <!-- CATEGORY TABS -->
    <div class="category-tabs" id="category-tabs">
      <a href="<?php echo e(buildFilterUrl(array_merge($baseFilterParams, ['category' => '', 'page' => 1]))); ?>" class="tab-btn <?php echo empty($category) ? 'active' : ''; ?>" onclick="return true;">
        Tất cả
      </a>
      <?php foreach ($activeCategories as $catName): ?>
        <a href="<?php echo e(buildFilterUrl(array_merge($baseFilterParams, ['category' => (string)$catName, 'page' => 1]))); ?>" class="tab-btn <?php echo $category === $catName ? 'active' : ''; ?>">
          <?php echo e((string)$catName); ?>
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
           <div class="product-card" data-id="<?php echo $product['id']; ?>" onclick="window.location.href='./product-detail.php?id=<?php echo $product['id']; ?>';" style="cursor: pointer;">
            <div class="product-card-img">
              <?php if (!empty($product['badge'])): ?>
                <span class="product-badge"><?php echo e((string)$product['badge']); ?></span>
              <?php endif; ?>
              <img 
                src="<?php echo formatImagePath($product['image']); ?>" 
                alt="<?php echo e((string)$product['name']); ?>"
                style="width:100%;height:100%;object-fit:cover;"
                onerror="this.src='/frontend/images/placeholder.jpg'"
              />
            </div>
            <div class="product-card-body">
              <p class="product-category"><?php echo e((string)$product['brand']); ?></p>
              <h3><?php echo e((string)$product['name']); ?></h3>
              <p><?php echo e((string)$product['notes']); ?></p>
              <div class="product-footer">
                <span class="product-price"><?php echo formatPrice((int)$product['display_price']); ?> ₫</span>
                <div class="product-actions">
                  <button 
                    type="button"
                    class="add-cart-btn" 
                    data-product-id="<?php echo $product['id']; ?>"
                    data-product-name="<?php echo e((string)$product['name']); ?>"
                    data-product-price="<?php echo (int)$product['display_price']; ?>"
                    onclick="event.stopPropagation(); addToCart(Number(this.dataset.productId), this.dataset.productName, Number(this.dataset.productPrice));"
                    title="Thêm vào giỏ hàng"
                  >
                    <i class="fa-solid fa-bag-shopping"></i> Thêm
                  </button>
                </div>
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
          <a href="<?php echo e(buildFilterUrl(array_merge($baseFilterParams, ['page' => $page - 1]))); ?>" class="page-btn">
            &lt;
          </a>
        <?php endif; ?>

        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
          <?php if ($p == $page): ?>
            <span class="page-btn active"><?php echo $p; ?></span>
          <?php else: ?>
            <a 
              href="<?php echo e(buildFilterUrl(array_merge($baseFilterParams, ['page' => $p]))); ?>" 
              class="page-btn"
            >
              <?php echo $p; ?>
            </a>
          <?php endif; ?>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
          <a href="<?php echo e(buildFilterUrl(array_merge($baseFilterParams, ['page' => $page + 1]))); ?>" class="page-btn">
            &gt;
          </a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</section>