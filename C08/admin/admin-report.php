<?php
require_once __DIR__ . '/../setup_db.php';

function ensureReportSchema(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS receipts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        receipt_code VARCHAR(30) NOT NULL UNIQUE,
        import_date DATE NOT NULL,
        import_round INT NOT NULL,
        status ENUM('draft','completed') NOT NULL DEFAULT 'draft',
        notes TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        completed_at TIMESTAMP NULL DEFAULT NULL,
        INDEX idx_import_date (import_date),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS receipt_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        receipt_id INT NOT NULL,
        product_id INT NOT NULL,
        import_price INT UNSIGNED NOT NULL,
        quantity INT UNSIGNED NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_receipt_id (receipt_id),
        INDEX idx_product_id (product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $checkCode = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'product_code'");
    $checkCode->execute();
    if ((int)$checkCode->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE products ADD COLUMN product_code VARCHAR(30) NULL UNIQUE AFTER id");
        $pdo->exec("UPDATE products SET product_code = CONCAT('SP', LPAD(id, 3, '0')) WHERE product_code IS NULL OR product_code = ''");
    }

    $checkInitialStock = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'initial_stock'");
    $checkInitialStock->execute();
    if ((int)$checkInitialStock->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE products ADD COLUMN initial_stock INT UNSIGNED NOT NULL DEFAULT 0 AFTER image");
    }
}

ensureReportSchema($pdo);

$fromDate = trim($_GET['from_date'] ?? date('Y-m-01'));
$toDate = trim($_GET['to_date'] ?? date('Y-m-d'));
$categoryFilter = trim($_GET['category'] ?? '');
$lowThreshold = max(1, (int)($_GET['low_threshold'] ?? 5));

if ($fromDate === '') {
    $fromDate = date('Y-m-01');
}
if ($toDate === '') {
    $toDate = date('Y-m-d');
}

$categories = $pdo->query("SELECT name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);

$whereCategory = '';
$params = [$fromDate, $toDate, $fromDate, $toDate];
if ($categoryFilter !== '') {
    $whereCategory = 'WHERE p.category = ?';
    $params[] = $categoryFilter;
}

$reportSql = "SELECT
    p.id,
    COALESCE(p.product_code, CONCAT('SP', LPAD(p.id, 3, '0'))) AS product_code,
    p.name,
    p.category,
    COALESCE(p.initial_stock, 0) AS initial_stock,
    COALESCE(imp.total_import, 0) AS total_import,
    COALESCE(exp.total_export, 0) AS total_export,
    GREATEST(0, COALESCE(p.initial_stock, 0) + COALESCE(imp.total_import, 0) - COALESCE(exp.total_export, 0)) AS net_qty
FROM products p
LEFT JOIN (
    SELECT ri.product_id, SUM(ri.quantity) AS total_import
    FROM receipt_items ri
    INNER JOIN receipts r ON r.id = ri.receipt_id
    WHERE r.status = 'completed'
      AND DATE(r.import_date) >= ?
      AND DATE(r.import_date) <= ?
    GROUP BY ri.product_id
) imp ON imp.product_id = p.id
LEFT JOIN (
    SELECT oi.product_id, SUM(oi.quantity) AS total_export
    FROM order_items oi
    INNER JOIN orders o ON o.id = oi.order_id
        WHERE o.status <> 'cancelled'
            AND DATE(o.created_at) >= ?
            AND DATE(o.created_at) <= ?
    GROUP BY oi.product_id
) exp ON exp.product_id = p.id
{$whereCategory}
ORDER BY p.name ASC";

$reportStmt = $pdo->prepare($reportSql);
$reportStmt->execute($params);
$reportRows = $reportStmt->fetchAll(PDO::FETCH_ASSOC);

$summaryImport = 0;
$summaryExport = 0;
$summaryStock = 0;
foreach ($reportRows as $row) {
    $summaryImport += (int)$row['total_import'];
    $summaryExport += (int)$row['total_export'];
    $summaryStock += (int)$row['net_qty'];
}

$warningWhere = '';
$warningParams = [];
if ($categoryFilter !== '') {
    $warningWhere = 'WHERE p.category = ?';
    $warningParams[] = $categoryFilter;
}

$warningSql = "SELECT
    p.id,
    COALESCE(p.product_code, CONCAT('SP', LPAD(p.id, 3, '0'))) AS product_code,
    p.name,
    p.category,
    GREATEST(0, COALESCE(p.initial_stock, 0) + COALESCE(imp.total_import, 0) - COALESCE(exp.total_export, 0)) AS current_stock
FROM products p
LEFT JOIN (
    SELECT ri.product_id, SUM(ri.quantity) AS total_import
    FROM receipt_items ri
    INNER JOIN receipts r ON r.id = ri.receipt_id
    WHERE r.status = 'completed'
    GROUP BY ri.product_id
) imp ON imp.product_id = p.id
LEFT JOIN (
    SELECT oi.product_id, SUM(oi.quantity) AS total_export
    FROM order_items oi
    INNER JOIN orders o ON o.id = oi.order_id
    WHERE o.status <> 'cancelled'
    GROUP BY oi.product_id
) exp ON exp.product_id = p.id
{$warningWhere}
HAVING current_stock <= ?
ORDER BY current_stock ASC, p.name ASC";

$warningParams[] = $lowThreshold;
$warningStmt = $pdo->prepare($warningSql);
$warningStmt->execute($warningParams);
$warningRows = $warningStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="admin-page<?php echo ($page === 'report') ? ' active' : ''; ?>" id="page-report">
    <div class="page-header">
        <div class="page-header-left">
            <span class="eyebrow">✦ Phân tích</span>
            <h1>Báo cáo nhập - xuất và cảnh báo tồn kho</h1>
        </div>
    </div>

    <div class="table-wrap" style="margin-bottom: 1.5rem; padding: 1.25rem 1.5rem;">
        <form method="get" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:1rem; align-items:end;">
            <input type="hidden" name="page" value="report">
            <div>
                <span class="filter-label">Từ ngày</span>
                <input class="search-inline" type="date" name="from_date" value="<?php echo htmlspecialchars($fromDate); ?>" style="width:100%;">
            </div>
            <div>
                <span class="filter-label">Đến ngày</span>
                <input class="search-inline" type="date" name="to_date" value="<?php echo htmlspecialchars($toDate); ?>" style="width:100%;">
            </div>
            <div>
                <span class="filter-label">Loại sản phẩm</span>
                <select class="search-inline" name="category" style="width:100%;">
                    <option value="">Tất cả loại</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $categoryFilter === $cat ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <span class="filter-label">Ngưỡng sắp hết</span>
                <input class="search-inline" type="number" min="1" name="low_threshold" value="<?php echo (int)$lowThreshold; ?>" style="width:100%;">
            </div>
            <div style="display:flex; gap:0.5rem;">
                <button class="btn btn-gold btn-sm" type="submit">Xem báo cáo</button>
                <a class="btn btn-ghost btn-sm" href="index.php?page=report">Đặt lại</a>
            </div>
        </form>
    </div>

    <div class="table-wrap" style="margin-bottom:1.5rem; padding:1rem 1.25rem;">
        <div style="display:flex; gap:1.5rem; flex-wrap:wrap;">
            <div><span class="td-muted">Khoảng thời gian:</span> <strong><?php echo date('d/m/Y', strtotime($fromDate)); ?> - <?php echo date('d/m/Y', strtotime($toDate)); ?></strong></div>
            <div><span class="td-muted">Tổng nhập:</span> <strong><?php echo number_format($summaryImport, 0, ',', '.'); ?></strong></div>
            <div><span class="td-muted">Tổng xuất:</span> <strong><?php echo number_format($summaryExport, 0, ',', '.'); ?></strong></div>
            <div><span class="td-muted">Tổng tồn còn lại:</span> <strong><?php echo number_format($summaryStock, 0, ',', '.'); ?></strong></div>
        </div>
    </div>

    <div class="table-wrap" style="margin-bottom:1.5rem;">
        <div class="table-toolbar">
            <h3>Báo cáo tổng số lượng nhập - xuất theo sản phẩm trong khoảng thời gian</h3>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Mã SP</th>
                    <th>Sản phẩm</th>
                    <th>Loại</th>
                    <th>Tổng nhập</th>
                    <th>Tổng xuất</th>
                    <th>Tồn còn lại</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reportRows)): ?>
                    <tr>
                        <td colspan="6" class="td-muted">Không có dữ liệu trong khoảng thời gian đã chọn.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reportRows as $row): ?>
                        <tr>
                            <td class="td-muted"><?php echo htmlspecialchars($row['product_code']); ?></td>
                            <td class="td-name"><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><span class="badge badge-muted"><?php echo htmlspecialchars($row['category']); ?></span></td>
                            <td><?php echo number_format((int)$row['total_import'], 0, ',', '.'); ?></td>
                            <td><?php echo number_format((int)$row['total_export'], 0, ',', '.'); ?></td>
                            <td class="td-gold"><?php echo number_format((int)$row['net_qty'], 0, ',', '.'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="table-wrap">
        <div class="table-toolbar">
            <h3>Cảnh báo sản phẩm sắp hết hàng (ngưỡng ≤ <?php echo (int)$lowThreshold; ?>)</h3>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Mã SP</th>
                    <th>Sản phẩm</th>
                    <th>Loại</th>
                    <th>Tồn kho hiện tại</th>
                    <th>Cảnh báo</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($warningRows)): ?>
                    <tr>
                        <td colspan="5" class="td-muted">Không có sản phẩm nào dưới ngưỡng cảnh báo.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($warningRows as $row): ?>
                        <tr>
                            <td class="td-muted"><?php echo htmlspecialchars($row['product_code']); ?></td>
                            <td class="td-name"><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><span class="badge badge-muted"><?php echo htmlspecialchars($row['category']); ?></span></td>
                            <td class="td-gold"><?php echo number_format((int)$row['current_stock'], 0, ',', '.'); ?></td>
                            <td><span class="badge badge-danger">Sắp hết hàng</span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>


