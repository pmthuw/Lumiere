<?php
require_once __DIR__ . '/../setup_db.php';

function ensureReceiptSchema(PDO $pdo): void
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

    $checkColumn = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'initial_stock'");
    $checkColumn->execute();
    if ((int)$checkColumn->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE products ADD COLUMN initial_stock INT UNSIGNED NOT NULL DEFAULT 0");
    }

    $checkAvgCost = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'avg_import_price'");
    $checkAvgCost->execute();
    if ((int)$checkAvgCost->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE products ADD COLUMN avg_import_price DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER initial_stock");
    }

    $checkProfit = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'profit_rate'");
    $checkProfit->execute();
    if ((int)$checkProfit->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE products ADD COLUMN profit_rate DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER price");
    }

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

    $checkCode = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'product_code'");
    $checkCode->execute();
    if ((int)$checkCode->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE products ADD COLUMN product_code VARCHAR(30) NULL UNIQUE AFTER id");
        $pdo->exec("UPDATE products SET product_code = CONCAT('SP', LPAD(id, 3, '0')) WHERE product_code IS NULL OR product_code = ''");
    }
}

function nextImportRound(PDO $pdo, string $importDate): int
{
    $stmt = $pdo->prepare("SELECT COALESCE(MAX(import_round), 0) FROM receipts WHERE import_date = ?");
    $stmt->execute([$importDate]);
    return ((int)$stmt->fetchColumn()) + 1;
}

function generateReceiptCode(string $importDate, int $round): string
{
    return 'PN' . date('Ymd', strtotime($importDate)) . '-' . str_pad((string)$round, 3, '0', STR_PAD_LEFT);
}

