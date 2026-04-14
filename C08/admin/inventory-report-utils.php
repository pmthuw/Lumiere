<?php

function ensureInventoryReportSchema(PDO $pdo): void
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

function getInventoryReportCategories(PDO $pdo): array
{
    return $pdo->query("SELECT name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
}

function normalizeInventoryDate(string $date, string $fallback): string
{
    $value = trim($date);
    return $value !== '' ? $value : $fallback;
}

function normalizeFilterDateInput(string $rawValue, string $fallback, string $todayDate): string
{
    $value = trim($rawValue);
    if ($value === '') {
        return $fallback;
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
        return $fallback;
    }

    if ($normalized > $todayDate) {
        return $todayDate;
    }

    return $normalized;
}

function buildInventoryReportRows(PDO $pdo, string $fromDate, string $toDate, string $categoryFilter): array
{
    $whereCategory = '';
    $params = [$fromDate, $toDate, $fromDate, $toDate, $toDate];

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
        GREATEST(0, COALESCE(p.initial_stock, 0) - COALESCE(exp_upto.total_export_upto, 0)) AS net_qty,
        GREATEST(0, COALESCE(p.initial_stock, 0) - COALESCE(exp_upto.total_export_upto, 0)) AS stock_qty
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
        WHERE o.status IN ('processing', 'shipped', 'delivered')
          AND DATE(o.created_at) >= ?
          AND DATE(o.created_at) <= ?
        GROUP BY oi.product_id
    ) exp ON exp.product_id = p.id
        LEFT JOIN (
                SELECT oi.product_id, SUM(oi.quantity) AS total_export_upto
                FROM order_items oi
                INNER JOIN orders o ON o.id = oi.order_id
                WHERE o.status IN ('processing', 'shipped', 'delivered')
                    AND DATE(o.created_at) <= ?
                GROUP BY oi.product_id
        ) exp_upto ON exp_upto.product_id = p.id
    {$whereCategory}
    ORDER BY p.name ASC";

    $reportStmt = $pdo->prepare($reportSql);
    $reportStmt->execute($params);
    return $reportStmt->fetchAll(PDO::FETCH_ASSOC);
}

function buildInventoryWarningRows(PDO $pdo, string $categoryFilter, int $lowThreshold): array
{
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
        GREATEST(0, COALESCE(p.initial_stock, 0) + COALESCE(imp.total_import, 0) - COALESCE(exp.total_export, 0)) AS current_stock,
        GREATEST(0, COALESCE(p.initial_stock, 0) + COALESCE(imp.total_import, 0) - COALESCE(exp.total_export, 0)) AS stock_qty
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
        WHERE o.status IN ('processing', 'shipped', 'delivered')
        GROUP BY oi.product_id
    ) exp ON exp.product_id = p.id
    {$warningWhere}
    HAVING current_stock <= ?
    ORDER BY current_stock ASC, p.name ASC";

    $warningParams[] = $lowThreshold;
    $warningStmt = $pdo->prepare($warningSql);
    $warningStmt->execute($warningParams);
    return $warningStmt->fetchAll(PDO::FETCH_ASSOC);
}
