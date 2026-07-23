# Portfolio Project Roadmap (SE / BrSE 2026-2027)

## Mục tiêu

Hoàn thành các project thực tế theo quy trình:

Requirements
→ Use Case
→ ERD
→ Database Design
→ Backend API
→ Frontend
→ Testing
→ Docker
→ AWS Deploy
→ Documentation

---

# Project 01 - HR & Attendance System

## Mức độ

Junior SE

## Mục tiêu học

* CRUD
* Authentication
* Authorization
* Database Design
* Laravel thực chiến
* AWS cơ bản

## Chức năng

### User

* Login
* Check In
* Check Out
* Xem lịch sử chấm công
* Gửi đơn nghỉ phép

### Manager

* Duyệt đơn nghỉ phép
* Xem báo cáo nhân viên

### Admin

* Quản lý nhân viên
* Quản lý phòng ban
* Quản lý ca làm

## Database

users
employees
departments
roles
attendances
leave_requests
work_shifts
holidays

## Tech Stack

### Frontend

* HTML
* CSS
* JavaScript
* Blade

### Backend

* Laravel 12
* PHP 8.4

### Database

* MySQL 8

### Infrastructure

* Docker
* Nginx
* AWS EC2
* AWS RDS

### CI/CD

* GitHub Actions

---

# Project 02 - EC Site

## Mức độ

Junior → Mid

## Mục tiêu học

* Transaction & Pessimistic Locking
* Payment Integration (Stripe Webhook)
* Inventory Management (2-Step Control)
* Complex Database Design (Snapshots)
* Qualified Invoice System Compliance (Thuế suất sau giảm giá)
* Stripe Minimum Charge Handling (Bypass đơn dưới ¥50)

## Chức năng

### User

* Đăng ký
* Đăng nhập
* Xem sản phẩm
* Giỏ hàng
* Đặt hàng

### Admin

* Quản lý sản phẩm
* Quản lý đơn hàng
* Quản lý tồn kho

## Database

users
products
categories
carts
cart_items
orders
order_items
payments
reviews
coupons
inventory_logs

## Tech Stack

* Architecture: Monolith Hybrid (Laravel Blade + Tailwind CSS + API Endpoints)
* Backend: Laravel 12 (PHP 8.4) + Stripe SDK
* Database: MySQL 8 (InnoDB)

### Infrastructure

* Docker
* AWS EC2
* AWS RDS
* AWS S3

### Bonus

* Stripe Sandbox
* Redis Cache

---

# Project 03 - Project Management System

## Mức độ

Mid-level

## Mục tiêu học

* Permission (2-Layer Auth Matrix)
* Team Development (Collaboration)
* Notification (Fan-out & Realtime)
* File Upload (Private S3 Storage)
* Aggregate Root Locking Pattern (Khử Gap Lock Deadlock)
* Task Rescheduling Notification (Quản lý trạng thái thông báo)

## Chức năng

* Project
* Task
* Kanban
* Comment
* File Upload
* Notification

## Database

projects
project_members
tasks
task_comments
task_files
notifications

## Tech Stack

* Architecture: Decoupled SPA (Single Page Application)
* Frontend: Vue 3 / React + TypeScript (Strict Mode)
* Backend: Laravel 12 API + Laravel Reverb (WebSocket)
* Database: MySQL 8 (InnoDB)

### Infrastructure

* Docker
* AWS EC2
* S3

### Bonus

* WebSocket
* Laravel Reverb

---

# Project 04 - Disaster Safety Report System

## Mức độ

Mid-level

## Mục tiêu học

* GIS
* Business System
* Dashboard

## Chức năng

* Báo cáo an toàn
* Quản lý thiên tai
* Gửi thông báo
* Bản đồ

## Database

companies
departments
employees
disasters
safety_reports
locations
notifications

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

* Google Maps API (đã chốt — không dùng Mapbox, xem `Disaster_Safety_Report_System/docs/00_開発計画書.md` 6章)

### Bonus

* Offline Geo-caching (Service Worker + IndexedDB — lưu bản đồ để dùng khi mất mạng)
* Background Geolocation Sync (đồng bộ vị trí chạy ngầm, hàng đợi gửi lại khi có mạng trở lại)

---

# Project 05 - E-Learning System

## Mức độ

Mid-level

## Mục tiêu học

* Learning Flow
* Quiz Engine

## Chức năng

* Khóa học
* Bài học
* Quiz
* Chứng chỉ

