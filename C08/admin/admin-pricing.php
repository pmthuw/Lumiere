<?php
require_once __DIR__ . '/../setup_db.php';

function ensurePricingSchema(PDO $pdo): void
{
    $checkProfit = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'profit_rate'");
    $checkProfit->execute();
    if ((int)$checkProfit->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE products ADD COLUMN profit_rate DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER price");
    }

    $checkAvgCost = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'avg_import_price'");
    $checkAvgCost->execute();
    if ((int)$checkAvgCost->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE products ADD COLUMN avg_import_price DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER initial_stock");
    }

    $checkCode = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'product_code'");
    $checkCode->execute();
    if ((int)$checkCode->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE products ADD COLUMN product_code VARCHAR(30) NULL UNIQUE AFTER id");
        $pdo->exec("UPDATE products SET product_code = CONCAT('SP', LPAD(id, 3, '0')) WHERE product_code IS NULL OR product_code = ''");
    }

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

    // Backfill initial import cost for legacy products:
    // 1) from latest completed receipt item
    // 2) fallback from selling price and profit rate
    $pdo->exec("UPDATE products p
        LEFT JOIN (
            SELECT ri.product_id, ri.import_price
            FROM receipt_items ri
            INNER JOIN receipts r ON r.id = ri.receipt_id
            INNER JOIN (
                SELECT ri2.product_id, MAX(CONCAT(DATE_FORMAT(r2.import_date, '%Y%m%d'), '-', LPAD(r2.id, 10, '0'), '-', LPAD(ri2.id, 10, '0'))) AS max_key
                FROM receipt_items ri2
                INNER JOIN receipts r2 ON r2.id = ri2.receipt_id
                WHERE r2.status = 'completed'
                GROUP BY ri2.product_id
            ) x ON x.product_id = ri.product_id
                 AND CONCAT(DATE_FORMAT(r.import_date, '%Y%m%d'), '-', LPAD(r.id, 10, '0'), '-', LPAD(ri.id, 10, '0')) = x.max_key
        ) latest ON latest.product_id = p.id
        SET p.avg_import_price = CASE
            WHEN latest.import_price IS NOT NULL THEN latest.import_price
            WHEN COALESCE(p.profit_rate, 0) > 0 THEN ROUND(p.price / (1 + (p.profit_rate / 100)), 2)
            ELSE p.price
        END
        WHERE COALESCE(p.avg_import_price, 0) <= 0");
}

function pricingRedirect(string $query = ''): void
{
    $url = 'index.php?page=pricing';
    if ($query !== '') {
        $url .= '&' . ltrim($query, '&');
    }
    header('Location: ' . $url);
    exit;
}

ensurePricingSchema($pdo);

$successMessage = '';
$errorMessage = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = $_POST['pricing_action'] ?? '';

    if ($action === 'update_profit_rate') {
        $productId = (int)($_POST['product_id'] ?? 0);
        $profitRate = (float)($_POST['profit_rate'] ?? 0);

        if ($productId <= 0 || $profitRate < 0) {
            $errorMessage = 'Dữ liệu cập nhật tỉ lệ lợi nhuận không hợp lệ.';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE products
                    SET profit_rate = ?,
                        price = CASE
                            WHEN COALESCE(avg_import_price, 0) > 0 THEN ROUND(avg_import_price * (1 + (? / 100)))
                            ELSE price
                        END
                    WHERE id = ?");
                $stmt->execute([$profitRate, $profitRate, $productId]);
                pricingRedirect($_POST['keep_query'] ?? 'updated=1');
            } catch (Throwable $e) {
                $errorMessage = 'Không thể cập nhật tỉ lệ lợi nhuận: ' . $e->getMessage();
            }
        }
    }
}

if (($_GET['updated'] ?? '') === '1') {
    $successMessage = 'Đã cập nhật tỉ lệ lợi nhuận theo sản phẩm.';
}

$search = trim($_GET['q'] ?? '');
$receiptFilter = trim($_GET['receipt_code'] ?? '');