function receiptItemsPayload(): array
{
    $productIds = $_POST['product_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $prices = $_POST['import_price'] ?? [];

    $items = [];
    $count = max(count($productIds), count($quantities), count($prices));

    for ($i = 0; $i < $count; $i++) {
        $productId = (int)($productIds[$i] ?? 0);
        $quantity = (int)($quantities[$i] ?? 0);
        $price = (int)($prices[$i] ?? 0);

        if ($productId > 0 && $quantity > 0 && $price > 0) {
            $items[] = [
                'product_id' => $productId,
                'quantity' => $quantity,
                'import_price' => $price
            ];
        }
    }

    return $items;
}

function redirectToReceiptsPage(): void
{
    header('Location: index.php?page=receipts');
    exit;
}

ensureReceiptSchema($pdo);

$successMessage = '';
$errorMessage = '';
$editingReceipt = null;
$editingItems = [];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = $_POST['receipt_action'] ?? '';

    if ($action === 'create_receipt') {
        $importDate = trim($_POST['import_date'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $items = receiptItemsPayload();

        if ($importDate === '' || empty($items)) {
            $errorMessage = 'Vui lòng chọn ngày nhập và nhập ít nhất 1 dòng sản phẩm hợp lệ.';
        } else {
            try {
                $pdo->beginTransaction();

                $round = nextImportRound($pdo, $importDate);
                $receiptCode = generateReceiptCode($importDate, $round);

                $receiptStmt = $pdo->prepare("INSERT INTO receipts (receipt_code, import_date, import_round, status, notes) VALUES (?, ?, ?, 'draft', ?)");
                $receiptStmt->execute([$receiptCode, $importDate, $round, $notes !== '' ? $notes : null]);
                $receiptId = (int)$pdo->lastInsertId();

                $itemStmt = $pdo->prepare("INSERT INTO receipt_items (receipt_id, product_id, import_price, quantity) VALUES (?, ?, ?, ?)");
                foreach ($items as $item) {
                    $itemStmt->execute([$receiptId, $item['product_id'], $item['import_price'], $item['quantity']]);
                }

                $pdo->commit();
                redirectToReceiptsPage();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errorMessage = 'Không thể tạo phiếu nhập: ' . $e->getMessage();
            }
        }
    }

    if ($action === 'update_receipt') {
        $receiptId = (int)($_POST['receipt_id'] ?? 0);
        $importDate = trim($_POST['import_date'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $items = receiptItemsPayload();

        if ($receiptId <= 0 || $importDate === '' || empty($items)) {
            $errorMessage = 'Dữ liệu cập nhật phiếu nhập không hợp lệ.';
        } else {
            try {
                $statusStmt = $pdo->prepare("SELECT status, receipt_code, import_round FROM receipts WHERE id = ?");
                $statusStmt->execute([$receiptId]);
                $current = $statusStmt->fetch(PDO::FETCH_ASSOC);

                if (!$current) {
                    $errorMessage = 'Phiếu nhập không tồn tại.';
                } elseif ($current['status'] !== 'draft') {
                    $errorMessage = 'Chỉ có thể sửa phiếu nhập trước khi hoàn thành.';
                } else {
                    $pdo->beginTransaction();

                    $newCode = generateReceiptCode($importDate, (int)$current['import_round']);
                    $receiptStmt = $pdo->prepare("UPDATE receipts SET receipt_code = ?, import_date = ?, notes = ? WHERE id = ?");
                    $receiptStmt->execute([$newCode, $importDate, $notes !== '' ? $notes : null, $receiptId]);

                    $pdo->prepare("DELETE FROM receipt_items WHERE receipt_id = ?")->execute([$receiptId]);
                    $itemStmt = $pdo->prepare("INSERT INTO receipt_items (receipt_id, product_id, import_price, quantity) VALUES (?, ?, ?, ?)");
                    foreach ($items as $item) {
                        $itemStmt->execute([$receiptId, $item['product_id'], $item['import_price'], $item['quantity']]);
                    }

                    $pdo->commit();
                    redirectToReceiptsPage();
                }
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errorMessage = 'Không thể cập nhật phiếu nhập: ' . $e->getMessage();
            }
        }
    }

    if ($action === 'complete_receipt') {
        $receiptId = (int)($_POST['receipt_id'] ?? 0);

        if ($receiptId <= 0) {
            $errorMessage = 'Mã phiếu nhập không hợp lệ.';
        } else {
            try {
                $pdo->beginTransaction();

                $statusStmt = $pdo->prepare("SELECT status FROM receipts WHERE id = ?");
                $statusStmt->execute([$receiptId]);
                $status = $statusStmt->fetchColumn();

                if ($status === false) {
                    $errorMessage = 'Phiếu nhập không tồn tại.';
                } elseif ($status !== 'draft') {
                    $errorMessage = 'Phiếu nhập đã hoàn thành, không thể hoàn thành lại.';
                } else {
                    $itemsStmt = $pdo->prepare("SELECT product_id, quantity, import_price FROM receipt_items WHERE receipt_id = ?");
                    $itemsStmt->execute([$receiptId]);
                    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

                    if (empty($items)) {
                        $errorMessage = 'Phiếu nhập không có dòng sản phẩm nào.';
                    } else {
                        $productStmt = $pdo->prepare("SELECT initial_stock, avg_import_price, profit_rate FROM products WHERE id = ? FOR UPDATE");
                        $updateStmt = $pdo->prepare("UPDATE products SET initial_stock = ?, avg_import_price = ?, price = ? WHERE id = ?");

                        foreach ($items as $item) {
                            $productId = (int)$item['product_id'];
                            $importQty = (int)$item['quantity'];
                            $newImportPrice = (float)$item['import_price'];

                            $productStmt->execute([$productId]);
                            $product = $productStmt->fetch(PDO::FETCH_ASSOC);

                            if (!$product) {
                                throw new RuntimeException('Không tìm thấy sản phẩm ID ' . $productId . ' khi hoàn thành phiếu nhập.');
                            }

                            $currentStock = (int)($product['initial_stock'] ?? 0);
                            $currentAvgCost = (float)($product['avg_import_price'] ?? 0);
                            $profitRate = (float)($product['profit_rate'] ?? 0);

                            $newStock = $currentStock + $importQty;
                            if ($newStock <= 0) {
                                $newAvgCost = 0.0;
                            } else {
                                $newAvgCost = (($currentStock * $currentAvgCost) + ($importQty * $newImportPrice)) / $newStock;
                            }

                            $newSellingPrice = (int)round($newAvgCost * (1 + ($profitRate / 100)));
                            $updateStmt->execute([$newStock, $newAvgCost, $newSellingPrice, $productId]);
                        }

                        $doneStmt = $pdo->prepare("UPDATE receipts SET status = 'completed', completed_at = NOW() WHERE id = ?");
                        $doneStmt->execute([$receiptId]);
                        $successMessage = 'Đã hoàn thành phiếu nhập và cập nhật số lượng tồn.';
                    }
                }

                if ($errorMessage === '') {
                    $pdo->commit();
                    redirectToReceiptsPage();
                } else {
                    $pdo->rollBack();
                }
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errorMessage = 'Không thể hoàn thành phiếu nhập: ' . $e->getMessage();
            }
        }
    }
}

$editReceiptId = (int)($_GET['edit_receipt'] ?? 0);
if ($editReceiptId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM receipts WHERE id = ?");
    $stmt->execute([$editReceiptId]);
    $editingReceipt = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    if ($editingReceipt) {
        $itemStmt = $pdo->prepare("SELECT * FROM receipt_items WHERE receipt_id = ? ORDER BY id");
        $itemStmt->execute([$editReceiptId]);
        $editingItems = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

$productKeyword = trim($_GET['product_q'] ?? '');
$receiptKeyword = trim($_GET['receipt_q'] ?? '');

if ($productKeyword !== '') {
    $productsStmt = $pdo->prepare("SELECT id, COALESCE(product_code, CONCAT('SP', LPAD(id, 3, '0'))) AS product_code, name, category FROM products WHERE name LIKE ? OR category LIKE ? OR COALESCE(product_code, '') LIKE ? ORDER BY name LIMIT 200");
    $kw = '%' . $productKeyword . '%';
    $productsStmt->execute([$kw, $kw, $kw]);
} else {
    $productsStmt = $pdo->query("SELECT id, COALESCE(product_code, CONCAT('SP', LPAD(id, 3, '0'))) AS product_code, name, category FROM products ORDER BY name LIMIT 200");
}
$products = $productsStmt->fetchAll(PDO::FETCH_ASSOC);

if ($receiptKeyword !== '') {
    $receiptsStmt = $pdo->prepare("SELECT r.id, r.receipt_code, r.import_date, r.import_round, r.status, r.created_at, COALESCE(SUM(ri.quantity),0) AS total_qty, COALESCE(SUM(ri.quantity * ri.import_price),0) AS total_amount
        FROM receipts r
        LEFT JOIN receipt_items ri ON ri.receipt_id = r.id
        WHERE r.receipt_code LIKE ? OR DATE_FORMAT(r.import_date, '%d/%m/%Y') LIKE ?
        GROUP BY r.id
        ORDER BY r.import_date DESC, r.import_round DESC");
    $kw = '%' . $receiptKeyword . '%';
    $receiptsStmt->execute([$kw, $kw]);
} else {
    $receiptsStmt = $pdo->query("SELECT r.id, r.receipt_code, r.import_date, r.import_round, r.status, r.created_at, COALESCE(SUM(ri.quantity),0) AS total_qty, COALESCE(SUM(ri.quantity * ri.import_price),0) AS total_amount
        FROM receipts r
        LEFT JOIN receipt_items ri ON ri.receipt_id = r.id
        GROUP BY r.id
        ORDER BY r.import_date DESC, r.import_round DESC");
}
$receipts = $receiptsStmt->fetchAll(PDO::FETCH_ASSOC);

$formDate = $editingReceipt['import_date'] ?? date('Y-m-d');
$formNotes = $editingReceipt['notes'] ?? '';
$formItems = !empty($editingItems) ? $editingItems : array_fill(0, 5, ['product_id' => '', 'quantity' => '', 'import_price' => '']);
?>

<section class="admin-page<?php echo ($page === 'receipts') ? ' active' : ''; ?>" id="page-receipts">
    <div class="page-header">
        <div class="page-header-left">
            <span class="eyebrow">✦ Nhập kho</span>
            <h1>Phiếu nhập hàng</h1>
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
            <h3>Tìm sản phẩm để lập phiếu</h3>
            <form method="get" style="display:flex;gap:0.6rem;align-items:center;">
                <input type="hidden" name="page" value="receipts">
                <?php if ($editReceiptId > 0): ?>
                    <input type="hidden" name="edit_receipt" value="<?php echo (int)$editReceiptId; ?>">
                <?php endif; ?>
                <?php if ($receiptKeyword !== ''): ?>
                    <input type="hidden" name="receipt_q" value="<?php echo htmlspecialchars($receiptKeyword); ?>">
                <?php endif; ?>
                <input class="search-inline" type="text" name="product_q" value="<?php echo htmlspecialchars($productKeyword); ?>" placeholder="Tìm theo mã, tên, loại...">
                <button class="btn btn-ghost btn-sm" type="submit">Tìm</button>
                <a class="btn btn-ghost btn-sm" href="index.php?page=receipts<?php echo $receiptKeyword !== '' ? '&receipt_q=' . urlencode($receiptKeyword) : ''; ?>">Đặt lại</a>
            </form>
        </div>
        <p class="td-muted" style="padding:0 1rem 1rem;">Có <?php echo count($products); ?> sản phẩm phù hợp từ tìm kiếm hiện tại để chọn vào phiếu nhập.</p>
    </div>

    <?php if ($editingReceipt): ?>
    <div id="receipt-edit-modal" style="position: fixed; inset: 0; background: rgba(6,8,16,0.72); z-index: 1200; display: flex; align-items: center; justify-content: center; padding: 1rem;">
        <div class="table-wrap" style="padding:1.5rem; margin-bottom:0; width:min(1200px, 100%); max-height:90vh; overflow:auto;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.6rem;">
                <div class="td-muted">Chế độ sửa phiếu nhập</div>
                <a class="btn btn-ghost btn-sm" href="index.php?page=receipts<?php echo $receiptKeyword !== '' ? '&receipt_q=' . urlencode($receiptKeyword) : ''; ?><?php echo $productKeyword !== '' ? '&product_q=' . urlencode($productKeyword) : ''; ?>" aria-label="Đóng">×</a>
            </div>
    <?php else: ?>
    <div class="table-wrap" style="padding:1.5rem; margin-bottom:1.5rem;">
    <?php endif; ?>
        <h3 style="font-family:'Playfair Display', serif; margin-bottom:1rem;">
            <?php if ($editingReceipt): ?>
                Sửa phiếu nhập: <?php echo htmlspecialchars($editingReceipt['receipt_code']); ?>
            <?php else: ?>
                Tạo phiếu nhập mới
            <?php endif; ?>
        </h3>

        <?php if ($editingReceipt && $editingReceipt['status'] !== 'draft'): ?>
            <p class="td-muted">Phiếu này đã hoàn thành, không thể chỉnh sửa. Bạn chỉ có thể xem thông tin.</p>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="receipt_action" value="<?php echo ($editingReceipt && ($editingReceipt['status'] ?? '') === 'draft') ? 'update_receipt' : 'create_receipt'; ?>">
            <?php if ($editingReceipt): ?>
                <input type="hidden" name="receipt_id" value="<?php echo (int)$editingReceipt['id']; ?>">
            <?php endif; ?>

            <div class="form-row form-row-2">
                <div class="form-group">
                    <label>Ngày nhập *</label>
                    <input class="form-control" type="date" name="import_date" value="<?php echo htmlspecialchars($formDate); ?>" <?php echo ($editingReceipt && $editingReceipt['status'] !== 'draft') ? 'disabled' : 'required'; ?>>
                </div>
                <div class="form-group">
                    <label>Lần nhập</label>
                    <input class="form-control" type="text" value="<?php echo $editingReceipt ? ('Lần ' . (int)$editingReceipt['import_round']) : 'Tự động theo ngày nhập'; ?>" readonly>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Ghi chú phiếu</label>
                    <textarea class="form-control" name="notes" <?php echo ($editingReceipt && $editingReceipt['status'] !== 'draft') ? 'disabled' : ''; ?>><?php echo htmlspecialchars($formNotes); ?></textarea>
                </div>
            </div>

            <div class="table-wrap" style="margin-top:1rem;">
                <table>
                    <thead>
                        <tr>
                            <th style="width:50%;">Sản phẩm</th>
                            <th style="width:20%;">Số lượng nhập</th>
                            <th style="width:30%;">Giá nhập</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($formItems as $idx => $item): ?>
                            <tr>
                                <td>
                                    <select class="form-control" name="product_id[]" <?php echo ($editingReceipt && $editingReceipt['status'] !== 'draft') ? 'disabled' : ''; ?>>
                                        <option value="">-- Chọn sản phẩm --</option>
                                        <?php foreach ($products as $product): ?>
                                            <option value="<?php echo (int)$product['id']; ?>" <?php echo ((int)($item['product_id'] ?? 0) === (int)$product['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($product['product_code'] . ' - ' . $product['name'] . ' (' . $product['category'] . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if ($editingReceipt && $editingReceipt['status'] !== 'draft'): ?>
                                        <input type="hidden" name="product_id[]" value="<?php echo (int)($item['product_id'] ?? 0); ?>">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <input class="form-control" type="number" min="0" name="quantity[]" value="<?php echo htmlspecialchars((string)($item['quantity'] ?? '')); ?>" <?php echo ($editingReceipt && $editingReceipt['status'] !== 'draft') ? 'readonly' : ''; ?>>
                                </td>
                                <td>
                                    <input class="form-control" type="number" min="0" name="import_price[]" value="<?php echo htmlspecialchars((string)($item['import_price'] ?? '')); ?>" <?php echo ($editingReceipt && $editingReceipt['status'] !== 'draft') ? 'readonly' : ''; ?>>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div style="display:flex;gap:0.6rem;margin-top:1rem;">
                <?php if (!$editingReceipt || ($editingReceipt['status'] ?? '') === 'draft'): ?>
                    <button class="btn btn-gold" type="submit">
                        <?php echo $editingReceipt ? 'Lưu phiếu nhập' : '+ Tạo phiếu nhập'; ?>
                    </button>
                <?php endif; ?>
                <a class="btn btn-ghost" href="index.php?page=receipts">Phiếu mới</a>
            </div>
        </form>
    </div>
    <?php if ($editingReceipt): ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="table-wrap">
        <div class="table-toolbar">
            <h3>Danh sách phiếu nhập</h3>
            <form method="get" style="display:flex;gap:0.6rem;align-items:center;">
                <input type="hidden" name="page" value="receipts">
                <?php if ($productKeyword !== ''): ?>
                    <input type="hidden" name="product_q" value="<?php echo htmlspecialchars($productKeyword); ?>">
                <?php endif; ?>
                <input class="search-inline" type="text" name="receipt_q" value="<?php echo htmlspecialchars($receiptKeyword); ?>" placeholder="Tìm mã phiếu hoặc ngày nhập...">
                <button class="btn btn-ghost btn-sm" type="submit">Tìm phiếu</button>
                <a class="btn btn-ghost btn-sm" href="index.php?page=receipts<?php echo $productKeyword !== '' ? '&product_q=' . urlencode($productKeyword) : ''; ?>">Đặt lại</a>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Mã phiếu</th>
                    <th>Ngày nhập</th>
                    <th>Lần nhập</th>
                    <th>Tổng SL</th>
                    <th>Tổng tiền nhập</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($receipts as $receipt): ?>
                    <tr>
                        <td class="td-gold"><?php echo htmlspecialchars($receipt['receipt_code']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($receipt['import_date'])); ?></td>
                        <td>Lần <?php echo (int)$receipt['import_round']; ?></td>
                        <td><?php echo (int)$receipt['total_qty']; ?></td>
                        <td class="td-gold"><?php echo number_format((int)$receipt['total_amount'], 0, ',', '.'); ?>₫</td>
                        <td>
                            <span class="badge <?php echo $receipt['status'] === 'completed' ? 'badge-success' : 'badge-muted'; ?>">
                                <?php echo $receipt['status'] === 'completed' ? 'Đã hoàn thành' : 'Nhập'; ?>
                            </span>
                        </td>
                        <td>
                            <div style="display:flex;gap:0.4rem;">
                                <a class="btn btn-ghost btn-sm" href="index.php?page=receipts&edit_receipt=<?php echo (int)$receipt['id']; ?><?php echo $receiptKeyword !== '' ? '&receipt_q=' . urlencode($receiptKeyword) : ''; ?><?php echo $productKeyword !== '' ? '&product_q=' . urlencode($productKeyword) : ''; ?>">
                                    <?php echo $receipt['status'] === 'draft' ? 'Sửa' : 'Xem'; ?>
                                </a>
                                <?php if ($receipt['status'] === 'draft'): ?>
                                    <form method="post" style="display:inline;" onsubmit="return confirm('Hoàn thành phiếu nhập này? Sau khi hoàn thành sẽ không thể sửa.');">
                                        <input type="hidden" name="receipt_action" value="complete_receipt">
                                        <input type="hidden" name="receipt_id" value="<?php echo (int)$receipt['id']; ?>">
                                        <button class="btn btn-gold btn-sm" type="submit">Hoàn thành</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>


