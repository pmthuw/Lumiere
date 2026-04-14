<?php
declare(strict_types=1);

require_once __DIR__ . '/../admin/db.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
  throw new RuntimeException('Database connection is not available.');
}
/** @var PDO $pdo */

function e(string $value): string
{
  return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function formatImageUrl(?string $image): string
{
  $image = trim((string)$image);
  if ($image === '') {
    return 'images/default.jpg';
  }

  if (preg_match('/^(https?:)?\\/\\//i', $image) || str_starts_with($image, 'data:')) {
    return $image;
  }

  if (str_starts_with($image, '/')) {
    return $image;
  }

  if (str_starts_with($image, 'frontend/images/')) {
    return str_replace('frontend/', '', $image);
  }

  if (str_starts_with($image, 'images/')) {
    return $image;
  }

  return ltrim($image, '/');
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  header('Location: index.php#products');
  exit;
}

$hasStatusColumn = false;
try {
  $statusCheckStmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'status'");
  $hasStatusColumn = (bool)$statusCheckStmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $hasStatusColumn = false;
}

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


$sql = "SELECT p.id, p.name, p.category, {$avgCostExpr} AS computed_avg_import_price, p.profit_rate, p.notes, p.concentration, p.size, p.brand, p.badge, p.image FROM products p {$priceJoinSql} WHERE p.id = :id";
if ($hasStatusColumn) {
  $sql .= " AND p.status = 'active'";
}
$sql .= ' LIMIT 1';

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
  http_response_code(404);
}

$name          = $product ? (string)($product['name']          ?? 'Sản phẩm không tồn tại') : 'Sản phẩm không tồn tại';
$brand         = $product ? (string)($product['brand']         ?? 'N/A') : 'N/A';
$category      = $product ? (string)($product['category']      ?? 'N/A') : 'N/A';
$notes         = $product ? (string)($product['notes']         ?? '') : '';
$concentration = $product ? (string)($product['concentration'] ?? 'Đang cập nhật') : 'Đang cập nhật';
$size          = $product ? (string)($product['size']          ?? 'Đang cập nhật') : 'Đang cập nhật';
$badge         = $product ? trim((string)($product['badge']    ?? '')) : '';
$image         = $product ? formatImageUrl($product['image']   ?? null) : 'images/default.jpg';
$price         = $product ? (int)round((float)($product['computed_avg_import_price'] ?? 0) * (1 + ((float)($product['profit_rate'] ?? 0) / 100))) : 0;
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo e($name); ?> | LUMIERE</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,700;1,500&family=Cormorant+Garamond:wght@300;400;600&family=Jost:wght@300;400;500&display=swap"
    rel="stylesheet"
  />
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
  />
  <link rel="stylesheet" href="../frontend/styles/style.css" />
  <style>
    .detail-grid {
      align-items: start;
      grid-template-columns: fit-content(100%) 1fr;
    }
    .detail-img {
      width: fit-content;
      height: fit-content;
      padding: 0;
      overflow: hidden;
      justify-self: start;
      align-self: start;
    }
    .detail-img img {
      display: block;
      width: auto;
      height: auto;
      max-width: min(100%, 520px);
      max-height: 520px;
      object-fit: contain;
      object-position: center;
      padding: 0;
      box-sizing: border-box;
      border-radius: inherit;
    }
    @media (max-width: 960px) {
      .detail-grid {
        grid-template-columns: 1fr;
      }
      .detail-img {
        width: 100%;
      }
      .detail-img img {
        width: 100%;
        max-width: 100%;
        max-height: 420px;
      }
    }
  </style>