$params = [];
$where = '';
if ($search !== '') {
    $where = "WHERE p.name LIKE ? OR p.category LIKE ? OR COALESCE(p.product_code,'') LIKE ?";
    $kw = '%' . $search . '%';
    $params = [$kw, $kw, $kw];
}

$productSql = "SELECT p.id, COALESCE(p.product_code, CONCAT('SP', LPAD(p.id, 3, '0'))) AS product_code,
    p.name, p.category, p.price AS selling_price, COALESCE(p.profit_rate, 0) AS profit_rate,
    COALESCE(p.avg_import_price, 0) AS avg_cost,
    r.receipt_code AS latest_receipt_code, r.import_date AS latest_import_date
FROM products p
LEFT JOIN receipt_items ri ON ri.id = (
    SELECT ri2.id
    FROM receipt_items ri2
    INNER JOIN receipts r2 ON r2.id = ri2.receipt_id
    WHERE ri2.product_id = p.id AND r2.status = 'completed'
    ORDER BY r2.import_date DESC, r2.id DESC, ri2.id DESC
    LIMIT 1
)
LEFT JOIN receipts r ON r.id = ri.receipt_id
{$where}
ORDER BY p.id DESC";

$productStmt = $pdo->prepare($productSql);
$productStmt->execute($params);
$products = $productStmt->fetchAll(PDO::FETCH_ASSOC);

$receiptQuery = "SELECT r.receipt_code, r.import_date, p.id AS product_id,
    COALESCE(p.product_code, CONCAT('SP', LPAD(p.id, 3, '0'))) AS product_code,
    p.name, p.price AS selling_price, COALESCE(p.profit_rate,0) AS profit_rate,
    ri.import_price AS cost_price, ri.quantity,
    ROUND(COALESCE(p.profit_rate, 0), 2) AS profit_percent_by_receipt,
    ROUND(ri.import_price * (1 + (COALESCE(p.profit_rate, 0) / 100))) AS selling_price_by_receipt
FROM receipt_items ri
INNER JOIN receipts r ON r.id = ri.receipt_id
INNER JOIN products p ON p.id = ri.product_id
WHERE r.status = 'completed'";

$receiptParams = [];
if ($receiptFilter !== '') {
    $receiptQuery .= " AND r.receipt_code LIKE ?";
    $receiptParams[] = '%' . $receiptFilter . '%';
}

$receiptQuery .= " ORDER BY r.import_date DESC, r.receipt_code DESC, p.name ASC LIMIT 300";
$receiptStmt = $pdo->prepare($receiptQuery);
$receiptStmt->execute($receiptParams);
$receiptPricingRows = $receiptStmt->fetchAll(PDO::FETCH_ASSOC);

$keepQuery = http_build_query([
    'q' => $search,
    'receipt_code' => $receiptFilter
]);
?>

