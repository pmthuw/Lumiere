<?php
session_start();
if (empty($_SESSION['admin_logged_in'])) {
    echo "Chua dang nh?p";
    exit;
}

echo "<h2>Test setup_db.php</h2>";
try {
    require_once __DIR__ . '/setup_db.php';
    echo "<p style='color: green;'>? setup_db.php d� load th�nh c�ng</p>";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM categories");
    $count = $stmt->fetchColumn();
    echo "<p>S? lo?i: $count</p>";
    
    $categories = $pdo->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($categories);
    echo "</pre>";
} catch (Exception $e) {
    echo "<p style='color: red;'>? L?i: " . $e->getMessage() . "</p>";
}
?>