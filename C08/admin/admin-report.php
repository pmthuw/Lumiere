<?php
require_once __DIR__ . '/../setup_db.php';
require_once __DIR__ . '/inventory-report-utils.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    ?>
    <section class="admin-page<?php echo ($page === 'report') ? ' active' : ''; ?>" id="page-report">
        <div style="background: rgba(255, 0, 0, 0.1); border: 1px solid red; color: red; padding: 1rem; margin-bottom: 1rem; border-radius: 0.5rem;">
            Không thể kết nối cơ sở dữ liệu để tải báo cáo.
        </div>
    </section>
    <?php
    return;
}

ensureInventoryReportSchema($pdo);

$todayDate = date('Y-m-d');
$defaultFromDate = date('Y-m-01');

$fromDate = normalizeFilterDateInput((string)($_GET['from_date'] ?? ''), $defaultFromDate, $todayDate);
$toDate = normalizeFilterDateInput((string)($_GET['to_date'] ?? ''), $todayDate, $todayDate);
$categoryFilter = trim($_GET['category'] ?? '');
$lowThreshold = max(1, (int)($_GET['low_threshold'] ?? 5));

if ($fromDate > $toDate) {
    $fromDate = $toDate;
}


$categories = getInventoryReportCategories($pdo);
$reportRows = buildInventoryReportRows($pdo, $fromDate, $toDate, $categoryFilter);

$summaryImport = 0;
$summaryExport = 0;
$summaryStock = 0;
foreach ($reportRows as $row) {
    $summaryImport += (int)$row['total_import'];
    $summaryExport += (int)$row['total_export'];
    $summaryStock += (int)$row['net_qty'];
}

$warningRows = buildInventoryWarningRows($pdo, $categoryFilter, $lowThreshold);
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
                <input class="search-inline" type="date" id="report-from-date" name="from_date" max="<?php echo htmlspecialchars($todayDate); ?>" value="<?php echo htmlspecialchars($fromDate); ?>" style="width:100%;">
            </div>
            <div>
                <span class="filter-label">Đến ngày</span>
                <input class="search-inline" type="date" id="report-to-date" name="to_date" max="<?php echo htmlspecialchars($todayDate); ?>" value="<?php echo htmlspecialchars($toDate); ?>" style="width:100%;">
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

<script>
    (function () {
        const today = '<?php echo htmlspecialchars($todayDate, ENT_QUOTES); ?>';
        const fromInput = document.getElementById('report-from-date');
        const toInput = document.getElementById('report-to-date');

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


