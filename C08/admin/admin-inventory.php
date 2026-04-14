<?php
require_once __DIR__ . '/../setup_db.php';
require_once __DIR__ . '/inventory-report-utils.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    ?>
    <section class="admin-page<?php echo ($page === 'inventory') ? ' active' : ''; ?>" id="page-inventory">
        <div style="background: rgba(255, 0, 0, 0.1); border: 1px solid red; color: red; padding: 1rem; margin-bottom: 1rem; border-radius: 0.5rem;">
            Không thể kết nối cơ sở dữ liệu để tải tồn kho.
        </div>
    </section>
    <?php
    return;
}

ensureInventoryReportSchema($pdo);

$todayDate = date('Y-m-d');
$asOfDate = normalizeFilterDateInput((string)($_GET['as_of_date'] ?? ''), $todayDate, $todayDate);
$categoryFilter = trim($_GET['category'] ?? '');
$lowThreshold = max(1, (int)($_GET['low_threshold'] ?? 5));

$categories = getInventoryReportCategories($pdo);

$rows = buildInventoryReportRows($pdo, '0000-01-01', $asOfDate, $categoryFilter);

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
                <input class="search-inline" type="date" id="inventory-as-of-date" name="as_of_date" max="<?php echo htmlspecialchars($todayDate); ?>" value="<?php echo htmlspecialchars($asOfDate); ?>" style="width:100%;">
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
            <h3>Tồn kho tại thời điểm người dùng chọn</h3>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Mã SP</th>
                    <th>Tên sản phẩm</th>
                    <th>Loại</th>
                    <th>Tồn kho</th>
                    <th>Cảnh báo</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="5" class="td-muted">Không có dữ liệu phù hợp.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <?php $stock = (int)$row['stock_qty']; ?>
                        <tr>
                            <td class="td-muted"><?php echo htmlspecialchars($row['product_code']); ?></td>
                            <td class="td-name"><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><span class="badge badge-muted"><?php echo htmlspecialchars($row['category']); ?></span></td>
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

<script>
    (function () {
        const input = document.getElementById('inventory-as-of-date');
        const today = '<?php echo htmlspecialchars($todayDate, ENT_QUOTES); ?>';
        if (!input) return;

        const validate = () => {
            const isFuture = !!input.value && input.value > today;
            if (isFuture) {
                input.style.borderColor = '#e05050';
                input.style.background = 'rgba(224, 80, 80, 0.08)';
                input.setCustomValidity('Không được chọn thời điểm trong tương lai.');
            } else {
                input.style.borderColor = '';
                input.style.background = '';
                input.setCustomValidity('');
            }
        };

        input.addEventListener('input', validate);
        input.addEventListener('change', validate);
        validate();
    })();
</script>


