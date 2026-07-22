# Project 02 - EC Site

## Mức độ

Junior → Mid

## Mục tiêu học

* Transaction (đơn hàng, thanh toán)
* Payment (Stripe Sandbox — PaymentIntent, Webhook)
* Inventory (giữ chỗ / xác nhận / giải phóng tồn kho 2 giai đoạn)
* Complex Database Design (snapshot giá/thuế tại thời điểm đặt hàng)
* Qualified Invoice System Compliance (thuế tiêu thụ tính trên số tiền sau khi trừ coupon theo từng dòng sản phẩm — không phải trên giá gốc, đúng luật thuế Nhật Bản)
* Stripe Minimum Charge Handling (bypass Stripe khi đơn hàng ¥0 hoặc dưới ¥50 — giới hạn課金 tối thiểu của Stripe cho JPY)

## Vai trò người dùng

| Vai trò | Mô tả |
|---|---|
| Guest (chưa đăng nhập) | Chỉ xem sản phẩm |
| Customer (thành viên) | Mua hàng, giỏ hàng, lịch sử đơn hàng, đánh giá |
| Admin | Quản lý sản phẩm, tồn kho, đơn hàng, coupon |

Không có vai trò Manager/duyệt đơn như Project 01 — trạng thái đơn hàng (`pending → paid → shipped → delivered`) tự đóng vai trò luồng phê duyệt.

## Chức năng

### User

* Đăng ký / Đăng nhập / Đăng xuất (Sanctum token)
* Xem sản phẩm theo danh mục, tìm kiếm, chọn biến thể (variant)
* Giỏ hàng (thêm / sửa số lượng / xóa)
* Đặt hàng: giỏ hàng → nhập địa chỉ giao → áp coupon → xác nhận đơn
* Thanh toán qua Stripe Sandbox (PaymentIntent + Webhook)
* Viết đánh giá sản phẩm (chỉ khi đã mua)
* Xem lịch sử đơn hàng, trạng thái giao hàng

### Admin

* Quản lý sản phẩm / danh mục / biến thể / hình ảnh
* Quản lý tồn kho (điều chỉnh, xem log)
* Quản lý đơn hàng (đổi trạng thái, đăng ký vận đơn)
* Quản lý coupon (giảm giá cố định / theo %, điều kiện áp dụng)
* Báo cáo doanh thu & tồn kho

## Database (16 bảng)

```
users
addresses
categories
products
product_variants
product_images
inventories
inventory_logs
carts
cart_items
orders
order_items
payments
shipments
coupons
reviews
```

Entity trung tâm là `orders`, nơi giao nhau của hai luồng: `users → carts → orders` (mua hàng) và `products → product_variants → inventories` (tồn kho). Xem chi tiết tại `docs/08_ER図.md` và `docs/09_テーブル定義.md`.

## Business rules quan trọng

* **Snapshot giá/thuế**: tên, giá, thuế suất tại thời điểm xác nhận đơn được lưu cố định vào `order_items`, không bị ảnh hưởng khi giá sản phẩm thay đổi sau này.
* **Tồn kho 2 giai đoạn**: giữ chỗ khi đặt hàng → xác nhận trừ kho khi thanh toán thành công → giải phóng khi thanh toán thất bại/hủy. Dùng transaction + pessimistic lock (`SELECT ... FOR UPDATE`) để tránh bán trùng khi có nhiều đơn đồng thời.
* **Coupon**: kiểm tra thời hạn hiệu lực, số lần dùng tối đa, giá trị đơn tối thiểu. Số tiền giảm được phân bổ theo tỷ lệ xuống từng dòng sản phẩm (`order_items.line_discount`) **trước khi** tính thuế của dòng đó (BR-TAX-005) — không tính thuế trên giá gốc rồi mới trừ giảm giá ở tổng cuối.
* **Bypass Stripe cho đơn quá nhỏ**: nếu `grand_total` sau giảm giá là ¥0 hoặc dưới ¥50 (giới hạn課金 tối thiểu JPY của Stripe), hệ thống xác nhận thanh toán ngay mà không gọi Stripe (`payments.bypass_reason`), giữ nguyên số tiền giảm giá thật — không thổi phồng để ép về ¥0 (BR-PAY-004).
* **Review**: chỉ cho phép đánh giá sản phẩm đã có trong `order_items` của chính người dùng (chống giả mạo).

## Tech Stack

* Architecture: Monolith Hybrid (Laravel Blade render trang + gọi API Endpoints cho hành vi động: giỏ hàng, coupon preview, xác nhận đơn, Stripe Webhook)

### Frontend

* Laravel Blade
* Tailwind CSS

### Backend

* Laravel 12
* PHP 8.4

### Database

* MySQL 8

### Payment

* Stripe Sandbox (PaymentIntent, Webhook)

### Cache (bonus)

* Redis 7 (cache danh sách sản phẩm / cây danh mục)

### Infrastructure

* Docker Compose
* Nginx
* AWS EC2 / RDS / S3 (lưu ảnh sản phẩm)

### CI/CD

* GitHub Actions

## Tài liệu thiết kế

Bộ tài liệu đầy đủ (21 văn bản, EC-000 ~ EC-020, theo cùng khuôn mẫu với Project 01) nằm tại [`docs/`](docs/README.md). Bản HTML để đọc/in nằm tại [`docs/html/index.html`](docs/html/index.html). Sơ đồ (.drawio) nằm tại [`diagrams/`](diagrams).

Thứ tự đọc trước khi code, xem chi tiết ở `docs/README.md`:

1. `docs/02_要件定義書.md` (chương 9 — business rules) — nền tảng của mọi quyết định thiết kế
2. `docs/08_ER図.md` + `docs/09_テーブル定義.md` — cấu trúc dữ liệu
3. `docs/10_API設計.md` (mục 6.5 xác nhận đơn, 6.7 Webhook) — 2 API phức tạp nhất
4. `docs/12_詳細設計書.md` (chương 5 — Service) — pseudocode để implement
5. `docs/15_単体試験仕様書.md` (chương 10~12) — tiêu chí "đúng"

## Việc chưa làm (follow-up)

* DDL thực thi (`database/ddl/` — dựa trên `docs/09_テーブル定義.md`, viết thành Laravel Migration)
* Postman collection cho từng API (làm sau khi implement, giống Project 01)