</head>
<body>

  <?php include __DIR__ . '/components/header.php'; ?>

  <main>
    <section class="section-container">

      <!-- BREADCRUMB -->
      <p style="color:var(--muted);font-size:0.85rem;margin-bottom:1.5rem;">
        <a href="index.php" style="transition:color .2s;" onmouseover="this.style.color='var(--gold)'" onmouseout="this.style.color=''">Trang chủ</a>
        &nbsp;/&nbsp;
        <a href="index.php#products" style="transition:color .2s;" onmouseover="this.style.color='var(--gold)'" onmouseout="this.style.color=''">Sản phẩm</a>
        &nbsp;/&nbsp;
        <span><?php echo e($name); ?></span>
      </p>

      <!-- SECTION HEADER (same pattern as products-section.php) -->
      <div class="section-header">
        <div class="section-title-block">
          <span class="eyebrow">Product Detail</span>
          <h2 class="section-title">Chi tiết sản phẩm</h2>
        </div>
        <a href="index.php#products" class="btn btn-ghost" style="text-decoration:none;">
          ← Quay lại danh sách
        </a>
      </div>

      <?php if (!$product): ?>
        <!-- NOT FOUND STATE -->
        <div class="no-results">
          <div class="icon">🔎</div>
          <h2 style="margin-bottom:0.8rem;">Không tìm thấy sản phẩm</h2>
          <p style="margin-bottom:1.5rem;">Sản phẩm bạn đang tìm có thể đã được ẩn hoặc không tồn tại.</p>
          <a class="btn btn-gold" href="index.php#products">Xem tất cả sản phẩm</a>
        </div>

      <?php else: ?>
        <!-- PRODUCT DETAIL GRID -->
        <div class="detail-grid">

          <!-- LEFT: IMAGE -->
          <div class="detail-img">
            <?php if ($badge !== ''): ?>
              <span class="product-badge"><?php echo e($badge); ?></span>
            <?php endif; ?>
            <img
              src="<?php echo e($image); ?>"
              alt="<?php echo e($name); ?>"
              onerror="this.src='images/default.jpg'"
            />
          </div>

          <!-- RIGHT: INFO -->
          <div>
            <!-- Category label -->
            <p class="detail-category"><?php echo e($category); ?> · <?php echo e($brand); ?></p>

            <!-- Name -->
            <h1 class="detail-name"><?php echo e($name); ?></h1>

            <!-- Price -->
            <p class="detail-price"><?php echo e(number_format($price, 0, ',', '.')); ?> ₫</p>

            <!-- Description -->
            <?php if ($notes !== ''): ?>
              <p class="detail-desc"><?php echo e($notes); ?></p>
            <?php endif; ?>

            <!-- Meta grid (reuses .detail-meta / .detail-meta-item from CSS) -->
            <div class="detail-meta">
              <div class="detail-meta-item">
                <p class="label">Nhóm hương</p>
                <p class="val"><?php echo e($notes !== '' ? $notes : 'Đang cập nhật'); ?></p>
              </div>
              <div class="detail-meta-item">
                <p class="label">Nồng độ</p>
                <p class="val"><?php echo e($concentration); ?></p>
              </div>
              <div class="detail-meta-item">
                <p class="label">Dung tích</p>
                <p class="val"><?php echo e($size); ?></p>
              </div>
              <div class="detail-meta-item">
                <p class="label">Danh mục</p>
                <p class="val"><?php echo e($category); ?></p>
              </div>
            </div>

            <!-- CTA buttons -->
            <div class="detail-actions">
              <button
                class="btn btn-gold"
                onclick="addToCart(<?php echo (int)$product['id']; ?>, '<?php echo e($name); ?>', <?php echo $price; ?>)"
              >
                <i class="fa-solid fa-bag-shopping"></i>&nbsp; Thêm vào giỏ
              </button>
              <a class="btn btn-ghost" href="checkout.php?product_id=<?php echo (int)$product['id']; ?>" style="text-decoration:none;">
                Mua ngay
              </a>
            </div>
          </div>

        </div><!-- /.detail-grid -->
      <?php endif; ?>

    </section>
  </main>

  <?php include __DIR__ . '/components/footer.php'; ?>
  <?php include __DIR__ . '/components/modals.php'; ?>

  <div class="toast" id="toast"></div>

  <!-- JS Modules — same order as index.php -->
  <script src="js/init.js"></script>
  <script src="js/utils.js"></script>
  <script src="js/products.js"></script>
  <script src="js/search.js"></script>
  <script src="js/cart.js"></script>
  <script src="js/auth.js?v=20260413-4"></script>
  <script src="js/orders.js"></script>
  <script src="js/profile.js"></script>

</body>
</html>