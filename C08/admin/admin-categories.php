<?php
require_once __DIR__ . '/../setup_db.php';

try {
    $hasStatus = (bool)$pdo->query("SHOW COLUMNS FROM categories LIKE 'status'")->fetch(PDO::FETCH_ASSOC);
    if (!$hasStatus) {
        $pdo->exec("ALTER TABLE categories ADD COLUMN status ENUM('active','hidden') NOT NULL DEFAULT 'active' AFTER description");
    }
} catch (Throwable $e) {
    // Keep page usable even if schema migration fails.
}

function productsTableExists(PDO $pdo): bool
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products'");
    $stmt->execute();
    return (int)$stmt->fetchColumn() > 0;
}

$successMessage = '';
$errorMessage = '';
$editingCategory = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_category') {
        $name = trim($_POST['category_name'] ?? '');
        $description = trim($_POST['category_description'] ?? '');

        if ($name === '') {
            $errorMessage = 'Tên danh mục không được để trống.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?) ON DUPLICATE KEY UPDATE description = VALUES(description)");
                $stmt->execute([$name, $description !== '' ? $description : null]);
                $successMessage = 'Đã thêm/cập nhật danh mục thành công.';
            } catch (Throwable $e) {
                $errorMessage = 'Không thể thêm danh mục: ' . $e->getMessage();
            }
        }
    }

    if ($action === 'update_category') {
        $id = (int)($_POST['category_id'] ?? 0);
        $name = trim($_POST['category_name'] ?? '');
        $description = trim($_POST['category_description'] ?? '');

        if ($id <= 0 || $name === '') {
            $errorMessage = 'Dữ liệu cập nhật danh mục không hợp lệ.';
        } else {
            try {
                $oldStmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
                $oldStmt->execute([$id]);
                $old = $oldStmt->fetch(PDO::FETCH_ASSOC);

                $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
                $stmt->execute([$name, $description !== '' ? $description : null, $id]);

                if ($old && $old['name'] !== $name && productsTableExists($pdo)) {
                    $syncStmt = $pdo->prepare("UPDATE products SET category = ? WHERE category = ?");
                    $syncStmt->execute([$name, $old['name']]);
                }

                $successMessage = 'Đã cập nhật danh mục thành công.';
            } catch (Throwable $e) {
                $errorMessage = 'Không thể cập nhật danh mục: ' . $e->getMessage();
            }
        }
    }

    if ($action === 'delete_category') {
        $id = (int)($_POST['category_id'] ?? 0);

        if ($id <= 0) {
            $errorMessage = 'ID danh mục không hợp lệ.';
        } else {
            try {
                $nameStmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
                $nameStmt->execute([$id]);
                $category = $nameStmt->fetch(PDO::FETCH_ASSOC);

                if (!$category) {
                    $errorMessage = 'Danh mục không tồn tại.';
                } else {
                    $productCount = 0;
                    if (productsTableExists($pdo)) {
                        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category = ?");
                        $countStmt->execute([$category['name']]);
                        $productCount = (int)$countStmt->fetchColumn();
                    }

                    if ($productCount > 0) {
                        $errorMessage = 'Không thể xóa danh mục và vẫn còn sản phẩm thuộc danh mục này.';
                    } else {
                        $deleteStmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                        $deleteStmt->execute([$id]);
                        $successMessage = 'Đã xóa danh mục thành công.';
                    }
                }
            } catch (Throwable $e) {
                $errorMessage = 'Không thể xóa danh mục: ' . $e->getMessage();
            }
        }
    }

    if ($action === 'toggle_category_status') {
        $id = (int)($_POST['category_id'] ?? 0);
        if ($id <= 0) {
            $errorMessage = 'ID danh mục không hợp lệ.';
        } else {
            try {
                $toggleStmt = $pdo->prepare("UPDATE categories SET status = CASE WHEN status = 'active' THEN 'hidden' ELSE 'active' END WHERE id = ?");
                $toggleStmt->execute([$id]);
                $successMessage = 'Đã cập nhật trạng thái hiển thị danh mục.';
            } catch (Throwable $e) {
                $errorMessage = 'Không thể cập nhật trạng thái danh mục: ' . $e->getMessage();
            }
        }
    }
}

