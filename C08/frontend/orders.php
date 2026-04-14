<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../admin/db.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    throw new RuntimeException('Database connection is not available.');
}

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function fmtPrice(int|float $amount): string {
    return number_format((float)$amount, 0, '.', ',') . ' ₫';
}

function mapStatusLabel(string $status): string {
    $key = strtolower(trim($status));
    if ($key === 'pending' || $key === 'processing') return 'Đang xử lý';
    if ($key === 'shipping' || $key === 'shipped') return 'Đang giao';
    if ($key === 'delivered' || $key === 'completed') return 'Đã giao';
    return $status !== '' ? $status : 'Đang xử lý';
}

function mapStatusClass(string $statusLabel): string {
    if ($statusLabel === 'Đang xử lý') return 'badge-processing';
    if ($statusLabel === 'Đang giao') return 'badge-shipping';
    if ($statusLabel === 'Đã giao') return 'badge-delivered';
    return 'badge-processing';
}

$userFromSession = $_SESSION['customer_user']['email'] ?? $_SESSION['user_email'] ?? '';
$userFromQuery = trim((string)($_GET['user'] ?? ''));
$userEmail = trim((string)($userFromSession ?: $userFromQuery));

$orders = [];
$loadError = '';

if ($userEmail !== '') {
    try {
        $orderStmt = $pdo->prepare(
            'SELECT o.id, o.order_number, o.customer_email, o.customer_name, o.customer_phone, o.shipping_address, o.ward, o.district, o.city, o.total_amount, o.status, o.payment_method, o.notes, o.created_at
             FROM orders o
             LEFT JOIN users u ON u.id = o.user_id
             WHERE o.customer_email = :email OR u.email = :email
             ORDER BY o.created_at DESC, o.id DESC'
        );
        $orderStmt->execute(['email' => $userEmail]);
        $rawOrders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);

        if ($rawOrders) {
            $itemStmt = $pdo->prepare(
                'SELECT product_id, product_name, quantity, unit_price, total_price
                 FROM order_items
                 WHERE order_id = ?
                 ORDER BY id ASC'
            );

            foreach ($rawOrders as $order) {
                $orderId = (int)$order['id'];
                $itemStmt->execute([$orderId]);
                $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

                $statusLabel = mapStatusLabel((string)($order['status'] ?? 'pending'));

                $orders[] = [
                    'id' => $orderId,
                    'order_number' => (string)($order['order_number'] ?? $orderId),
                    'customer_name' => (string)($order['customer_name'] ?? ''),
                    'customer_phone' => (string)($order['customer_phone'] ?? ''),
                    'customer_email' => (string)($order['customer_email'] ?? ''),
                    'shipping_address' => (string)($order['shipping_address'] ?? ''),
                    'ward' => (string)($order['ward'] ?? ''),
                    'district' => (string)($order['district'] ?? ''),
                    'city' => (string)($order['city'] ?? ''),
                    'status_label' => $statusLabel,
                    'status_class' => mapStatusClass($statusLabel),
                    'payment_method' => (string)($order['payment_method'] ?? ''),
                    'notes' => (string)($order['notes'] ?? ''),
                    'created_at' => (string)($order['created_at'] ?? ''),
                    'total_amount' => (int)($order['total_amount'] ?? 0),
                    'items' => array_map(
                        static fn(array $item): array => [
                            'name' => (string)($item['product_name'] ?? 'Sản phẩm'),
                            'qty' => max(1, (int)($item['quantity'] ?? 1)),
                            'price' => (int)($item['unit_price'] ?? 0),
                            'total' => (int)($item['total_price'] ?? 0),
                        ],
                        $items
                    ),
                ];
            }
        }
    } catch (Throwable $e) {
        $loadError = 'Không thể tải đơn hàng. Vui lòng thử lại sau.';
    }
}
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>LUMIERE | Đơn hàng của tôi</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link
      href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,700;1,500&family=Cormorant+Garamond:wght@300;400;600&family=Jost:wght@300;400;500&display=swap"
      rel="stylesheet"
    />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    />
    <link rel="stylesheet" href="styles/style.css" />
    <style>
      .orders-page {
        max-width: 1120px;
        margin: 0 auto;
        padding: 2rem 1.5rem 4rem;
      }
      .orders-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.5rem;
      }
      .orders-sub {
        color: var(--muted);
        font-size: 0.9rem;
      }
      .orders-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
      }
      .order-shell {
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid var(--border);
        border-radius: 1rem;
        overflow: hidden;
      }
      .order-summary {
        padding: 1rem 1.25rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        cursor: pointer;
      }
      .order-meta {
        color: var(--muted);
        font-size: 0.8rem;
      }
      .order-body {
        border-top: 1px solid var(--border);
        padding: 1rem 1.25rem;
      }
      .order-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin-bottom: 1rem;
      }
      .meta-card {
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid var(--border);
        border-radius: 0.85rem;
        padding: 0.85rem;
      }
      .meta-title {
        color: var(--muted);
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        margin-bottom: 0.4rem;
      }
      .items-wrap {
        border: 1px solid var(--border);
        border-radius: 0.85rem;
        overflow: hidden;
      }
      .item-row {
        display: grid;
        grid-template-columns: 1fr auto auto;
        gap: 0.75rem;
        padding: 0.75rem 0.9rem;
        border-bottom: 1px solid var(--border);
        font-size: 0.9rem;
      }
      .item-row:last-child {
        border-bottom: none;
      }
      .item-name {
        color: var(--text);
      }
      .item-qty {
        color: var(--muted);
      }
      .item-total {
        color: var(--gold);
        font-weight: 600;
      }
      .order-total {
        margin-top: 0.9rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-family: "Playfair Display", serif;
        color: var(--gold);
        font-size: 1.1rem;
      }
      .empty-card {
        text-align: center;
        padding: 2.2rem;
        border: 1px solid var(--border);
        border-radius: 1rem;
        background: rgba(255, 255, 255, 0.02);
      }
      @media (max-width: 760px) {
        .order-grid {
          grid-template-columns: 1fr;
        }
      }
    </style>
  </head>
  <body>
    <?php include __DIR__ . '/components/header.php'; ?>

    <main class="orders-page">
      <div class="orders-head">
        <div>
          <span class="eyebrow">Tài khoản</span>
          <h1 class="section-title" style="margin: 0.35rem 0 0.2rem">Đơn hàng của tôi</h1>
          <div class="orders-sub">
            <?php echo $userEmail !== '' ? 'Đang hiển thị đơn của: ' . e($userEmail) : 'Bạn cần đăng nhập để xem đơn hàng.'; ?>
          </div>
        </div>
      </div>

      <?php if ($userEmail === ''): ?>
        <div class="empty-card">
          <div style="font-size: 2.3rem; margin-bottom: 0.8rem">🔐</div>
          <p style="color: var(--muted); margin-bottom: 1rem">Vui lòng đăng nhập để xem đơn hàng của bạn.</p>
          <a href="login.php?redirect=<?php echo urlencode('orders.php'); ?>" class="btn btn-gold">Đăng nhập</a>
        </div>
      <?php elseif ($loadError !== ''): ?>
        <div class="empty-card">
          <p style="color: #ffb3b3"><?php echo e($loadError); ?></p>
        </div>
      <?php elseif (empty($orders)): ?>
        <div class="empty-card">
          <div style="font-size: 2.5rem; margin-bottom: 0.8rem">📦</div>
          <p style="color: var(--muted)">Bạn chưa có đơn hàng nào.</p>
        </div>
      <?php else: ?>
        <div class="orders-list">
          <?php foreach ($orders as $order): ?>
            <details class="order-shell">
              <summary class="order-summary">
                <div>
                  <div class="oc-id">#<?php echo e($order['order_number']); ?></div>
                  <div class="order-meta"><?php echo e(date('d/m/Y H:i', strtotime($order['created_at']))); ?></div>
                </div>
                <div class="oc-right">
                  <div class="oc-total"><?php echo e(fmtPrice($order['total_amount'])); ?></div>
                  <span class="status-badge <?php echo e($order['status_class']); ?>"><?php echo e($order['status_label']); ?></span>
                </div>
              </summary>
              <div class="order-body">
                <div class="order-grid">
                  <div class="meta-card">
                    <div class="meta-title">Người nhận</div>
                    <div><?php echo e($order['customer_name'] !== '' ? $order['customer_name'] : 'Không có thông tin'); ?></div>
                    <div class="order-meta"><?php echo e($order['customer_phone'] !== '' ? $order['customer_phone'] : '-'); ?></div>
                    <div class="order-meta"><?php echo e($order['customer_email']); ?></div>
                  </div>
                  <div class="meta-card">
                    <div class="meta-title">Giao hàng và thanh toán</div>
                    <div><?php echo e(trim($order['shipping_address'] . ' ' . $order['ward'] . ' ' . $order['district'] . ' ' . $order['city'])); ?></div>
                    <div class="order-meta" style="margin-top: 0.25rem">Phương thức: <?php echo e($order['payment_method'] !== '' ? $order['payment_method'] : 'Không xác định'); ?></div>
                    <?php if ($order['notes'] !== ''): ?>
                      <div class="order-meta">Ghi chú: <?php echo e($order['notes']); ?></div>
                    <?php endif; ?>
                  </div>
                </div>

                <div class="items-wrap">
                  <?php foreach ($order['items'] as $item): ?>
                    <div class="item-row">
                      <div class="item-name"><?php echo e($item['name']); ?></div>
                      <div class="item-qty">x<?php echo (int)$item['qty']; ?></div>
                      <div class="item-total"><?php echo e(fmtPrice($item['total'])); ?></div>
                    </div>
                  <?php endforeach; ?>
                </div>

                <div class="order-total">
                  <span>Tổng thanh toán</span>
                  <span><?php echo e(fmtPrice($order['total_amount'])); ?></span>
                </div>
              </div>
            </details>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </main>

    <?php include __DIR__ . '/components/footer.php'; ?>

    <?php include __DIR__ . '/components/modals.php'; ?>

    <div class="toast" id="toast"></div>

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
