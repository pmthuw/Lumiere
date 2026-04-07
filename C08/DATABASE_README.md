# Database Setup Instructions

## Cách thiết lập database hoàn chỉnh

### Phương pháp 1: Sử dụng file SQL (Khuyến nghị)

1. Mở XAMPP Control Panel và khởi động **Apache** và **MySQL**
2. Truy cập `http://localhost/phpmyadmin`
3. Tạo database mới tên `perfume_store` (nếu chưa có)
4. Chọn database `perfume_store`
5. Nhấn tab "Import"
6. Chọn file `sql/complete_database_setup.sql`
7. Nhấn "Go" để import

### Phương pháp 2: Sử dụng PHP script

1. Đảm bảo XAMPP đang chạy
2. Truy cập `http://localhost/web%20bán%20nước%20hoa%20(1)%20(1)%20(1)/backend/setup_db.php`

## Cấu trúc database

File `complete_database_setup.sql` sẽ tạo:

- **products**: Bảng sản phẩm (14 sản phẩm mẫu)
- **users**: Bảng khách hàng
- **admin_users**: Bảng quản trị viên (tài khoản admin mặc định)
- **orders**: Bảng đơn hàng
- **order_items**: Bảng chi tiết đơn hàng

## Tài khoản mặc định

- **Admin**: username: `admin`, password: `admin123`
- **Test users**: password cho tất cả: `password123`

## Kiểm tra

Sau khi setup, truy cập:

- `http://localhost/web%20bán%20nước%20hoa%20(1)%20(1)%20(1)/backend/api/products.php` để kiểm tra API sản phẩm
- `http://localhost/web%20bán%20nước%20hoa%20(1)%20(1)%20(1)/frontend/index.php` để xem website
