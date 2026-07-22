# Project 04 - Disaster Safety Report System

## Mức độ

Mid-level

## Mục tiêu học

* GIS (tích hợp bản đồ, toạ độ, hiển thị vị trí)
* Business System (nghiệp vụ báo cáo/quản lý theo tổ chức — công ty/phòng ban)
* Dashboard (tổng hợp trạng thái an toàn theo thời gian thực)

## Chức năng

* Báo cáo an toàn (nhân viên gửi tình trạng an toàn của bản thân khi có sự cố)
* Quản lý thiên tai (Admin/quản trị tạo, theo dõi sự kiện thiên tai đang diễn ra)
* Gửi thông báo (thông báo tới nhân viên khi có sự cố, nhắc gửi báo cáo an toàn)
* Bản đồ (hiển thị vị trí nhân viên/địa điểm liên quan trên bản đồ)

Chi tiết vai trò người dùng, luồng nghiệp vụ, và use case cụ thể sẽ chốt ở giai đoạn thiết kế (`docs/02_要件定義書.md`, `docs/03_ユースケース.md`) — chưa viết.

## Database (dự kiến, theo roadmap)

```
companies
departments
employees
disasters
safety_reports
locations
notifications
```

Danh sách cột, kiểu dữ liệu, quan hệ chính xác sẽ chốt ở `docs/08_ER図.md` / `docs/09_テーブル定義.md` khi bắt đầu thiết kế — đây mới là bảng dự kiến theo root roadmap.

## Tech Stack

### Frontend

* Vue.js

### Backend

* Laravel API

### Database

* MySQL

### Cloud

* AWS EC2
* AWS RDS
* AWS S3

### External

* Google Maps API hoặc Mapbox (chốt 1 trong 2 khi thiết kế — xem trade-off tại ADR nếu cần)

### Bonus

* Offline Geo-caching (Service Worker + IndexedDB — lưu bản đồ để dùng khi mất mạng, tình huống thực tế khi có thiên tai)
* Background Geolocation Sync (đồng bộ vị trí chạy ngầm, hàng đợi gửi lại khi có mạng trở lại)

Đây là mục **Bonus**, không phải Mục tiêu học bắt buộc — offline-first (conflict resolution khi nhiều báo cáo tạo lúc offline, quản lý hàng đợi đồng bộ) là mảng phức tạp riêng, dễ đẩy dự án vượt tầm Mid-level nếu coi là core. Cân nhắc làm sau khi phần core (báo cáo/quản lý thiên tai/thông báo/bản đồ online) đã ổn định.

## Tài liệu thiết kế

**Chưa bắt đầu.** Project 03 (Project Management System) vừa hoàn thành toàn bộ 21 tài liệu thiết kế — Project 04 sẽ đi theo đúng quy trình đó (`/system-design` skill, §19 thứ tự phụ thuộc, cấu trúc `docs/00_開発計画書.md` ~ `docs/20_運用保守手順書.md`).

Việc cần làm theo thứ tự:

1. `docs/00_開発計画書.md` — xác nhận lại tech stack (đặc biệt Google Maps API vs Mapbox), phạm vi, lịch trình
2. `docs/01_企画書.md` → `docs/02_要件定義書.md` — làm rõ "companies" là gì trong ngữ cảnh này (multi-tenant? hay 1 công ty duy nhất?), vai trò người dùng cụ thể (nhân viên / quản lý phòng ban / Admin?)
3. Theo đúng thứ tự phụ thuộc ở §19 của `/system-design` cho tới khi đủ bộ tài liệu

## Việc chưa làm (follow-up)

* Toàn bộ `docs/*.md` (chưa có file nào)
* `diagrams/` (ER, use case, activity, class, screen-flow)
* `guide/` (giáo trình học theo chương, sau khi có code)
* `backend/`, `frontend/` (chưa code)
