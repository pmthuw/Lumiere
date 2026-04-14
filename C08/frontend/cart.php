<?php
declare(strict_types=1);

require_once __DIR__ . '/../admin/db.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
  throw new RuntimeException('Database connection is not available.');
}

session_start();
$userEmail = $_SESSION['user_email'] ?? ($_GET['user'] ?? null);

// Ensure cart table exists
try {
  $pdo->exec(<<<SQL
    CREATE TABLE IF NOT EXISTS shopping_cart (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_email VARCHAR(255) NOT NULL,
      product_id INT UNSIGNED NOT NULL,
      quantity INT NOT NULL DEFAULT 1,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      UNIQUE KEY unique_user_product (user_email, product_id),
      FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )
  SQL);
} catch (Throwable $e) {
  // Table may already exist
}

function e(string $value): string {
  return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function formatPrice($price): string {
  return number_format($price, 0, '.', ',');
}

function formatImagePath($image): string {
  $image = trim((string)$image);
  if (empty($image)) return '/frontend/images/default.jpg';
  if (str_starts_with($image, 'http') || str_starts_with($image, '/')) return $image;
  if (str_starts_with($image, 'frontend/')) return '/' . $image;
  if (str_starts_with($image, 'images/')) return '/frontend/' . $image;
  return '/frontend/images/' . $image;
}

$cartItems = [];
$total = 0;

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


if ($userEmail) {
  try {
    $stmt = $pdo->prepare(<<<SQL
      SELECT 
        sc.id,
        sc.product_id,
        sc.quantity,
        p.name,
        ROUND({$avgCostExpr} * (1 + (COALESCE(p.profit_rate, 0) / 100))) AS price,
        p.image,
        p.brand,
        p.notes,
        (sc.quantity * ROUND({$avgCostExpr} * (1 + (COALESCE(p.profit_rate, 0) / 100)))) as subtotal
      FROM shopping_cart sc
      JOIN products p ON sc.product_id = p.id
      {$priceJoinSql}
      WHERE sc.user_email = ?
      ORDER BY sc.created_at DESC
    SQL);
    $stmt->execute([$userEmail]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total = array_reduce($cartItems, fn($sum, $item) => $sum + $item['subtotal'], 0);
  } catch (Throwable $e) {
    $cartItems = [];
    $total = 0;
  }
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
  <link rel="stylesheet" href="styles/style.css">
  <title>Giỏ hàng | LUMIERE</title>
  <style>
    .cart-page { padding: 2rem 0 0; }
    .cart-container { display: grid; grid-template-columns: 1fr 380px; gap: 2rem; align-items: start; }
    .cart-items-list { display: flex; flex-direction: column; gap: 1rem; }
    .cart-item { display: grid; grid-template-columns: 100px 1fr auto; gap: 1.5rem; align-items: center; padding: 1.5rem; background: var(--surface); border: 1px solid var(--border); border-radius: 1rem; }
    .cart-item-img { width: 100px; height: 100px; border-radius: 0.75rem; overflow: hidden; background: rgba(255,255,255,0.03); display: flex; align-items: center; justify-content: center; }
    .cart-item-img img { width: 100%; height: 100%; object-fit: contain; }
    .cart-item-info { display: flex; flex-direction: column; gap: 0.5rem; }
    .cart-item-name { font-weight: 500; color: var(--text); }
    .cart-item-brand { font-size: 0.85rem; color: var(--muted); }
    .cart-item-price { font-family: "Playfair Display", serif; font-size: 1.1rem; color: var(--gold); }
    .cart-item-controls { display: flex; gap: 0.5rem; align-items: center; }
    .qty-btn { width: 32px; height: 32px; border: 1px solid var(--border); background: transparent; color: var(--text); cursor: pointer; border-radius: 0.4rem; transition: all 0.2s; }
    .qty-btn:hover { border-color: var(--gold); color: var(--gold); }
    .cart-item-remove { width: 100%; text-align: right; }
    .cart-remove-btn { background: none; border: none; color: var(--muted); cursor: pointer; font-size: 1.2rem; transition: color 0.2s; }
    .cart-remove-btn:hover { color: #ff6b6b; }
    .cart-summary { background: var(--surface); border: 1px solid var(--border); border-radius: 1rem; padding: 2rem; }
    .summary-row { display: flex; justify-content: space-between; margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px dashed var(--border); }
    .summary-row.total { border-bottom: none; font-weight: 600; font-size: 1.1rem; color: var(--gold); }
    .summary-label { color: var(--muted); }
    .summary-value { text-align: right; }
    .cart-empty { text-align: center; padding: 4rem 2rem; color: var(--muted); }
    .cart-empty .icon { font-size: 4rem; margin-bottom: 1rem; opacity: 0.3; }
    .checkout-btn { width: 100%; padding: 1rem; background: var(--gold); color: #000; border: none; border-radius: 0.75rem; font-weight: 600; cursor: pointer; margin-top: 1.5rem; transition: all 0.2s; }
    .checkout-btn:hover { background: #d9bc82; transform: translateY(-2px); }
    .continue-btn { width: 100%; padding: 0.8rem; background: transparent; border: 1px solid var(--border); color: var(--text); border-radius: 0.75rem; cursor: pointer; margin-top: 0.5rem; transition: all 0.2s; }
    .continue-btn:hover { border-color: var(--gold); color: var(--gold); }
    @media (max-width: 960px) {
      .cart-container { grid-template-columns: 1fr; }
      .cart-item { grid-template-columns: 80px 1fr; gap: 1rem; }
      .cart-item-img { width: 80px; height: 80px; }
    }
  </style>
</head>
<body>
  <?php include __DIR__ . '/components/header.php'; ?>

  <main class="cart-page">
    <section class="section-container">
      <div class="section-header" style="margin-bottom: 2rem;">
        <div class="section-title-block">
          <span class="eyebrow">Shopping Cart</span>
          <h2 class="section-title">Giỏ hàng</h2>
        </div>
        <a href="index.php#products" class="btn btn-ghost" style="text-decoration: none;">
          ← Tiếp tục mua sắm
        </a>
      </div>

      <?php if (empty($cartItems)): ?>
        <div class="cart-empty">
          <div class="icon">🛒</div>
          <h3 style="margin-bottom: 0.5rem;">Giỏ hàng trống</h3>
          <p style="margin-bottom: 1.5rem;">Bạn chưa thêm sản phẩm nào vào giỏ hàng.</p>
          <a href="index.php#products" class="btn btn-gold" style="text-decoration: none; display: inline-block;">
            Xem sản phẩm
          </a>
        </div>
      <?php else: ?>
        <div class="cart-container">
          <div class="cart-items-list">
            <?php foreach ($cartItems as $item): ?>
              <div class="cart-item" data-product-id="<?php echo $item['product_id']; ?>">
                <div class="cart-item-img">
                  <img 
                    src="<?php echo formatImagePath($item['image']); ?>" 
                    alt="<?php echo e($item['name']); ?>"
                    onerror="this.src='/frontend/images/placeholder.jpg'"
                  >
                </div>
                <div class="cart-item-info">
                  <p class="cart-item-name"><?php echo e((string)$item['name']); ?></p>
                  <p class="cart-item-brand"><?php echo e((string)$item['brand']); ?></p>
                  <p class="cart-item-price"><?php echo formatPrice((int)$item['price']); ?> ₫</p>
                </div>
                <div style="display: flex; flex-direction: column; gap: 1rem; align-items: flex-end;">
                  <div class="cart-item-controls">
                    <button class="qty-btn" onclick="updateQty(<?php echo $item['product_id']; ?>, -1)">−</button>
                    <span style="width: 40px; text-align: center;"><?php echo $item['quantity']; ?></span>
                    <button class="qty-btn" onclick="updateQty(<?php echo $item['product_id']; ?>, 1)">+</button>
                  </div>
                  <button class="cart-remove-btn" onclick="removeItem(<?php echo $item['product_id']; ?>)" title="Xóa">🗑</button>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <div class="cart-summary">
            <div class="summary-row">
              <span class="summary-label">Số lượng:</span>
              <span class="summary-value"><?php echo count($cartItems); ?> sản phẩm</span>
            </div>
            <div class="summary-row">
              <span class="summary-label">Tạm tính:</span>
              <span class="summary-value"><?php echo formatPrice((int)$total); ?> ₫</span>
            </div>
            <div class="summary-row">
              <span class="summary-label">Vận chuyển:</span>
              <span class="summary-value">Miễn phí</span>
            </div>
            <div class="summary-row total">
              <span>Tổng cộng:</span>
              <span><?php echo formatPrice((int)$total); ?> ₫</span>
            </div>
            <button class="checkout-btn" type="button" onclick="window.location.href='checkout.php<?php echo $userEmail ? '?user=' . urlencode((string)$userEmail) : ''; ?>'">Thanh toán</button>
            <a href="index.php#products" class="continue-btn" style="text-decoration: none; display: block; text-align: center;">Tiếp tục mua sắm</a>
          </div>
        </div>
      <?php endif; ?>
    </section>
  </main>

  <?php include __DIR__ . '/components/footer.php'; ?>

  <script>
    let userEmail = '<?php echo addslashes($userEmail ?? ''); ?>';

    function updateQty(productId, delta) {
      const currentQtySpan = document.querySelector(`[data-product-id="${productId}"] .cart-item-controls span`);
      const currentQty = parseInt(currentQtySpan.textContent);
      const newQty = Math.max(1, currentQty + delta);
      
      fetch(`../backend/api/cart.php?action=update&product_id=${productId}&quantity=${newQty}&user=${userEmail}`, {
        method: 'POST'
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          currentQtySpan.textContent = newQty;
          location.reload();
        } else {
          alert('Lỗi: ' + (data.error || 'Không xác định'));
        }
      })
      .catch(err => console.error('Error:', err));
    }

    function removeItem(productId) {
      if (!confirm('Xác nhận xóa sản phẩm này?')) return;
      
      fetch(`../backend/api/cart.php?action=remove&product_id=${productId}&user=${userEmail}`, {
        method: 'POST'
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          location.reload();
        } else {
          alert('Lỗi: ' + (data.error || 'Không xác định'));
        }
      })
      .catch(err => console.error('Error:', err));
    }
  </script>
</body>
</html>