## Database

users
courses
lessons
quizzes
questions
answers
quiz_results
learning_progress
certificates

## Tech Stack

### Frontend

* React

### Backend

* Laravel API

### Database

* MySQL

### Cloud

* AWS S3

---

# Project 06 - Job Matching System

## Mức độ

Mid-level

## Mục tiêu học

* Workflow Design
* Search Engine

## Chức năng

* Đăng tuyển
* Ứng tuyển
* Phỏng vấn
* Offer

## Database

users
companies
jobs
applications
interviews
offers
messages

## Tech Stack

### Frontend

* React

### Backend

* Laravel

### Database

* MySQL

### Cloud

* AWS

---

# Project 07 - CRM System

## Mức độ

Mid → Senior

## Mục tiêu học

* Sales Process
* Business Logic

## Database

customers
contacts
opportunities
meetings
contracts
activities
sales_users

## Tech Stack

### Frontend

* Vue.js

### Backend

* Laravel

### Database

* MySQL

### Cloud

* AWS

---

# Project 08 - ERP Mini

## Mức độ

Senior

## Mục tiêu học

* Large Scale Database Design
* Module Architecture
* Event-driven Architecture (Domain Events — vd `SalesOrderCreated` để Inventory/Accounting phản ứng, thay vì gọi thẳng DB của nhau, giữ các module độc lập)

## Modules

* HR
* Sales
* Purchase
* Inventory
* Accounting

## Database

employees
suppliers
customers
products
purchases
sales
warehouses
stock_movements

## Tech Stack

### Frontend

* React

### Backend

* Laravel

### Database

* MySQL

### Infrastructure

* Docker
* AWS

---

# Project 09 - IoT Monitoring Dashboard

## Mức độ

Senior

## Mục tiêu học

* Python
* AWS
* Realtime
* Time-series Data Optimization (TimescaleDB — extension của PostgreSQL, tối ưu ghi/truy vấn hàng loạt bản ghi sensor theo thời gian)

## Chức năng

* Thu thập dữ liệu Sensor
* Dashboard
* Alert

## Database

devices
sensor_events
alerts
users

## Tech Stack

### Frontend

* React

### Backend

* FastAPI (Python)

### Database

* PostgreSQL + TimescaleDB (time-series)

### Cloud

* AWS Lambda
* API Gateway
* CloudWatch
* DynamoDB

---

# Project 10 - Mobile Companion App

## Mức độ

Senior

## Mục tiêu học

* Mobile Development
* API Integration
* MVVM/MVI Architecture + Jetpack Compose (UI khai báo, thay cho XML/View truyền thống)
* Encrypted Offline Storage (SQLCipher qua Room — bảo vệ dữ liệu đồng bộ offline)

## Chức năng

* Login
* Notification
* Dashboard
* Offline Sync

## Tech Stack

### Mobile

* Kotlin Android
* Jetpack Compose (MVVM/MVI)
* Room + SQLCipher (offline storage mã hóa)

### Backend

* Laravel API

### Database

* MySQL

### Cloud

* Firebase FCM
* AWS

---

# Checklist Bắt Buộc Cho Mỗi Project

## Design

* Requirements
* Use Case
* ERD
* Database Design
* API Design
* UML Diagrams (Sequence & Activity Diagrams cho logic lõi)
* Bilingual Design Specs (Tiếng Nhật/Anh - dành cho BrSE)

## Backend

* REST API
* Validation
* Transaction & Concurrency Control (Locking)
* Error Handling (Consistent Exception)
* Audit logging

## Frontend

* Responsive UI
* Form Validation
* Authentication

## Database

* PK
* FK
* Index (Composite, Cover Index)
* Normalization & Intended De-normalization (Snapshots)
* Check Constraints

## DevOps

* Docker
* Nginx
* SSL
* Domain

## AWS

* EC2
* RDS
* S3

## Documentation

* README
* ERD
* API Spec
* Deployment Guide

---

# Thứ tự thực hiện

1. HR & Attendance
2. EC Site
3. Project Management
4. Disaster Safety
5. E-Learning
6. Job Matching
7. CRM
8. ERP
9. IoT Dashboard
10. Mobile App

Hoàn thành 10 project này sẽ bao phủ gần như toàn bộ kiến thức:
HTML, CSS, JS, PHP, Laravel, MySQL, Java, Kotlin, Python, Network, Unix, AWS, Docker, DevOps và Database Design.