$editCategoryId = (int)($_GET['edit_category'] ?? 0);
if ($editCategoryId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$editCategoryId]);
    $editingCategory = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$categoryCounts = [];
$hasProductsTable = productsTableExists($pdo);

foreach ($categories as $cat) {
    if ($hasProductsTable) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category = ?");
        $stmt->execute([$cat['name']]);
        $categoryCounts[$cat['name']] = (int)$stmt->fetchColumn();
    } else {
        $categoryCounts[$cat['name']] = 0;
    }
}
?>

<section class="admin-page<?php echo ($page === 'categories') ? ' active' : ''; ?>" id="page-categories">
    <div class="page-header">
        <div class="page-header-left">
            <span class="eyebrow">✦ Danh mục</span>
            <h1>Loại sản phẩm</h1>
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

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; align-items: start;">
        <div class="table-wrap">
            <div class="table-toolbar">
                <h3>Danh sách loại</h3>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Tên loại</th>
                        <th>Mô tả</th>
                        <th>Hiển thị</th>
                        <th>Số sản phẩm</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="5" class="td-muted" style="text-align:center;">Chưa có loại sản phẩm nào.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($categories as $cat): ?>
                            <?php $isHidden = (($cat['status'] ?? 'active') === 'hidden'); ?>
                            <tr>
                                <td class="td-name"><?php echo htmlspecialchars($cat['name']); ?></td>
                                <td class="td-muted"><?php echo htmlspecialchars($cat['description'] ?? '-'); ?></td>
                                <td>
                                    <span class="badge <?php echo $isHidden ? 'badge-danger' : 'badge-success'; ?>">
                                        <?php echo $isHidden ? 'Đang ẩn' : 'Đang hiện'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-muted"><?php echo $categoryCounts[$cat['name']] ?? 0; ?> SP</span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                        <a class="btn btn-ghost btn-sm" href="index.php?page=categories&edit_category=<?php echo (int)$cat['id']; ?>">Sửa</a>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="action" value="toggle_category_status">
                                            <input type="hidden" name="category_id" value="<?php echo (int)$cat['id']; ?>">
                                            <button type="submit" class="btn btn-sm <?php echo $isHidden ? 'btn-ghost' : 'btn-danger'; ?>">
                                                <?php echo $isHidden ? 'Mở' : 'Ẩn'; ?>
                                            </button>
                                        </form>
                                        <form method="post" style="display: inline;" onsubmit="return confirm('Bạn có chắc muốn xóa danh mục này?')">
                                            <input type="hidden" name="action" value="delete_category">
                                            <input type="hidden" name="category_id" value="<?php echo (int)$cat['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm btn-icon">Xóa</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="table-wrap" style="padding: 1.75rem;">
            <h3 style="font-family: 'Playfair Display', serif; font-size: 1.1rem; margin-bottom: 1.5rem;">
                <?php echo $editingCategory ? 'Chỉnh sửa loại' : 'Thêm loại mới'; ?>
            </h3>
            <form method="post">
                <input type="hidden" name="action" value="<?php echo $editingCategory ? 'update_category' : 'add_category'; ?>">
                <?php if ($editingCategory): ?>
                    <input type="hidden" name="category_id" value="<?php echo (int)$editingCategory['id']; ?>">
                <?php endif; ?>

                <div class="form-row">
                    <div class="form-group">
                        <label>Tên loại sản phẩm</label>
                        <input class="form-control" name="category_name" type="text" value="<?php echo htmlspecialchars($editingCategory['name'] ?? ''); ?>" placeholder="VD: Cao cấp, Nhập khẩu..." required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Mô tả dữ liệu loại sản phẩm</label>
                        <input class="form-control" name="category_description" type="text" value="<?php echo htmlspecialchars($editingCategory['description'] ?? ''); ?>" placeholder="Mô tả ngắn về loại sản phẩm...">
                    </div>
                </div>

                <button type="submit" class="btn btn-gold"><?php echo $editingCategory ? 'Cập nhật' : '+ Thêm loại'; ?></button>
                <?php if ($editingCategory): ?>
                    <a href="index.php?page=categories" class="btn btn-ghost">Hủy</a>
                <?php endif; ?>
            </form>
        </div>
    </div>
</section>


