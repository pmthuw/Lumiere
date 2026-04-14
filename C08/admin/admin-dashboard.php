        <?php
        require_once __DIR__ . '/../setup_db.php';

        $stats = [
            'revenue' => 0,
            'orders' => 0,
            'pending' => 0,
            'products' => 0,
            'low_stock' => 0,
        ];
        $monthlyRevenue = array_fill(1, 12, 0.0);
        $recentOrders = [];
        $topProducts = [];
        $lowStockItems = [];
        $lowStockThreshold = 5;

        if (isset($pdo) && $pdo instanceof PDO) {
            try {
                $stats['products'] = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
                $stats['orders'] = (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();

                $pendingStmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending', 'Chưa xử lý')");
                $stats['pending'] = (int)$pendingStmt->fetchColumn();

                $revenueStmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status IN ('delivered', 'Đã giao')");
                $stats['revenue'] = (int)$revenueStmt->fetchColumn();

                $year = (int)date('Y');
                $monthlyStmt = $pdo->prepare(
                    "SELECT MONTH(created_at) AS m, COALESCE(SUM(total_amount), 0) AS total
                     FROM orders
                     WHERE YEAR(created_at) = :y AND status IN ('delivered', 'Đã giao')
                     GROUP BY MONTH(created_at)"
                );
                $monthlyStmt->execute([':y' => $year]);
                foreach ($monthlyStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $m = (int)($row['m'] ?? 0);
                    if ($m >= 1 && $m <= 12) {
                        $monthlyRevenue[$m] = (float)($row['total'] ?? 0);
                    }
                }

                $recentStmt = $pdo->query(
                    "SELECT o.order_number, o.customer_name, o.total_amount, o.status,
                            COALESCE(
                              (SELECT GROUP_CONCAT(oi.product_name SEPARATOR ', ')
                               FROM order_items oi
                               WHERE oi.order_id = o.id),
                              '—'
                            ) AS products
                     FROM orders o
                     ORDER BY o.created_at DESC
                     LIMIT 6"
                );
                $recentOrders = $recentStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

                $topStmt = $pdo->query(
                    "SELECT oi.product_name, SUM(oi.quantity) AS qty
                     FROM order_items oi
                     INNER JOIN orders o ON o.id = oi.order_id
                     WHERE o.status IN ('delivered', 'Đã giao')
                     GROUP BY oi.product_name
                     ORDER BY qty DESC
                     LIMIT 6"
                );
                $topProducts = $topStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

                $lowStmt = $pdo->prepare(
                    "SELECT p.name,
                            GREATEST(
                              p.initial_stock - COALESCE(SUM(CASE WHEN o.status IN ('delivered', 'Đã giao') THEN oi.quantity ELSE 0 END), 0),
                              0
                            ) AS stock_left
                     FROM products p
                     LEFT JOIN order_items oi ON oi.product_id = p.id
                     LEFT JOIN orders o ON o.id = oi.order_id
                     GROUP BY p.id, p.name, p.initial_stock
                     HAVING stock_left <= :threshold
                     ORDER BY stock_left ASC, p.name ASC
                     LIMIT 8"
                );
                $lowStmt->execute([':threshold' => $lowStockThreshold]);
                $lowStockItems = $lowStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                $stats['low_stock'] = count($lowStockItems);
            } catch (Throwable $e) {
                // Keep dashboard usable even if a query fails.
            }
        }

        $fmtMoney = static function (int $v): string {
            return number_format($v, 0, ',', '.') . ' đ';
        };

        $orderBadge = static function (string $status): string {
            $s = mb_strtolower(trim($status), 'UTF-8');
            if ($s === 'delivered' || $s === 'đã giao') {
                return '<span class="badge badge-success">Đã giao</span>';
            }
            if ($s === 'pending' || $s === 'chưa xử lý') {
                return '<span class="badge badge-warning">Chưa xử lý</span>';
            }
            if ($s === 'cancelled' || $s === 'đã hủy') {
                return '<span class="badge badge-danger">Đã hủy</span>';
            }
            return '<span class="badge badge-muted">' . htmlspecialchars($status) . '</span>';
        };

        $months = ['T1','T2','T3','T4','T5','T6','T7','T8','T9','T10','T11','T12'];
        $maxMonthly = max($monthlyRevenue) ?: 1;
        ?>

        <section class="admin-page<?php echo ($page === 'dashboard') ? ' active' : ''; ?>" id="page-dashboard">
          <div class="page-header">
            <div class="page-header-left">
              <span class="eyebrow">Tổng quan hệ thống</span>
              <h1>Dashboard</h1>
            </div>
            <a class="btn btn-gold btn-sm" href="index.php?page=dashboard">Làm mới</a>
          </div>

          <div class="stats-grid">
            <div class="stat-card"><div class="stat-card-icon">◈</div><div class="stat-card-label">Doanh thu</div><div class="stat-card-value"><?php echo htmlspecialchars($fmtMoney($stats['revenue'])); ?></div><div class="stat-card-sub">Đơn đã giao</div></div>
            <div class="stat-card"><div class="stat-card-icon">◫</div><div class="stat-card-label">Đơn hàng</div><div class="stat-card-value"><?php echo (int)$stats['orders']; ?></div><div class="stat-card-sub"><?php echo (int)$stats['pending']; ?> chưa xử lý</div></div>
            <div class="stat-card"><div class="stat-card-icon">◻</div><div class="stat-card-label">Sản phẩm</div><div class="stat-card-value"><?php echo (int)$stats['products']; ?></div><div class="stat-card-sub">Trong hệ thống</div></div>
            <div class="stat-card"><div class="stat-card-icon">⚠</div><div class="stat-card-label">Sắp hết hàng</div><div class="stat-card-value"><?php echo (int)$stats['low_stock']; ?></div><div class="stat-card-sub">Ngưỡng ≤ <?php echo (int)$lowStockThreshold; ?></div></div>
          </div>

          <div class="chart-wrap" style="margin-bottom: 1.5rem">
            <div class="chart-title">Doanh thu theo tháng <span><?php echo (int)date('Y'); ?></span></div>
            <div class="bar-chart">
              <?php foreach ($months as $idx => $label): ?>
                <?php $monthIdx = $idx + 1; $value = (float)$monthlyRevenue[$monthIdx]; $h = ($value / $maxMonthly) * 100; ?>
                <div class="bar-item">
                  <div class="bar" style="height:<?php echo (float)$h; ?>%" title="<?php echo htmlspecialchars($fmtMoney((int)$value)); ?>"></div>
                  <span class="bar-label"><?php echo htmlspecialchars($label); ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="dashboard-grid">
            <div class="recent-orders-wrap">
              <div class="inner-table-header">Đơn hàng gần đây</div>
              <table>
                <thead>
                  <tr>
                    <th>Mã đơn</th>
                    <th>Khách hàng</th>
                    <th>Sản phẩm</th>
                    <th>Tổng</th>
                    <th>Trạng thái</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($recentOrders)): ?>
                    <tr><td colspan="5" class="td-muted" style="text-align:center">Chưa có đơn hàng.</td></tr>
                  <?php else: ?>
                    <?php foreach ($recentOrders as $o): ?>
                      <tr>
                        <td class="td-gold"><?php echo htmlspecialchars((string)($o['order_number'] ?? '—')); ?></td>
                        <td class="td-name"><?php echo htmlspecialchars((string)($o['customer_name'] ?? '—')); ?></td>
                        <td class="td-muted" style="max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php echo htmlspecialchars((string)($o['products'] ?? '—')); ?></td>
                        <td class="td-gold"><?php echo htmlspecialchars($fmtMoney((int)($o['total_amount'] ?? 0))); ?></td>
                        <td><?php echo $orderBadge((string)($o['status'] ?? '')); ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>

            <div>
              <div class="top-products-wrap" style="margin-bottom: 1.5rem">
                <div class="inner-table-header">Bán chạy nhất</div>
                <table>
                  <thead>
                    <tr>
                      <th>Sản phẩm</th>
                      <th>SL</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($topProducts)): ?>
                      <tr><td colspan="2" class="td-muted" style="text-align:center">Chưa có dữ liệu.</td></tr>
                    <?php else: ?>
                      <?php foreach ($topProducts as $p): ?>
                        <tr>
                          <td class="td-name" style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php echo htmlspecialchars((string)($p['product_name'] ?? '—')); ?></td>
                          <td><span class="badge badge-gold"><?php echo (int)($p['qty'] ?? 0); ?></span></td>
                        </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>

              <div class="recent-orders-wrap">
                <div class="inner-table-header" style="display:flex;align-items:center;justify-content:space-between;">
                  <span>Sắp hết hàng</span>
                  <a class="btn btn-ghost btn-sm" href="index.php?page=inventory">Xem tất cả</a>
                </div>
                <div>
                  <?php if (empty($lowStockItems)): ?>
                    <p style="color:var(--muted);padding:1rem;font-size:0.85rem">Không có sản phẩm sắp hết.</p>
                  <?php else: ?>
                    <?php foreach ($lowStockItems as $item): ?>
                      <div style="display:flex;justify-content:space-between;align-items:center;padding:0.75rem 1.5rem;border-bottom:1px solid var(--border)">
                        <span class="td-name"><?php echo htmlspecialchars((string)($item['name'] ?? '—')); ?></span>
                        <span class="badge badge-danger">⚠ Còn <?php echo (int)($item['stock_left'] ?? 0); ?></span>
                      </div>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        </section>

        <script>
          window.ADMIN_DASHBOARD_SERVER_RENDERED = true;
        </script>
