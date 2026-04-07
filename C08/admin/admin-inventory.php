<?php
require_once __DIR__ . '/../setup_db.php';

function ensureInventorySchema(PDO $pdo): void
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

ensureInventorySchema($pdo);

$asOfDate = trim($_GET['as_of_date'] ?? date('Y-m-d'));
$categoryFilter = trim($_GET['category'] ?? '');
$lowThreshold = max(1, (int)($_GET['low_threshold'] ?? 5));

if ($asOfDate === '') {
    $asOfDate = date('Y-m-d');
}

$categories = $pdo->query("SELECT name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);

$whereClause = '';
$params = [$asOfDate, $asOfDate];
if ($categoryFilter !== '') {
    $whereClause = 'WHERE p.category = ?';
    $params[] = $categoryFilter;
}

$sql = "SELECT
    p.id,
    COALESCE(p.product_code, CONCAT('SP', LPAD(p.id, 3, '0'))) AS product_code,
    p.name,
    p.category,
    COALESCE(p.initial_stock, 0) AS initial_stock,
    COALESCE(imp.total_import, 0) AS total_import,
    COALESCE(exp.total_export, 0) AS total_export,
    GREATEST(0, COALESCE(p.initial_stock, 0) + COALESCE(imp.total_import, 0) - COALESCE(exp.total_export, 0)) AS stock_qty
FROM products p
LEFT JOIN (
    SELECT ri.product_id, SUM(ri.quantity) AS total_import
    FROM receipt_items ri
    INNER JOIN receipts r ON r.id = ri.receipt_id
    WHERE r.status = 'completed' AND DATE(r.import_date) <= ?
    GROUP BY ri.product_id
) imp ON imp.product_id = p.id
LEFT JOIN (
    SELECT oi.product_id, SUM(oi.quantity) AS total_export
    FROM order_items oi
    INNER JOIN orders o ON o.id = oi.order_id
    WHERE o.status <> 'cancelled' AND DATE(o.created_at) <= ?
    GROUP BY oi.product_id
) exp ON exp.product_id = p.id
{$whereClause}
ORDER BY stock_qty ASC, p.name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalStockByFilter = 0;
$lowItemsCount = 0;
foreach ($rows as $row) {
    $stock = (int)$row['stock_qty'];
    $totalStockByFilter += $stock;
    if ($stock <= $lowThreshold) {
        $lowItemsCount++;
    }
}
?>

<section class="admin-page<?php echo ($page === 'inventory') ? ' active' : ''; ?>" id="page-inventory">
    <div class="page-header">
        <div class="page-header-left">
            <span class="eyebrow">✦ Kho hàng</span>
            <h1>Tồn kho</h1>
        </div>
    </div>

    <div class="table-wrap" style="padding: 1.25rem 1.5rem; margin-bottom: 1.5rem">
        <form method="get" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:1rem; align-items:end;">
            <input type="hidden" name="page" value="inventory">
            <div>
                <span class="filter-label">Thời điểm tra cứu</span>
                <input class="search-inline" type="date" name="as_of_date" value="<?php echo htmlspecialchars($asOfDate); ?>" style="width:100%;">
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
                <button class="btn btn-gold btn-sm" type="submit">Tra cứu</button>
                <a class="btn btn-ghost btn-sm" href="index.php?page=inventory">Đặt lại</a>
            </div>
        </form>
    </div>

    <div class="table-wrap" style="margin-bottom:1.5rem; padding:1rem 1.25rem;">
        <div style="display:flex; gap:1.5rem; flex-wrap:wrap;">
            <div><span class="td-muted">Tổng tồn theo bộ lọc:</span> <strong><?php echo number_format($totalStockByFilter, 0, ',', '.'); ?></strong></div>
            <div><span class="td-muted">Sản phẩm sắp hết (≤ <?php echo (int)$lowThreshold; ?>):</span> <strong><?php echo (int)$lowItemsCount; ?></strong></div>
            <div><span class="td-muted">Thời điểm:</span> <strong><?php echo date('d/m/Y', strtotime($asOfDate)); ?></strong></div>
        </div>
    </div>

    <div class="table-wrap">
        <div class="table-toolbar">
            <h3>Tồn kho tại thời điểm người dòng chọn</h3>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Mã SP</th>
                    <th>Tên sản phẩm</th>
                    <th>Loại</th>
                    <th>Tổng nhập</th>
                    <th>Tổng xuất</th>
                    <th>Tồn kho</th>
                    <th>Cảnh bA�o</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="7" class="td-muted">Không cA� dữ liệu phA� hợp.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <?php $stock = (int)$row['stock_qty']; ?>
                        <tr>
                            <td class="td-muted"><?php echo htmlspecialchars($row['product_code']); ?></td>
                            <td class="td-name"><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><span class="badge badge-muted"><?php echo htmlspecialchars($row['category']); ?></span></td>
                            <td><?php echo number_format((int)$row['total_import'], 0, ',', '.'); ?></td>
                            <td><?php echo number_format((int)$row['total_export'], 0, ',', '.'); ?></td>
                            <td class="<?php echo $stock <= $lowThreshold ? 'td-gold' : ''; ?>"><?php echo number_format($stock, 0, ',', '.'); ?></td>
                            <td>
                                <?php if ($stock <= $lowThreshold): ?>
                                    <span class="badge badge-danger">Sắp hết hàng</span>
                                <?php else: ?>
                                    <span class="badge badge-success">Ổn định</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>


