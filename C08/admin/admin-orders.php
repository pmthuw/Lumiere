<?php
require_once __DIR__ . '/../setup_db.php';

/** @var PDO $pdo */
if (!isset($pdo) || !($pdo instanceof PDO)) {
    throw new RuntimeException('Database connection is not available.');
}

try {
    $columns = [
        'ward' => "ALTER TABLE orders ADD COLUMN ward VARCHAR(100) DEFAULT NULL AFTER shipping_address",
        'district' => "ALTER TABLE orders ADD COLUMN district VARCHAR(100) DEFAULT NULL AFTER ward",
        'city' => "ALTER TABLE orders ADD COLUMN city VARCHAR(100) DEFAULT NULL AFTER district"
    ];

    foreach ($columns as $columnName => $alterSql) {
        $hasColumn = (bool)$pdo->query("SHOW COLUMNS FROM orders LIKE '{$columnName}'")->fetch(PDO::FETCH_ASSOC);
        if (!$hasColumn) {
            $pdo->exec($alterSql);
        }
    }

    // Keep status values in DB aligned with current admin workflow.
    $pdo->exec("UPDATE orders SET status = 'confirmed' WHERE status IN ('processing', 'shipped')");
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

function normalizeOrderStatusForWorkflow(string $status): string
{
    if (in_array($status, ['processing', 'shipped'], true)) {
        return 'confirmed';
    }

    return $status;
}

function canTransitionOrderStatus(string $fromStatus, string $toStatus): bool
{
    $from = normalizeOrderStatusForWorkflow($fromStatus);
    $to = normalizeOrderStatusForWorkflow($toStatus);

    if ($from === $to) {
        return true;
    }

    if ($from === 'pending' && $to === 'confirmed') {
        return true;
    }

    if ($from === 'confirmed' && in_array($to, ['delivered', 'cancelled'], true)) {
        return true;
    }

    return false;
}

function getNextStatusOptions(string $currentStatus): array
{
    $current = normalizeOrderStatusForWorkflow($currentStatus);

    if ($current === 'pending') {
        return ['pending', 'confirmed'];
    }

    if ($current === 'confirmed') {
        return ['confirmed', 'delivered', 'cancelled'];
    }

    return [$current];
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

function normalizeOrderFilterDate(string $rawValue, string $todayDate): string
{
    $value = trim($rawValue);
    if ($value === '') {
        return '';
    }

    $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y'];
    $normalized = null;

    foreach ($formats as $format) {
        $dt = DateTime::createFromFormat($format, $value);
        if ($dt instanceof DateTime) {
            $normalized = $dt->format('Y-m-d');
            break;
        }
    }

    if ($normalized === null) {
        return '';
    }

    if ($normalized > $todayDate) {
        return $todayDate;
    }

    return $normalized;
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
                $currentStmt = $pdo->prepare("SELECT status FROM orders WHERE id = ? LIMIT 1");
                $currentStmt->execute([$orderId]);
                $currentStatusRaw = $currentStmt->fetchColumn();

                if ($currentStatusRaw === false) {
                    throw new RuntimeException('Không tìm thấy đơn hàng cần cập nhật.');
                }

                $currentStatus = (string)$currentStatusRaw;
                if (!canTransitionOrderStatus($currentStatus, $newStatus)) {
                    throw new RuntimeException('Chỉ được chuyển trạng thái 1 chiều: Chưa xử lý -> Đã xác nhận -> (Đã giao thành công hoặc Đã hủy).');
                }

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
$statusFilter = trim($_GET['status'] ?? '');
$wardFilter = trim($_GET['ward'] ?? '');
$todayDate = date('Y-m-d');
$fromDate = normalizeOrderFilterDate((string)($_GET['from_date'] ?? ''), $todayDate);
$toDate = normalizeOrderFilterDate((string)($_GET['to_date'] ?? ''), $todayDate);
$sortBy = trim($_GET['sort_by'] ?? 'ward_asc');

if ($fromDate !== '' && $toDate !== '' && $fromDate > $toDate) {
    $fromDate = $toDate;
}


if (!in_array($sortBy, ['ward_asc', 'ward_desc'], true)) {
    $sortBy = 'ward_asc';
}

$whereParts = [];
$params = [];

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

$orderBy = 'COALESCE(o.ward, "") ASC, COALESCE(o.district, "") ASC, o.created_at DESC';
if ($sortBy === 'ward_desc') {
    $orderBy = 'COALESCE(o.ward, "") DESC, COALESCE(o.district, "") DESC, o.created_at DESC';
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
                <input class="search-inline" type="date" id="orders-from-date" name="from_date" max="<?php echo htmlspecialchars($todayDate); ?>" value="<?php echo htmlspecialchars($fromDate); ?>" style="width:100%;">
            </div>
            <div>
                <span class="filter-label">Đến ngày</span>
                <input class="search-inline" type="date" id="orders-to-date" name="to_date" max="<?php echo htmlspecialchars($todayDate); ?>" value="<?php echo htmlspecialchars($toDate); ?>" style="width:100%;">
            </div>
            <div>
                <span class="filter-label">Sắp xếp</span>
                <select class="search-inline" name="sort_by" style="width:100%;">
                    <option value="ward_asc" <?php echo $sortBy === 'ward_asc' ? 'selected' : ''; ?>>Theo phường giao hàng (A-Z)</option>
                    <option value="ward_desc" <?php echo $sortBy === 'ward_desc' ? 'selected' : ''; ?>>Theo phường giao hàng (Z-A)</option>
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
                        <?php $normalizedStatus = normalizeOrderStatusForWorkflow((string)$order['status']); ?>
                        <?php $nextStatusOptions = getNextStatusOptions((string)$order['status']); ?>
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
                                <span class="badge <?php echo in_array($normalizedStatus, ['delivered', 'confirmed'], true) ? 'badge-success' : ($normalizedStatus === 'cancelled' ? 'badge-danger' : 'badge-muted'); ?>">
                                    <?php echo htmlspecialchars(mapOrderStatusToLabel((string)$order['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <form method="post" style="display:flex; gap:0.4rem; align-items:center;">
                                    <input type="hidden" name="order_action" value="update_status">
                                    <input type="hidden" name="order_id" value="<?php echo (int)$order['id']; ?>">
                                    <input type="hidden" name="keep_query" value="<?php echo htmlspecialchars($keepQuery); ?>">
                                    <select class="search-inline" name="new_status">
                                        <?php if (in_array('pending', $nextStatusOptions, true)): ?>
                                            <option value="pending" <?php echo $normalizedStatus === 'pending' ? 'selected' : ''; ?>>Chưa xử lý</option>
                                        <?php endif; ?>
                                        <?php if (in_array('confirmed', $nextStatusOptions, true)): ?>
                                            <option value="confirmed" <?php echo $normalizedStatus === 'confirmed' ? 'selected' : ''; ?>>Đã xác nhận</option>
                                        <?php endif; ?>
                                        <?php if (in_array('delivered', $nextStatusOptions, true)): ?>
                                            <option value="delivered" <?php echo $normalizedStatus === 'delivered' ? 'selected' : ''; ?>>Đã giao thành công</option>
                                        <?php endif; ?>
                                        <?php if (in_array('cancelled', $nextStatusOptions, true)): ?>
                                            <option value="cancelled" <?php echo $normalizedStatus === 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
                                        <?php endif; ?>
                                    </select>
                                    <button class="btn btn-ghost btn-sm" type="submit" <?php echo in_array($normalizedStatus, ['delivered', 'cancelled'], true) ? 'disabled' : ''; ?>>Lưu</button>
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
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</section>

<script>
    (function () {
        const today = '<?php echo htmlspecialchars($todayDate, ENT_QUOTES); ?>';
        const fromInput = document.getElementById('orders-from-date');
        const toInput = document.getElementById('orders-to-date');

        const validateOne = (input) => {
            if (!input) return;
            const isFuture = !!input.value && input.value > today;
            if (isFuture) {
                input.style.borderColor = '#e05050';
                input.style.background = 'rgba(224, 80, 80, 0.08)';
                input.setCustomValidity('Không được chọn ngày trong tương lai.');
            } else {
                input.style.borderColor = '';
                input.style.background = '';
                input.setCustomValidity('');
            }
        };

        [fromInput, toInput].forEach((input) => {
            if (!input) return;
            input.addEventListener('input', () => validateOne(input));
            input.addEventListener('change', () => validateOne(input));
            validateOne(input);
        });
    })();
</script>


