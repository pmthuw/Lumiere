<?php
require_once __DIR__ . '/../setup_db.php';

try {
    $hasWardColumn = (bool)$pdo->query("SHOW COLUMNS FROM orders LIKE 'ward'")->fetch(PDO::FETCH_ASSOC);
    if (!$hasWardColumn) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN ward VARCHAR(100) DEFAULT NULL AFTER shipping_address");
    }
} catch (Throwable $e) {
    // Keep page usable even if migration fails.
}

function mapOrderStatusToLabel(string $status): string
{
    $map = [
        'pending' => 'Chưa xử lý',
        'confirmed' => 'Đã xác nhận',
        // Legacy states are grouped into the nearest required workflow state.
        'processing' => 'Đã xác nhận',
        'shipped' => 'Đã xác nhận',
        'delivered' => 'Đã giao thành công',
        'cancelled' => 'Đã hủy'
    ];
    return $map[$status] ?? $status;
}

function mapLabelToOrderStatus(string $label): ?string
{
    $map = [
        'pending' => 'pending',
        'confirmed' => 'confirmed',
        'delivered' => 'delivered',
        'cancelled' => 'cancelled'
    ];
    return $map[$label] ?? null;
}

function formatOrderAddress(?string $shippingAddress, ?string $ward, ?string $district, ?string $city): string
{
    $shipping = trim((string)$shippingAddress);
    $parts = [];

    if ($shipping !== '') {
        $parts[] = $shipping;
    }

    foreach ([$ward, $district, $city] as $part) {
        $value = trim((string)$part);
        if ($value === '') {
            continue;
        }

        if ($shipping !== '' && stripos($shipping, $value) !== false) {
            continue;
        }

        $parts[] = $value;
    }

    return empty($parts) ? '-' : implode(' - ', $parts);
}

function redirectOrders(string $query = ''): void
{
    $url = 'index.php?page=orders';
    if ($query !== '') {
        $url .= '&' . ltrim($query, '&');
    }
    header('Location: ' . $url);
    exit;
}

$successMessage = '';
$errorMessage = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = $_POST['order_action'] ?? '';

    if ($action === 'update_status') {
        $orderId = (int)($_POST['order_id'] ?? 0);
        $newStatusKey = trim($_POST['new_status'] ?? '');
        $newStatus = mapLabelToOrderStatus($newStatusKey);

        if ($orderId <= 0 || $newStatus === null) {
            $errorMessage = 'Dữ liệu cập nhật trạng thái đơn không hợp lệ.';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$newStatus, $orderId]);
                redirectOrders($_POST['keep_query'] ?? 'updated=1');
            } catch (Throwable $e) {
                $errorMessage = 'Không thể cập nhật trạng thái đơn: ' . $e->getMessage();
            }
        }
    }
}

if (($_GET['updated'] ?? '') === '1') {
    $successMessage = 'Đã cập nhật trạng thái đơn hàng.';
}

