<?php
/**
 * Manual test endpoint to debug password reset
 */
require_once __DIR__ . '/../setup_db.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    die("Database connection failed");
}

$result = [
    'success' => false,
    'message' => 'No action',
    'debug' => []
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int)($_POST['user_id'] ?? 0);
    $scope = ($_POST['scope'] ?? 'customer') === 'admin' ? 'admin' : 'customer';
    $new_password = $_POST['password'] ?? '12345';

    $result['debug']['requested'] = [
        'user_id' => $user_id,
        'scope' => $scope,
        'new_password' => $new_password
    ];

    if ($user_id <= 0) {
        $result['message'] = 'Invalid user_id';
        $result['debug']['error'] = 'user_id must be > 0';
    } else {
        try {
            $table = ($scope === 'admin') ? 'admin_users' : 'users';
            $result['debug']['table'] = $table;

            // Check if user exists
            $checkStmt = $pdo->prepare("SELECT id, password_hash FROM $table WHERE id = ?");
            $checkStmt->execute([$user_id]);
            $user = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                $result['message'] = "User not found in $table with id=$user_id";
                $result['debug']['check_result'] = 'NOT FOUND';
            } else {
                $result['debug']['user_before'] = [
                    'id' => $user['id'],
                    'password_hash' => $user['password_hash']
                ];

                // Try to update
                $updateSQL = "UPDATE $table SET password_hash = ? WHERE id = ?";
                $result['debug']['update_sql'] = $updateSQL;
                $result['debug']['update_params'] = [$new_password, $user_id];

                $updateStmt = $pdo->prepare($updateSQL);
                $updateResult = $updateStmt->execute([$new_password, $user_id]);
                
                $result['debug']['update_execute_result'] = $updateResult;
                $result['debug']['rows_affected'] = $updateStmt->rowCount();

                if ($updateResult && $updateStmt->rowCount() > 0) {
                    // Verify
                    $verifyStmt = $pdo->prepare("SELECT password_hash FROM $table WHERE id = ?");
                    $verifyStmt->execute([$user_id]);
                    $verifyPassword = $verifyStmt->fetchColumn();
                    
                    $result['success'] = true;
                    $result['message'] = "Password reset successfully to: $new_password";
                    $result['debug']['verified_password'] = $verifyPassword;
                } else {
                    $result['message'] = "Update failed or no rows affected";
                }
            }
        } catch (Exception $e) {
            $result['message'] = "Exception: " . $e->getMessage();
            $result['debug']['exception'] = $e->getMessage();
        }
    }
}

header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
