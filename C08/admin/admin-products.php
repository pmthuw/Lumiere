<?php
require_once __DIR__ . '/../setup_db.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    ?>
    <section class="admin-page<?php echo ($page === 'products') ? ' active' : ''; ?>" id="page-products">
        <div style="background: rgba(255, 0, 0, 0.1); border: 1px solid red; color: red; padding: 1rem; margin-bottom: 1rem; border-radius: 0.5rem;">
            Không thể kết nối cơ sở dữ liệu để tải trang sản phẩm.
        </div>
    </section>
    <?php
    return;
}

function ensureProductsBackendSchema(PDO $pdo): void
{
    $requiredColumns = [
        'product_code' => "ALTER TABLE products ADD COLUMN product_code VARCHAR(30) NULL UNIQUE AFTER id",
        'description' => "ALTER TABLE products ADD COLUMN description TEXT NULL AFTER category",
        'unit' => "ALTER TABLE products ADD COLUMN unit VARCHAR(60) NULL AFTER description",
        'initial_stock' => "ALTER TABLE products ADD COLUMN initial_stock INT UNSIGNED NOT NULL DEFAULT 0 AFTER unit",
        'avg_import_price' => "ALTER TABLE products ADD COLUMN avg_import_price INT UNSIGNED NOT NULL DEFAULT 0 AFTER initial_stock",
        'profit_rate' => "ALTER TABLE products ADD COLUMN profit_rate DECIMAL(5,2) NOT NULL DEFAULT 30.00 AFTER initial_stock",
        'supplier' => "ALTER TABLE products ADD COLUMN supplier VARCHAR(255) NULL AFTER profit_rate",
        'status' => "ALTER TABLE products ADD COLUMN status ENUM('active','hidden') NOT NULL DEFAULT 'active' AFTER supplier"
    ];

    $checkStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'products'
          AND COLUMN_NAME = ?
    ");

    foreach ($requiredColumns as $column => $sql) {
        $checkStmt->execute([$column]);
        if ((int)$checkStmt->fetchColumn() === 0) {
            $pdo->exec($sql);
        }
    }

    $pdo->exec("
        UPDATE products
        SET product_code = CONCAT('SP', LPAD(id, 3, '0'))
        WHERE product_code IS NULL OR product_code = ''
    ");
}

function nextProductCode(PDO $pdo): string
{
    $stmt = $pdo->query("
        SELECT MAX(CAST(SUBSTRING(product_code, 3) AS UNSIGNED)) AS max_code_num
        FROM products
        WHERE product_code REGEXP '^SP[0-9]+$'
    ");
    $maxCodeNum = (int)($stmt->fetchColumn() ?: 0);
    return 'SP' . str_pad((string)($maxCodeNum + 1), 3, '0', STR_PAD_LEFT);
}

function adminImageUrl(?string $image): string
{
    $image = trim((string)$image);
    if ($image === '') {
        return '';
    }

    if (preg_match('/^(https?:)?\\/\\//i', $image) || str_starts_with($image, 'data:') || str_starts_with($image, '../') || str_starts_with($image, '/')) {
        return $image;
    }

    if (str_starts_with($image, 'images/')) {
        return '../frontend/' . $image;
    }

    if (str_starts_with($image, 'frontend/images/')) {
        return '../' . $image;
    }

    return $image;
}

function tableExists(PDO $pdo, string $tableName): bool
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
    $stmt->execute([$tableName]);
    return (int)$stmt->fetchColumn() > 0;
}

function hasImportedProduct(PDO $pdo, int $productId): bool
{
    if (!tableExists($pdo, 'receipts') || !tableExists($pdo, 'receipt_items')) {
        return false;
    }

    $stmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM receipt_items ri
         INNER JOIN receipts r ON r.id = ri.receipt_id
         WHERE ri.product_id = ? AND r.status = 'completed'"
    );
    $stmt->execute([$productId]);

    return (int)$stmt->fetchColumn() > 0;
}

function saveUploadedProductImage(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }

    $tmpPath = (string)($file['tmp_name'] ?? '');
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        return null;
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    $mime = mime_content_type($tmpPath) ?: '';
    if (!isset($allowed[$mime])) {
        return null;
    }

    $uploadDir = realpath(__DIR__ . '/../frontend/images');
    if ($uploadDir === false) {
        return null;
    }

    $targetDir = $uploadDir . DIRECTORY_SEPARATOR . 'uploads';
    if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
        return null;
    }

    $filename = 'prod_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
    $targetPath = $targetDir . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($tmpPath, $targetPath)) {
        return null;
    }

    return 'images/uploads/' . $filename;
}

