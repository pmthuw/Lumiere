
<?php
/*
// Thông tin cấu hình
$host = '127.0.0.1'; 
$user = 'bannuochoa';
$password = 'Thienphu1236@';
$dbname = 'lumiere';
$port = 3307; // Cổng khớp với XAMPP của bạn

// 1. Kiểm tra kết nối bằng MySQLi (Đã sửa lỗi sai tên biến $servername thành $host)
$conn = new mysqli($host, $user, $password, "", $port);

if ($conn->connect_error) {
    die("Kết nối MySQLi thất bại: " . $conn->connect_error);
}
echo "Kết nối MySQLi thành công! <br>";

// 2. Thực hiện tạo DB và Import SQL bằng PDO
try {
    // CHÚ Ý: Đã thêm port=$port vào chuỗi kết nối DSN bên dưới
    $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Tạo database nếu chưa có
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbname`");

    // Đọc và thực thi file SQL
    $sqlPath = '../sql/complete_database_setup.sql';
    if (file_exists($sqlPath)) {
        $sql = file_get_contents($sqlPath);
        $pdo->exec($sql);
        echo "Database và dữ liệu đã được thiết lập thành công!";
    } else {
        echo "Cảnh báo: Đã tạo Database nhưng không tìm thấy file SQL tại: $sqlPath";
    }

} catch(PDOException $e) {
    echo "Lỗi PDO: " . $e->getMessage();
}

$conn->close();
*/
?>