$detailId = (int)($_GET['detail'] ?? 0);
$keyword = trim($_GET['q'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$wardFilter = trim($_GET['ward'] ?? '');
$fromDate = trim($_GET['from_date'] ?? '');
$toDate = trim($_GET['to_date'] ?? '');
$sortBy = trim($_GET['sort_by'] ?? 'date');

$whereParts = [];
$params = [];

if ($keyword !== '') {
    $whereParts[] = "(o.order_number LIKE ? OR o.customer_name LIKE ? OR o.customer_email LIKE ? OR oi.product_name LIKE ?)";
    $kw = '%' . $keyword . '%';
    $params[] = $kw;
    $params[] = $kw;
    $params[] = $kw;
    $params[] = $kw;
}

if ($statusFilter !== '') {
    $statusValue = mapLabelToOrderStatus($statusFilter);
    if ($statusValue !== null) {
        $whereParts[] = 'o.status = ?';
        $params[] = $statusValue;
    }
}

if ($wardFilter !== '') {
    $whereParts[] = '(COALESCE(o.ward, "") LIKE ? OR COALESCE(o.district, "") LIKE ? OR COALESCE(o.shipping_address, "") LIKE ?)';
    $kwWard = '%' . $wardFilter . '%';
    $params[] = $kwWard;
    $params[] = $kwWard;
    $params[] = $kwWard;
}

if ($fromDate !== '') {
    $whereParts[] = 'DATE(o.created_at) >= ?';
    $params[] = $fromDate;
}

if ($toDate !== '') {
    $whereParts[] = 'DATE(o.created_at) <= ?';
    $params[] = $toDate;
}

$whereSql = '';
if (!empty($whereParts)) {
    $whereSql = 'WHERE ' . implode(' AND ', $whereParts);
}

$orderBy = 'o.created_at DESC, o.id DESC';
if ($sortBy === 'ward') {
    $orderBy = 'COALESCE(o.ward, "") ASC, COALESCE(o.district, "") ASC, o.created_at DESC';
}

$listSql = "SELECT
    o.id,
    o.order_number,
    o.customer_name,
    o.customer_email,
    o.customer_phone,
    o.shipping_address,
    o.ward,
    o.district,
    o.city,
    o.total_amount,
    o.status,
    o.created_at,
    GROUP_CONCAT(DISTINCT oi.product_name ORDER BY oi.product_name SEPARATOR ', ') AS products_text
FROM orders o
LEFT JOIN order_items oi ON oi.order_id = o.id
{$whereSql}
GROUP BY o.id
ORDER BY {$orderBy}";

$listStmt = $pdo->prepare($listSql);
$listStmt->execute($params);
$orders = $listStmt->fetchAll(PDO::FETCH_ASSOC);

$orderDetail = null;
$orderItems = [];
if ($detailId > 0) {
    $detailStmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $detailStmt->execute([$detailId]);
    $orderDetail = $detailStmt->fetch(PDO::FETCH_ASSOC) ?: null;

    if ($orderDetail) {
        $itemStmt = $pdo->prepare("SELECT product_id, product_name, product_brand, quantity, unit_price, total_price FROM order_items WHERE order_id = ? ORDER BY id");
        $itemStmt->execute([$detailId]);
        $orderItems = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

$keepQuery = http_build_query([
    'q' => $keyword,
    'status' => $statusFilter,
    'ward' => $wardFilter,
    'from_date' => $fromDate,
    'to_date' => $toDate,
    'sort_by' => $sortBy
]);
?>

<section class="admin-page<?php echo ($page === 'orders') ? ' active' : ''; ?>" id="page-orders">
    <div class="page-header">
        <div class="page-header-left">
            <span class="eyebrow">✦ Giao dịch</span>
            <h1>Đơn đặt hàng</h1>
        </div>
    </div>

    <?php if ($successMessage !== ''): ?>
        <div style="background: rgba(0, 255, 0, 0.1); border: 1px solid green; color: green; padding: 1rem; margin-bottom: 1rem; border-radius: 0.5rem;">
            <?php echo htmlspecialchars($successMessage); ?>
        </div>
    <?php endif; ?>

    <?php if ($errorMessage !== ''): ?>
        <div style="background: rgba(255, 0, 0, 0.1); border: 1px solid red; color: red; padding: 1rem; margin-bottom: 1rem; border-radius: 0.5rem;">
            <?php echo htmlspecialchars($errorMessage); ?>
        </div>
    <?php endif; ?>

    <div class="table-wrap" style="margin-bottom: 1.5rem; padding: 1.25rem 1.5rem;">
        <form method="get" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:1rem; align-items:end;">
            <input type="hidden" name="page" value="orders">
            <div>
                <span class="filter-label">Tìm kiếm</span>
                <input class="search-inline" type="text" name="q" value="<?php echo htmlspecialchars($keyword); ?>" placeholder="Tên, mã, email, sản phẩm..." style="width:100%;">
            </div>
            <div>
                <span class="filter-label">Trạng thái</span>
                <select class="search-inline" name="status" style="width:100%;">
                    <option value="">Tất cả</option>
                    <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Chưa xử lý</option>
                    <option value="confirmed" <?php echo $statusFilter === 'confirmed' ? 'selected' : ''; ?>>Đã xác nhận</option>
                    <option value="delivered" <?php echo $statusFilter === 'delivered' ? 'selected' : ''; ?>>Đã giao thành công</option>
                    <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
                </select>
            </div>
            <div>
                <span class="filter-label">Phường / quận</span>
                <input class="search-inline" type="text" name="ward" value="<?php echo htmlspecialchars($wardFilter); ?>" placeholder="Lọc theo phường/quận..." style="width:100%;">
            </div>
            <div>
                <span class="filter-label">Từ ngày</span>
                <input class="search-inline" type="date" name="from_date" value="<?php echo htmlspecialchars($fromDate); ?>" style="width:100%;">
            </div>
            <div>
                <span class="filter-label">Đến ngày</span>
                <input class="search-inline" type="date" name="to_date" value="<?php echo htmlspecialchars($toDate); ?>" style="width:100%;">
            </div>
            <div>
                <span class="filter-label">Sắp xếp</span>
                <select class="search-inline" name="sort_by" style="width:100%;">
                    <option value="date" <?php echo $sortBy === 'date' ? 'selected' : ''; ?>>Theo thời gian đơn</option>
                    <option value="ward" <?php echo $sortBy === 'ward' ? 'selected' : ''; ?>>Theo phường giao hàng</option>
                </select>
            </div>
            <div style="display:flex; gap:0.5rem;">
                <button class="btn btn-ghost btn-sm" type="submit">Lọc</button>
                <a class="btn btn-ghost btn-sm" href="index.php?page=orders">Đặt lại</a>
            </div>
        </form>
    </div>

    <div class="table-wrap">
        <div class="table-toolbar">
            <h3>Danh sách đơn hàng</h3>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Mã đơn</th>
                    <th>Khách hàng</th>
                    <th>Sản phẩm</th>
                    <th>Tổng tiền</th>
                    <th>Ngày đặt</th>
                    <th>Phường / quận</th>
                    <th>Trạng thái</th>
                    <th>Cập nhật</th>
                    <th>Chi tiết</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="9" class="td-muted">Không có đơn hàng phù hợp bộ lọc.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td class="td-gold"><?php echo htmlspecialchars($order['order_number']); ?></td>
                            <td>
                                <div class="td-name"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                                <div class="td-muted"><?php echo htmlspecialchars($order['customer_phone'] ?: $order['customer_email']); ?></div>
                            </td>
                            <td class="td-muted" style="max-width:220px;"><?php echo htmlspecialchars($order['products_text'] ?? '-'); ?></td>
                            <td class="td-gold"><?php echo number_format((int)$order['total_amount'], 0, ',', '.'); ?>₫</td>
                            <td><?php echo date('d/m/Y H:i', strtotime((string)$order['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars(trim((string)($order['ward'] ?? '')) !== '' ? (($order['ward'] ?? '') . ' / ' . ($order['district'] ?: '-')) : ($order['district'] ?: '-')); ?></td>
                            <td>
                                <span class="badge <?php echo in_array($order['status'], ['delivered', 'confirmed'], true) ? 'badge-success' : ($order['status'] === 'cancelled' ? 'badge-danger' : 'badge-muted'); ?>">
                                    <?php echo htmlspecialchars(mapOrderStatusToLabel((string)$order['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <form method="post" style="display:flex; gap:0.4rem; align-items:center;">
                                    <input type="hidden" name="order_action" value="update_status">
                                    <input type="hidden" name="order_id" value="<?php echo (int)$order['id']; ?>">
                                    <input type="hidden" name="keep_query" value="<?php echo htmlspecialchars($keepQuery); ?>">
                                    <select class="search-inline" name="new_status">
                                        <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Chưa xử lý</option>
                                        <option value="confirmed" <?php echo $order['status'] === 'confirmed' ? 'selected' : ''; ?>>Đã xác nhận</option>
                                        <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Đã giao thành công</option>
                                        <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
                                    </select>
                                    <button class="btn btn-ghost btn-sm" type="submit">Lưu</button>
                                </form>
                            </td>
                            <td>
                                <a class="btn btn-ghost btn-sm" href="index.php?page=orders&detail=<?php echo (int)$order['id']; ?>&<?php echo htmlspecialchars($keepQuery); ?>">Xem</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($orderDetail): ?>
        <div class="modal-overlay open" id="order-detail-modal">
            <div class="modal" style="max-width: 1080px;">
                <div class="modal-inner" style="padding: 1.25rem 1.25rem 1rem;">
                    <div class="modal-header" style="margin-bottom: 1rem;">
                        <h2>Chi tiết đơn: <?php echo htmlspecialchars($orderDetail['order_number']); ?></h2>
                        <a class="modal-close" href="index.php?page=orders&<?php echo htmlspecialchars($keepQuery); ?>" style="text-decoration:none;">✕</a>
                    </div>

                    <div class="table-wrap" style="padding:1.15rem; margin-bottom:1rem;">
                        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:0.8rem;">
                            <div><span class="td-muted">Khách hàng:</span><br><strong><?php echo htmlspecialchars($orderDetail['customer_name']); ?></strong></div>
                            <div><span class="td-muted">Điện thoại:</span><br><strong><?php echo htmlspecialchars($orderDetail['customer_phone'] ?? '-'); ?></strong></div>
                            <div><span class="td-muted">Email:</span><br><strong><?php echo htmlspecialchars($orderDetail['customer_email']); ?></strong></div>
                            <div><span class="td-muted">Trạng thái:</span><br><strong><?php echo htmlspecialchars(mapOrderStatusToLabel((string)$orderDetail['status'])); ?></strong></div>
                            <div><span class="td-muted">Địa chỉ:</span><br><strong><?php echo htmlspecialchars(formatOrderAddress($orderDetail['shipping_address'] ?? '', $orderDetail['ward'] ?? '', $orderDetail['district'] ?? '', $orderDetail['city'] ?? '')); ?></strong></div>
                            <div><span class="td-muted">Thời gian đơn:</span><br><strong><?php echo date('d/m/Y H:i', strtotime((string)$orderDetail['created_at'])); ?></strong></div>
                        </div>
                    </div>

                    <div class="table-wrap" style="padding: 0; overflow: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Sản phẩm</th>
                                    <th>Thương hiệu</th>
                                    <th>Số lượng</th>
                                    <th>Đơn giá</th>
                                    <th>Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orderItems as $item): ?>
                                    <tr>
                                        <td class="td-name"><?php echo htmlspecialchars($item['product_name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['product_brand']); ?></td>
                                        <td><?php echo (int)$item['quantity']; ?></td>
                                        <td><?php echo number_format((int)$item['unit_price'], 0, ',', '.'); ?>₫</td>
                                        <td class="td-gold"><?php echo number_format((int)$item['total_price'], 0, ',', '.'); ?>₫</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="modal-footer" style="margin-top: 1rem; padding-top: 1rem;">
                        <strong style="margin-right:auto;">Tổng đơn: <?php echo number_format((int)$orderDetail['total_amount'], 0, ',', '.'); ?>₫</strong>
                        <a class="btn btn-ghost" href="index.php?page=orders&<?php echo htmlspecialchars($keepQuery); ?>">Đãng</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</section>