ensureProductsBackendSchema($pdo);

$productSuccess = '';
$productError = '';
$editingProduct = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = $_POST['product_action'] ?? '';

    if ($action === 'add') {
        $productCode = trim($_POST['product_code'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $unit = trim($_POST['unit'] ?? '');
        $initialStock = max(0, (int)($_POST['initial_stock'] ?? 0));
        $image = trim($_POST['image'] ?? '');
        $uploadedImagePath = saveUploadedProductImage($_FILES['image_file'] ?? []);
        $profitRate = 30.00;
        $avgImportPrice = 0.0;
        $supplier = trim($_POST['supplier'] ?? '');
        $status = (($_POST['status'] ?? 'active') === 'hidden') ? 'hidden' : 'active';
        $brand = trim($_POST['brand'] ?? '');
        $badge = null;
        $notes = trim($_POST['notes'] ?? '');
        $concentration = trim($_POST['concentration'] ?? '');
        $size = trim($_POST['size'] ?? '');

        if ($name === '' || $category === '' || $unit === '') {
            $productError = 'Vui lòng nhập đầy đủ các trường bắt buộc: tên, loại, đơn vị tính.';
        } else {
            try {
                if ($productCode === '') {
                    $productCode = nextProductCode($pdo);
                }

                $stmt = $pdo->prepare("
                    INSERT INTO products (
                        product_code, name, category, description, unit, initial_stock, image,
                        avg_import_price, profit_rate, supplier, status, brand, badge, notes, concentration, size
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $productCode,
                    $name,
                    $category,
                    $description,
                    $unit,
                    $initialStock,
                    $uploadedImagePath ?? ($image !== '' ? $image : null),
                    $avgImportPrice,
                    $profitRate,
                    $supplier !== '' ? $supplier : null,
                    $status,
                    $brand !== '' ? $brand : '-',
                    $badge !== '' ? $badge : null,
                    $notes !== '' ? $notes : null,
                    $concentration !== '' ? $concentration : null,
                    $size !== '' ? $size : null
                ]);
                $productSuccess = 'Đã thêm sản phẩm thành công.';
            } catch (Throwable $e) {
                $productError = 'Không thể thêm sản phẩm: ' . $e->getMessage();
            }
        }
    }

    if ($action === 'update') {
        $idRaw = $_POST['id'] ?? '';
        $id = (int)$idRaw;
        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $unit = trim($_POST['unit'] ?? '');
        $initialStock = max(0, (int)($_POST['initial_stock'] ?? 0));
        $image = trim($_POST['image'] ?? '');
        $uploadedImagePath = saveUploadedProductImage($_FILES['image_file'] ?? []);
        $removeImage = isset($_POST['remove_image']) && $_POST['remove_image'] === '1';
        $profitRate = (float)($_POST['profit_rate'] ?? 0);
        $supplier = trim($_POST['supplier'] ?? '');
        $status = (($_POST['status'] ?? 'active') === 'hidden') ? 'hidden' : 'active';
        $brand = trim($_POST['brand'] ?? '');
        $badge = trim($_POST['badge'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $concentration = trim($_POST['concentration'] ?? '');
        $size = trim($_POST['size'] ?? '');

        if ($idRaw === '' || $id < 0 || $name === '' || $category === '' || $unit === '') {
            $productError = 'Dữ liệu cập nhật không hợp lệ.';
        } else {
            try {
                $currentStmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
                $currentStmt->execute([$id]);
                $current = $currentStmt->fetch(PDO::FETCH_ASSOC);

                if (!$current) {
                    $productError = 'Sản phẩm không tồn tại.';
                } else {
                    $newImage = $current['image'];
                    if ($removeImage) {
                        $newImage = null;
                    } elseif ($uploadedImagePath !== null) {
                        $newImage = $uploadedImagePath;
                    } elseif ($image !== '') {
                        $newImage = $image;
                    }

                    $stmt = $pdo->prepare("\n                        UPDATE products\n                        SET name = ?, category = ?, description = ?, unit = ?, initial_stock = ?, image = ?,\n                            profit_rate = ?, supplier = ?, status = ?, brand = ?, badge = ?,\n                            notes = ?, concentration = ?, size = ?\n                        WHERE id = ?\n                    ");
                    $stmt->execute([
                        $name,
                        $category,
                        $description !== '' ? $description : null,
                        $unit,
                        $initialStock,
                        $newImage,
                        $profitRate,
                        $supplier !== '' ? $supplier : null,
                        $status,
                        $brand !== '' ? $brand : '-',
                        $badge !== '' ? $badge : null,
                        $notes !== '' ? $notes : null,
                        $concentration !== '' ? $concentration : null,
                        $size !== '' ? $size : null,
                        $id
                    ]);
                    $productSuccess = 'Đã cập nhật sản phẩm thành công.';
                }
            } catch (Throwable $e) {
                $productError = 'Không thể cập nhật sản phẩm: ' . $e->getMessage();
            }
        }
    }

    if ($action === 'toggle_status') {
        $id = (int)($_POST['id'] ?? 0);
        $idRaw = $_POST['id'] ?? '';
        $id = (int)$idRaw;
        if ($idRaw !== '' && $id >= 0) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE products
                    SET status = CASE WHEN status = 'active' THEN 'hidden' ELSE 'active' END
                    WHERE id = ?
                ");
                $stmt->execute([$id]);
                $productSuccess = 'Đã cập nhật hiện trạng sản phẩm.';
            } catch (Throwable $e) {
                $productError = 'Không thể cập nhật hiện trạng: ' . $e->getMessage();
            }
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $idRaw = $_POST['id'] ?? '';
        $id = (int)$idRaw;
        if ($idRaw !== '' && $id >= 0) {
            try {
                $stmt = $pdo->prepare("SELECT id, name, initial_stock FROM products WHERE id = ?");
                $stmt->execute([$id]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($product) {
                    $hasImported = hasImportedProduct($pdo, (int)$product['id']);

                    if ($hasImported) {
                        $hideStmt = $pdo->prepare("UPDATE products SET status = 'hidden' WHERE id = ?");
                        $hideStmt->execute([$id]);
                        $productSuccess = 'Sản phẩm đã từng nhập hàng, hệ thống chuyển sang trạng thái ẩn.';
                    } else {
                        $deleteStmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
                        $deleteStmt->execute([$id]);
                        $productSuccess = 'Đã xóa hẳn sản phẩm khỏi cơ sở dữ liệu.';
                    }
                } else {
                    $productError = 'Sản phẩm không tồn tại.';
                }
            } catch (Throwable $e) {
                $productError = 'Không thể xóa sản phẩm: ' . $e->getMessage();
            }
        }
    }
}

$editingProduct = null;

$categories = $pdo->query("SELECT id, name, description FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$productsSql = "
    SELECT
        p.*,
        GREATEST(
            0,
            COALESCE(p.initial_stock, 0)
            - COALESCE(exp.total_export, 0)
        ) AS stock_qty
    FROM products p
    LEFT JOIN (
        SELECT oi.product_id, SUM(oi.quantity) AS total_export
        FROM order_items oi
        INNER JOIN orders o ON o.id = oi.order_id
        WHERE o.status IN ('processing', 'shipping', 'shipped', 'delivered')
        GROUP BY oi.product_id
    ) exp ON exp.product_id = p.id
    ORDER BY p.id DESC
";
$products = $pdo->query($productsSql)->fetchAll(PDO::FETCH_ASSOC);
?>
<section class="admin-page<?php echo ($page === 'products') ? ' active' : ''; ?>" id="page-products">
    <div class="page-header">
        <div class="page-header-left">
            <span class="eyebrow">Quản lý kho hàng</span>
            <h1>Sản phẩm</h1>
        </div>
        <div class="page-header-right">
            <button type="button" class="btn btn-gold" id="open-add-product-btn" onclick="openProductAddModal()">+ Thêm sản phẩm</button>
        </div>
    </div>

    <?php if ($productSuccess !== ''): ?>
        <div style="background: rgba(0, 255, 0, 0.1); border: 1px solid green; color: green; padding: 1rem; margin-bottom: 1rem; border-radius: 0.5rem;">
            <?php echo htmlspecialchars($productSuccess); ?>
        </div>
    <?php endif; ?>

    <?php if ($productError !== ''): ?>
        <div style="background: rgba(255, 0, 0, 0.1); border: 1px solid red; color: red; padding: 1rem; margin-bottom: 1rem; border-radius: 0.5rem;">
            <?php echo htmlspecialchars($productError); ?>
        </div>
    <?php endif; ?>

    <div class="table-wrap">
        <div class="table-toolbar">
            <h3>Danh sách sản phẩm <span style="color: var(--muted); font-size: 0.8rem; font-weight: 400;">(<?php echo count($products); ?> SP)</span></h3>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Mã</th>
                    <th>Sản phẩm</th>
                    <th>Loại</th>
                    <th>Đơn vị</th>
                    <th>Số lượng</th>
                    <th>Nhà cung cấp</th>
                    <th>Hiện trạng</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr class="<?php echo (($product['status'] ?? 'active') === 'hidden') ? 'row-hidden' : ''; ?>">
                        <td class="td-muted"><?php echo htmlspecialchars($product['product_code'] ?? ('SP' . str_pad((string)$product['id'], 3, '0', STR_PAD_LEFT))); ?></td>
                        <td>
                            <div class="product-thumb-cell">
                                <div class="product-thumb">
                                    <?php if (!empty($product['image'])): ?>
                                        <img src="<?php echo htmlspecialchars(adminImageUrl($product['image'])); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" />
                                    <?php else: ?>
                                        ?
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="td-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                    <div class="td-muted"><?php echo htmlspecialchars(($product['size'] ?? '-') . ' - ' . ($product['brand'] ?? '-')); ?></div>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge badge-muted"><?php echo htmlspecialchars($product['category']); ?></span></td>
                        <td><?php echo htmlspecialchars($product['unit'] ?? '-'); ?></td>
                        <td><?php echo (int)($product['stock_qty'] ?? $product['initial_stock'] ?? 0); ?></td>
                        <td class="td-name"><?php echo htmlspecialchars($product['supplier'] ?? '-'); ?></td>
                        <td>
                            <span class="badge <?php echo (($product['status'] ?? 'active') === 'active') ? 'badge-success' : 'badge-danger'; ?>">
                                <?php echo (($product['status'] ?? 'active') === 'active') ? 'Hiển thị' : 'Ẩn'; ?>
                            </span>
                        </td>
                        <td>
                            <div style="display:flex; gap:0.4rem;">
                                <button
                                    type="button"
                                    class="btn btn-ghost btn-sm edit-product-btn"
                                    onclick="openProductEditModal(this)"
                                    data-id="<?php echo (int)$product['id']; ?>"
                                    data-code="<?php echo htmlspecialchars((string)($product['product_code'] ?? ''), ENT_QUOTES); ?>"
                                    data-name="<?php echo htmlspecialchars((string)($product['name'] ?? ''), ENT_QUOTES); ?>"
                                    data-category="<?php echo htmlspecialchars((string)($product['category'] ?? ''), ENT_QUOTES); ?>"
                                    data-unit="<?php echo htmlspecialchars((string)($product['unit'] ?? ''), ENT_QUOTES); ?>"
                                    data-initial-stock="<?php echo (int)($product['initial_stock'] ?? 0); ?>"
                                    data-profit-rate="<?php echo (float)($product['profit_rate'] ?? 0); ?>"
                                    data-supplier="<?php echo htmlspecialchars((string)($product['supplier'] ?? ''), ENT_QUOTES); ?>"
                                    data-brand="<?php echo htmlspecialchars((string)($product['brand'] ?? ''), ENT_QUOTES); ?>"
                                    data-status="<?php echo htmlspecialchars((string)($product['status'] ?? 'active'), ENT_QUOTES); ?>"
                                    data-concentration="<?php echo htmlspecialchars((string)($product['concentration'] ?? ''), ENT_QUOTES); ?>"
                                    data-size="<?php echo htmlspecialchars((string)($product['size'] ?? ''), ENT_QUOTES); ?>"
                                    data-badge="<?php echo htmlspecialchars((string)($product['badge'] ?? ''), ENT_QUOTES); ?>"
                                    data-image="<?php echo htmlspecialchars((string)($product['image'] ?? ''), ENT_QUOTES); ?>"
                                    data-notes="<?php echo htmlspecialchars((string)($product['notes'] ?? ''), ENT_QUOTES); ?>"
                                    data-description="<?php echo htmlspecialchars((string)($product['description'] ?? ''), ENT_QUOTES); ?>"
                                >Sửa</button>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="product_action" value="toggle_status">
                                    <input type="hidden" name="id" value="<?php echo (int)$product['id']; ?>">
                                    <button type="submit" class="btn btn-sm <?php echo (($product['status'] ?? 'active') === 'active') ? 'btn-danger' : 'btn-ghost'; ?>">
                                        <?php echo (($product['status'] ?? 'active') === 'active') ? 'Ẩn' : 'Hiện'; ?>
                                    </button>
                                </form>
                                <form method="post" style="display:inline;" onsubmit="return confirm('Xác nhận xóa sản phẩm này?');">
                                    <input type="hidden" name="product_action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo (int)$product['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm btn-icon">Xóa</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div id="product-add-modal" style="display:none; position:fixed; inset:0; background:rgba(6,8,16,0.7); z-index:1200; align-items:center; justify-content:center; padding:1rem;">
        <div class="table-wrap" style="padding:1.25rem; width:min(980px, 100%); max-height:90vh; overflow:auto;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.85rem;">
                <h3 style="font-family:'Playfair Display', serif; margin:0;">Thêm sản phẩm</h3>
                <button id="close-add-product" class="btn btn-ghost btn-sm" type="button" aria-label="Đóng" onclick="closeProductAddModal()">x</button>
            </div>

            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="product_action" value="add">
                <div class="form-row form-row-2">
                    <div class="form-group">
                        <label>Mã sản phẩm</label>
                        <input class="form-control" type="text" name="product_code" value="<?php echo htmlspecialchars(nextProductCode($pdo)); ?>" />
                    </div>
                    <div class="form-group">
                        <label>Tên sản phẩm *</label>
                        <input class="form-control" type="text" name="name" required />
                    </div>
                </div>
                <div class="form-row form-row-2">
                    <div class="form-group">
                        <label>Loại sản phẩm *</label>
                        <select class="form-control" name="category" required>
                            <option value="">-- Chọn loại --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['name']); ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Đơn vị tính *</label>
                        <input class="form-control" type="text" name="unit" placeholder="chai, hop, set..." required />
                    </div>
                </div>
                <div class="form-row form-row-2">
                    <div class="form-group">
                        <label>Số lượng ban đầu</label>
                        <input class="form-control" type="number" name="initial_stock" min="0" value="0" />
                    </div>
                    <div class="form-group">
                        <label>Nhà cung cấp</label>
                        <input class="form-control" type="text" name="supplier" />
                    </div>
                </div>
                <div class="form-row form-row-2">
                    <div class="form-group">
                        <label>Thương hiệu</label>
                        <input class="form-control" type="text" name="brand" />
                    </div>
                    <div class="form-group">
                        <label>Hiện trạng</label>
                        <select class="form-control" name="status">
                            <option value="active">Hiển thị (đang bán)</option>
                            <option value="hidden">Ẩn (không bán)</option>
                        </select>
                    </div>
                </div>
                <div class="form-row form-row-2">
                    <div class="form-group">
                        <label>Nồng độ</label>
                        <input class="form-control" type="text" name="concentration" />
                    </div>
                    <div class="form-group">
                        <label>Dung tích</label>
                        <input class="form-control" type="text" name="size" />
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Hình ảnh sản phẩm</label>
                        <input class="form-control" type="file" name="image_file" accept="image/*" />
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Ghi chú mùi hương</label>
                        <input class="form-control" type="text" name="notes" />
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Mô tả</label>
                        <textarea class="form-control" name="description"></textarea>
                    </div>
                </div>
                <div style="display:flex; gap:0.6rem;">
                    <button class="btn btn-gold" type="submit">+ Thêm sản phẩm</button>
                    <button class="btn btn-ghost" type="button" id="close-add-product-footer" onclick="closeProductAddModal()">Hủy</button>
                </div>
            </form>
        </div>
    </div>

    <div id="product-edit-modal" style="display:none; position:fixed; inset:0; background:rgba(6,8,16,0.7); z-index:1200; align-items:center; justify-content:center; padding:1rem;">
        <div class="table-wrap" style="padding:1.25rem; width:min(980px, 100%); max-height:90vh; overflow:auto;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.85rem;">
                <h3 style="font-family:'Playfair Display', serif; margin:0;">Sửa sản phẩm</h3>
                <button id="close-edit-product" class="btn btn-ghost btn-sm" type="button" aria-label="Đóng" onclick="closeProductEditModal()">x</button>
            </div>
            <div style="background: rgba(11, 107, 60, 0.12); border: 1px solid rgba(11, 107, 60, 0.45); color: #9be2bf; padding: 0.75rem 0.9rem; margin-bottom: 1rem; border-radius: 0.5rem;">
                Đang sửa: <strong id="editing-product-name"></strong> (<span id="editing-product-code"></span>)
            </div>

            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="product_action" value="update">
                <input id="edit-id" type="hidden" name="id" value="">
                <div class="form-row form-row-2">
                    <div class="form-group">
                        <label>Mã sản phẩm</label>
                        <input id="edit-product-code" class="form-control" type="text" value="" readonly />
                    </div>
                    <div class="form-group">
                        <label>Tên sản phẩm *</label>
                        <input id="edit-name" class="form-control" type="text" name="name" value="" required />
                    </div>
                </div>
                <div class="form-row form-row-2">
                    <div class="form-group">
                        <label>Loại sản phẩm *</label>
                        <select id="edit-category" class="form-control" name="category" required>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['name']); ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Đơn vị tính *</label>
                        <input id="edit-unit" class="form-control" type="text" name="unit" value="" required />
                    </div>
                </div>
                <div class="form-row form-row-2">
                    <div class="form-group">
                        <label>Số lượng ban đầu</label>
                        <input id="edit-initial-stock" class="form-control" type="number" name="initial_stock" min="0" value="0" />
                    </div>
                    <div class="form-group">
                        <label>Tỉ lệ lợi nhuận (%)</label>
                        <input id="edit-profit-rate" class="form-control" type="number" step="0.01" name="profit_rate" min="0" value="30" />
                    </div>
                </div>
                <div class="form-row form-row-2">
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Nhà cung cấp</label>
                        <input id="edit-supplier" class="form-control" type="text" name="supplier" value="" />
                    </div>
                </div>
                <div class="form-row form-row-2">
                    <div class="form-group">
                        <label>Thương hiệu</label>
                        <input id="edit-brand" class="form-control" type="text" name="brand" value="" />
                    </div>
                    <div class="form-group">
                        <label>Hiện trạng</label>
                        <select id="edit-status" class="form-control" name="status">
                            <option value="active">Hiển thị (đang bán)</option>
                            <option value="hidden">Ẩn (không bán)</option>
                        </select>
                    </div>
                </div>
                <div class="form-row form-row-2">
                    <div class="form-group">
                        <label>Nồng độ</label>
                        <input id="edit-concentration" class="form-control" type="text" name="concentration" value="" />
                    </div>
                    <div class="form-group">
                        <label>Dung tích</label>
                        <input id="edit-size" class="form-control" type="text" name="size" value="" />
                    </div>
                </div>
                <div class="form-row form-row-2">
                    <div class="form-group">
                        <label>Badge</label>
                        <input id="edit-badge" class="form-control" type="text" name="badge" value="" />
                    </div>
                    <div class="form-group">
                        <label>Đường dẫn hình ảnh</label>
                        <input id="edit-image" class="form-control" type="text" name="image" value="" />
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Hoặc chọn ảnh từ máy tính</label>
                        <input id="edit-image-file" class="form-control" type="file" name="image_file" accept="image/*" />
                    </div>
                </div>
                <div class="form-row" id="edit-image-wrap" style="display:none;">
                    <div class="form-group">
                        <img id="edit-image-preview" src="" alt="Ảnh hiện tại" style="width:80px;height:80px;object-fit:cover;border-radius:8px;margin-bottom:8px;" />
                        <label style="display:flex;align-items:center;gap:8px;">
                            <input id="edit-remove-image" type="checkbox" name="remove_image" value="1" />
                            Bỏ hình hiện tại
                        </label>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Ghi chú mùi hương</label>
                        <input id="edit-notes" class="form-control" type="text" name="notes" value="" />
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Mô tả</label>
                        <textarea id="edit-description" class="form-control" name="description"></textarea>
                    </div>
                </div>
                <div style="display:flex; gap:0.6rem;">
                    <button id="edit-submit-btn" class="btn btn-gold" type="submit">Cập nhật sản phẩm</button>
                    <button class="btn btn-ghost" type="button" id="close-edit-product-footer" onclick="closeProductEditModal()">Hủy</button>
                </div>
            </form>
        </div>
    </div>
</section>

<script>
    window.addEventListener('DOMContentLoaded', function () {
        var addModal = document.getElementById('product-add-modal');
        var openAddBtn = document.getElementById('open-add-product-btn');
        var closeAddBtn = document.getElementById('close-add-product');
        var closeAddFooterBtn = document.getElementById('close-add-product-footer');

        var editModal = document.getElementById('product-edit-modal');
        var editButtons = document.querySelectorAll('.edit-product-btn');
        var closeBtn = document.getElementById('close-edit-product');
        var closeFooterBtn = document.getElementById('close-edit-product-footer');
        var imageInput = document.getElementById('edit-image');
        var imageWrap = document.getElementById('edit-image-wrap');
        var imagePreview = document.getElementById('edit-image-preview');

        function syncBodyScrollLock() {
            var anyModalOpen = (addModal && addModal.style.display === 'flex') || (editModal && editModal.style.display === 'flex');
            document.body.style.overflow = anyModalOpen ? 'hidden' : '';
        }

        function setAddModalVisibility(show) {
            if (!addModal) return;
            addModal.style.display = show ? 'flex' : 'none';
            syncBodyScrollLock();
        }

        function setModalVisibility(show) {
            if (!editModal) return;
            editModal.style.display = show ? 'flex' : 'none';
            syncBodyScrollLock();
        }

        window.openProductAddModal = function () {
            setAddModalVisibility(true);
        };

        window.closeProductAddModal = function () {
            setAddModalVisibility(false);
        };

        function updateImagePreview(imagePath) {
            if (!imageWrap || !imagePreview) return;
            var value = (imagePath || '').trim();
            if (!value) {
                imageWrap.style.display = 'none';
                imagePreview.removeAttribute('src');
                return;
            }

            if (!/^((https?:)?\/\/|data:|\/|\.\.\/)/i.test(value)) {
                if (value.indexOf('images/') === 0) {
                    value = '../frontend/' + value;
                } else if (value.indexOf('frontend/images/') === 0) {
                    value = '../' + value;
                }
            }

            imagePreview.src = value;
            imageWrap.style.display = '';
        }

        window.openProductEditModal = function (btn) {
            var data = btn.dataset || {};
            document.getElementById('edit-id').value = data.id || '';
            document.getElementById('edit-product-code').value = data.code || '';
            document.getElementById('editing-product-code').textContent = data.code || '';
            document.getElementById('edit-name').value = data.name || '';
            document.getElementById('editing-product-name').textContent = data.name || '';
            document.getElementById('edit-category').value = data.category || '';
            document.getElementById('edit-unit').value = data.unit || '';
            document.getElementById('edit-initial-stock').value = data.initialStock || '0';
            document.getElementById('edit-profit-rate').value = data.profitRate || '0';
            document.getElementById('edit-supplier').value = data.supplier || '';
            document.getElementById('edit-brand').value = data.brand || '';
            document.getElementById('edit-status').value = data.status || 'active';
            document.getElementById('edit-concentration').value = data.concentration || '';
            document.getElementById('edit-size').value = data.size || '';
            document.getElementById('edit-badge').value = data.badge || '';
            document.getElementById('edit-image').value = data.image || '';
            document.getElementById('edit-notes').value = data.notes || '';
            document.getElementById('edit-description').value = data.description || '';
            document.getElementById('edit-remove-image').checked = false;

            updateImagePreview(data.image || '');
            setModalVisibility(true);
        };

        editButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                window.openProductEditModal(btn);
            });
        });

        window.closeProductEditModal = function () {
            setModalVisibility(false);
        };

        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                window.closeProductEditModal();
            });
        }

        if (openAddBtn) {
            openAddBtn.addEventListener('click', function () {
                window.openProductAddModal();
            });
        }

        if (closeAddBtn) {
            closeAddBtn.addEventListener('click', function () {
                window.closeProductAddModal();
            });
        }

        if (closeAddFooterBtn) {
            closeAddFooterBtn.addEventListener('click', function () {
                window.closeProductAddModal();
            });
        }

        if (closeFooterBtn) {
            closeFooterBtn.addEventListener('click', function () {
                window.closeProductEditModal();
            });
        }

        if (imageInput) {
            imageInput.addEventListener('input', function () {
                updateImagePreview(imageInput.value || '');
            });
        }

        if (editModal) {
            editModal.addEventListener('click', function (e) {
                if (e.target === editModal) {
                    window.closeProductEditModal();
                }
            });
        }

        if (addModal) {
            addModal.addEventListener('click', function (e) {
                if (e.target === addModal) {
                    window.closeProductAddModal();
                }
            });
        }

        setAddModalVisibility(false);
        setModalVisibility(false);
    });
</script>
