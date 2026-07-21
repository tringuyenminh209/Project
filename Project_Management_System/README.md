# Project 03 - Project Management System

## Mức độ

Mid-level

## Mục tiêu học

* Permission (phân quyền theo project — Owner/Member, khác RBAC toàn cục của Project 01)
* Team Development
* Notification (in-app + realtime)
* File Upload (S3)

## Chức năng

* Project (tạo, mời thành viên, phân quyền)
* Task (CRUD, gán người, hạn, trạng thái)
* Kanban (kéo thả đổi trạng thái)
* Comment (bình luận trên task)
* File Upload (đính kèm file vào task, lưu S3)
* Notification (3 loại — chốt tại 00_開発計画書: được gán task / task mình phụ trách có comment / task sắp đến hạn; @mention là mở rộng tương lai)

## Database

```
projects
project_members
tasks
task_comments
task_files
notifications
```

(6 bảng theo roadmap + `users` kế thừa pattern chung; danh sách cuối cùng chốt ở `docs/08_ER図.md` / `docs/09_テーブル定義.md` khi thiết kế)

## Tech Stack

### Frontend

* **Vue 3** + TypeScript + Vite + Pinia (SPA — đã chốt, lý do & trade-off ghi tại `docs/00_開発計画書.md` 6章; React sẽ học từ Project 05)

### Backend

* Laravel API

### Database

* MySQL 8 (AWS RDS)

### Infrastructure

* Docker
* AWS EC2
* AWS RDS
* AWS S3

### Bonus

* WebSocket
* Laravel Reverb

## Tài liệu thiết kế

Bộ 21 tài liệu (PMS-000~PMS-020, cùng cấu trúc với Project 01/02) **đã hoàn thành toàn bộ nội dung** tại [`docs/`](docs/README.md) (2026/07/19), kèm bản HTML tại `docs/html/`. Mọi 論点 treo đều đã chốt (danh sách bảng cuối cùng = 7 bảng gồm `users`, xem `docs/08_ER図.md`/`docs/09_テーブル定義.md`). Sơ đồ `.drawio` sẽ đặt tại `diagrams/` (er / usecase / activity / class / screen-flow, theo layout của EC_Site) — chưa vẽ.

---
