<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../setup_db.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
  http_response_code(500);
  echo json_encode(['error' => 'Database connection failed']);
  exit(1);
}

// Get user email from session or request
$userEmail = $_SESSION['user_email'] ?? ($_GET['user'] ?? null);
if (!$userEmail) {
  http_response_code(400);
  echo json_encode(['error' => 'User email required']);
  exit(1);
}

// Ensure cart table exists
try {
  $pdo->exec(<<<SQL
    CREATE TABLE IF NOT EXISTS shopping_cart (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_email VARCHAR(255) NOT NULL,
      product_id INT UNSIGNED NOT NULL,
      quantity INT NOT NULL DEFAULT 1,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      UNIQUE KEY unique_user_product (user_email, product_id),
      FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )
  SQL);
} catch (Throwable $e) {
  // Table may already exist
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'get';

$hasReceiptPricingTables = false;
try {
  $hasReceiptsTable = (bool)$pdo->query("SHOW TABLES LIKE 'receipts'")->fetchColumn();
  $hasReceiptItemsTable = (bool)$pdo->query("SHOW TABLES LIKE 'receipt_items'")->fetchColumn();
  $hasReceiptPricingTables = $hasReceiptsTable && $hasReceiptItemsTable;
} catch (Throwable $e) {
  $hasReceiptPricingTables = false;
}

$priceJoinSql = '';
$avgCostExpr = 'COALESCE(p.avg_import_price, 0)';


switch ($action) {
  case 'get':
    getCart($pdo, $userEmail, $avgCostExpr, $priceJoinSql);
    break;
  
  case 'add':
    addToCart($pdo, $userEmail);
    break;
  
  case 'remove':
    removeFromCart($pdo, $userEmail);
    break;
  
  case 'update':
    updateCartQty($pdo, $userEmail);
    break;
  
  case 'clear':
    clearCart($pdo, $userEmail);
    break;
  
  default:
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
    break;
}

function getCart($pdo, $userEmail, $avgCostExpr, $priceJoinSql) {
  try {
    $stmt = $pdo->prepare(<<<SQL
      SELECT 
        sc.id,
        sc.product_id,
        sc.quantity,
        p.name,
        ROUND({$avgCostExpr} * (1 + (COALESCE(p.profit_rate, 0) / 100))) AS price,
        p.image,
        p.brand,
        (sc.quantity * ROUND({$avgCostExpr} * (1 + (COALESCE(p.profit_rate, 0) / 100)))) as subtotal
      FROM shopping_cart sc
      JOIN products p ON sc.product_id = p.id
      {$priceJoinSql}
      WHERE sc.user_email = ?
      ORDER BY sc.created_at DESC
    SQL);
    $stmt->execute([$userEmail]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total = array_reduce($items, fn($sum, $item) => $sum + $item['subtotal'], 0);
    
    echo json_encode([
      'success' => true,
      'items' => $items,
      'count' => count($items),
      'total' => $total
    ]);
  } catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
  }
}

function addToCart($pdo, $userEmail) {
  $productId = (int)($_POST['product_id'] ?? $_GET['product_id'] ?? 0);
  $qty = max(1, (int)($_POST['quantity'] ?? $_GET['quantity'] ?? 1));
  
  if ($productId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid product ID']);
    exit(1);
  }
  
  try {
    // Check product exists
    $checkStmt = $pdo->prepare('SELECT id, name FROM products WHERE id = ?');
    $checkStmt->execute([$productId]);
    $product = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
      http_response_code(404);
      echo json_encode(['error' => 'Product not found']);
      exit(1);
    }
    
    // Insert or update cart
    $stmt = $pdo->prepare(<<<SQL
      INSERT INTO shopping_cart (user_email, product_id, quantity)
      VALUES (?, ?, ?)
      ON DUPLICATE KEY UPDATE quantity = quantity + ?
    SQL);
    $stmt->execute([$userEmail, $productId, $qty, $qty]);
    
    echo json_encode([
      'success' => true,
      'message' => "Added {$product['name']} to cart",
      'product_id' => $productId,
      'quantity' => $qty
    ]);
  } catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
  }
}

function removeFromCart($pdo, $userEmail) {
  $productId = (int)($_POST['product_id'] ?? $_GET['product_id'] ?? 0);
  
  if ($productId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid product ID']);
    exit(1);
  }
  
  try {
    $stmt = $pdo->prepare('DELETE FROM shopping_cart WHERE user_email = ? AND product_id = ?');
    $stmt->execute([$userEmail, $productId]);
    
    echo json_encode([
      'success' => true,
      'message' => 'Item removed from cart',
      'product_id' => $productId
    ]);
  } catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
  }
}

function updateCartQty($pdo, $userEmail) {
  $productId = (int)($_POST['product_id'] ?? $_GET['product_id'] ?? 0);
  $qty = max(1, (int)($_POST['quantity'] ?? $_GET['quantity'] ?? 1));
  
  if ($productId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid product ID']);
    exit(1);
  }
  
  try {
    $stmt = $pdo->prepare('UPDATE shopping_cart SET quantity = ? WHERE user_email = ? AND product_id = ?');
    $stmt->execute([$qty, $userEmail, $productId]);
    
    echo json_encode([
      'success' => true,
      'message' => 'Cart updated',
      'product_id' => $productId,
      'quantity' => $qty
    ]);
  } catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
  }
}

function clearCart($pdo, $userEmail) {
  try {
    $stmt = $pdo->prepare('DELETE FROM shopping_cart WHERE user_email = ?');
    $stmt->execute([$userEmail]);
    
    echo json_encode([
      'success' => true,
      'message' => 'Cart cleared'
    ]);
  } catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
  }
}