<section class="admin-page<?php echo ($page === 'pricing') ? ' active' : ''; ?>" id="page-pricing">
    <div class="page-header">
        <div class="page-header-left">
            <span class="eyebrow">✦ Giá bán</span>
            <h1>Quản lý giá bán</h1>
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

    <div class="table-wrap" style="margin-bottom:1.5rem;">
        <div class="table-toolbar">
            <h3>Tỉ lệ lợi nhuận theo sản phẩm</h3>
            <form method="get" style="display:flex; gap:0.5rem; align-items:center;">
                <input type="hidden" name="page" value="pricing">
                <?php if ($receiptFilter !== ''): ?>
                    <input type="hidden" name="receipt_code" value="<?php echo htmlspecialchars($receiptFilter); ?>">
                <?php endif; ?>
                <input class="search-inline" type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Tìm theo mã, tên, loại...">
                <button class="btn btn-ghost btn-sm" type="submit">Tìm</button>
                <a class="btn btn-ghost btn-sm" href="index.php?page=pricing<?php echo $receiptFilter !== '' ? '&receipt_code=' . urlencode($receiptFilter) : ''; ?>">Đặt lại</a>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Mã SP</th>
                    <th>Tên sản phẩm</th>
                    <th>Giá nhập bình quân</th>
                    <th>% lợi nhuận</th>
                    <th>Giá bán hiện tại</th>
                    <th>Giá bán theo công thức</th>
                    <th>Phiếu nhập gần nhất</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <?php
                        $cost = (float)($product['avg_cost'] ?? 0);
                        $profitRate = (float)($product['profit_rate'] ?? 0);
                        $computedSelling = $cost > 0 ? (int)round($cost * (1 + ($profitRate / 100))) : (int)($product['selling_price'] ?? 0);
                    ?>
                    <tr>
                        <td class="td-muted"><?php echo htmlspecialchars($product['product_code']); ?></td>
                        <td class="td-name"><?php echo htmlspecialchars($product['name']); ?></td>
                        <td><?php echo $cost > 0 ? number_format((int)round($cost), 0, ',', '.') . '₫' : '-'; ?></td>
                        <td>
                            <form method="post" style="display:flex; gap:0.4rem; align-items:center;">
                                <input type="hidden" name="pricing_action" value="update_profit_rate">
                                <input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>">
                                <input type="hidden" name="keep_query" value="<?php echo htmlspecialchars($keepQuery); ?>">
                                <input class="form-control" style="max-width:90px;" type="number" name="profit_rate" step="0.01" min="0" value="<?php echo htmlspecialchars((string)$profitRate); ?>">
                                <span>%</span>
                                <button class="btn btn-ghost btn-sm" type="submit">Lưu</button>
                            </form>
                        </td>
                        <td class="td-gold"><?php echo number_format((int)$product['selling_price'], 0, ',', '.'); ?>₫</td>
                        <td><?php echo number_format($computedSelling, 0, ',', '.'); ?>₫</td>
                        <td class="td-muted"><?php echo !empty($product['latest_receipt_code']) ? htmlspecialchars($product['latest_receipt_code']) . ' (' . date('d/m/Y', strtotime((string)$product['latest_import_date'])) . ')' : '-'; ?></td>
                        <td><a class="btn btn-ghost btn-sm" href="index.php?page=pricing&receipt_code=<?php echo urlencode((string)($product['latest_receipt_code'] ?? '')); ?>">Tra cứu phiếu</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="table-wrap">
        <div class="table-toolbar">
            <h3>Tra cứu giá vốn - % lợi nhuận - giá bán theo phiếu nhập</h3>
            <form method="get" style="display:flex; gap:0.5rem; align-items:center;">
                <input type="hidden" name="page" value="pricing">
                <?php if ($search !== ''): ?>
                    <input type="hidden" name="q" value="<?php echo htmlspecialchars($search); ?>">
                <?php endif; ?>
                <input class="search-inline" type="text" name="receipt_code" value="<?php echo htmlspecialchars($receiptFilter); ?>" placeholder="Nhập mã phiếu nhập...">
                <button class="btn btn-ghost btn-sm" type="submit">Tra cứu</button>
                <a class="btn btn-ghost btn-sm" href="index.php?page=pricing<?php echo $search !== '' ? '&q=' . urlencode($search) : ''; ?>">Đặt lại</a>
            </form>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Phiếu nhập</th>
                    <th>Ngày nhập</th>
                    <th>Mã SP</th>
                    <th>Tên sản phẩm</th>
                    <th>SL nhập</th>
                    <th>Giá vốn</th>
                    <th>% lợi nhuận theo phiếu</th>
                    <th>Giá bán hiện tại</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($receiptPricingRows)): ?>
                    <tr>
                        <td colspan="8" class="td-muted">Không có dữ liệu phù hợp.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($receiptPricingRows as $row): ?>
                        <tr>
                            <td class="td-gold"><?php echo htmlspecialchars($row['receipt_code']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime((string)$row['import_date'])); ?></td>
                            <td class="td-muted"><?php echo htmlspecialchars($row['product_code']); ?></td>
                            <td class="td-name"><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo (int)$row['quantity']; ?></td>
                            <td><?php echo number_format((int)$row['cost_price'], 0, ',', '.'); ?>₫</td>
                            <td><?php echo number_format((float)$row['profit_percent_by_receipt'], 2, ',', '.'); ?>%</td>
                            <td class="td-gold"><?php echo number_format((int)$row['selling_price_by_receipt'], 0, ',', '.'); ?>₫</td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>


