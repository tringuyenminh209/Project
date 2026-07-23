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

**Vai trò người dùng (đã chốt tại `docs/00_開発計画書.md` 3章, quyền hạn chi tiết tại `docs/02_要件定義書.md` 8章)**: hệ thống nội bộ 1 công ty duy nhất (không phải multi-tenant SaaS), 3 tầng người dùng — Admin (BCP/quản lý khủng hoảng: tạo/quản lý sự kiện thiên tai, xem dashboard toàn công ty), Trưởng phòng ban (xem tình trạng an toàn của phòng ban mình theo thời gian thực), Nhân viên (nhận thông báo, gửi báo cáo an toàn của bản thân — 2 lựa chọn "an toàn"/"cần hỗ trợ", có thể báo lại khi tình huống thay đổi). Use case cụ thể sẽ chốt ở `docs/03_ユースケース.md` — chưa viết.

## Database (đã chốt tại `docs/08_ER図.md` / `docs/09_テーブル定義.md`)

```
companies       — 1 dòng duy nhất (profile công ty, không FK với bảng nào)
departments
employees       — role phẳng 3 tầng (admin/manager/staff)
locations       — master địa điểm làm việc, không có UI CRUD riêng
disasters       — target_department_ids lưu JSON (không thêm bảng trung gian)
safety_reports  — UNIQUE(disaster_id, employee_id), báo lại = UPDATE
notifications
```

Đúng 7 bảng theo roadmap gốc, không phát sinh thêm bảng nào. Chi tiết cột/kiểu dữ liệu/index/FK xem `docs/09_テーブル定義.md`; sơ đồ quan hệ xem `docs/08_ER図.md` và `diagrams/er/dsr_erd.drawio`.

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

* Google Maps API (Maps JavaScript API + Geocoding API — đã chốt, không dùng Mapbox. Lý do: độ chính xác geocoding địa chỉ tiếng Nhật, phổ biến hơn tại thị trường Nhật. Xem `docs/00_開発計画書.md` 6章)

### Bonus

* Offline Geo-caching (Service Worker + IndexedDB — lưu bản đồ để dùng khi mất mạng, tình huống thực tế khi có thiên tai)
* Background Geolocation Sync (đồng bộ vị trí chạy ngầm, hàng đợi gửi lại khi có mạng trở lại)

Đây là mục **Bonus**, không phải Mục tiêu học bắt buộc — offline-first (conflict resolution khi nhiều báo cáo tạo lúc offline, quản lý hàng đợi đồng bộ) là mảng phức tạp riêng, dễ đẩy dự án vượt tầm Mid-level nếu coi là core. Cân nhắc làm sau khi phần core (báo cáo/quản lý thiên tai/thông báo/bản đồ online) đã ổn định.

## Tài liệu thiết kế

**Toàn bộ 21 file `docs/00_開発計画書.md` ~ `docs/20_運用保守手順書.md` đã viết xong nội dung (2026/07/23)** — hoàn thành pha thiết kế theo đúng thứ tự phụ thuộc §19 của `/system-design`. `02_要件定義書` (văn bản quan trọng nhất — 8 chương quyền hạn + 9 chương business rule) đã chốt thêm 3 điểm mà `00` từng để ngỏ: lựa chọn trạng thái an toàn (safe/needs_help, "chưa xác nhận" chỉ là trạng thái hiển thị suy ra chứ không phải lựa chọn), cách báo lại (UPDATE cùng 1 dòng, không giữ lịch sử), cách lưu vị trí (snapshot tại thời điểm báo cáo, không tham chiếu master). `03_ユースケース`/`04_業務フロー` cụ thể hoá 15 use case và 6 luồng nghiệp vụ (kèm đối chiếu AS-IS/TO-BE với cách xác nhận an toàn kiểu cũ — cây điện thoại liên lạc). `07_機能一覧` liệt kê 19 chức năng (ít hơn PMS vì mô hình quyền phẳng 3 tầng đơn giản hơn). `05_画面遷移図`/`06_画面設計` chốt 11 màn hình, URL trực tiếp cho form báo cáo an toàn (để noti bấm vào là vào thẳng form), và bộ design token riêng (3 màu trạng thái an toàn, ping bản đồ). Lúc viết 05, phát hiện và sửa 1 mâu thuẫn: ma trận quyền ở 02 đã cho phép Trưởng phòng ban tự báo cáo an toàn nhưng REQ-009/010 lại chỉ ghi "nhân viên" — đã đồng bộ lại 02/03/04/07. `08_ER図`/`09_テーブル定義` chốt 7 bảng vật lý: `companies` là node cô lập (không FK với bảng nào, đúng model single-company), đối tượng nhận thiên tai (`target_department_ids`) lưu bằng cột JSON thay vì thêm bảng trung gian (giữ đúng cam kết roadmap 7 bảng, đổi lại phải validate ở tầng app chứ DB không ràng buộc được), và nhận ra toàn bộ hệ thống không có thao tác xoá vật lý nào (chỉ vô hiệu hoá/UPDATE/chuyển trạng thái) — điểm khác biệt so với PMS (xoá task kéo theo xoá comment/file). File `diagrams/er/dsr_erd.drawio` đã sinh kèm theo. `10_API設計` chốt 26 API: gộp "gửi báo cáo" + "báo lại" thành 1 API PUT duy nhất (upsert, khớp với thiết kế UPDATE-only ở 09), dashboard phòng ban **không nhận department_id qua URL** — tự suy ra từ token, chặn đường tấn công IDOR ngay từ thiết kế thay vì phải viết logic kiểm tra, và quyết định không dùng WebSocket (khác PMS có Reverb) vì roadmap/NFR không yêu cầu real-time thật, poll 30s là đủ. `11_基本設計書` tổng hợp lại toàn bộ thiết kế ngoài và phát hiện 2 điểm hay: bảng ánh xạ màn hình×DB cho thấy `companies` không hề được đụng tới bởi bất kỳ màn hình nào (xác nhận lại quyết định node cô lập ở 08) và `locations` chỉ luôn luôn Read (đúng như đã định không có UI CRUD riêng); còn hệ thống phân quyền chỉ cần đúng 2 class Policy (`SafetyReportPolicy`, `NotificationPolicy`) — hệ quả trực tiếp của mô hình role phẳng 3 tầng, so với 6 class Policy của PMS. Do báo cáo an toàn luôn là UPDATE 1 dòng duy nhất (không có "nhóm bản ghi anh em"), hệ thống này hoàn toàn không cần tới Aggregate Root Locking như PMS/EC_Site. `12_詳細設計書` cụ thể hoá thành 9 đoạn pseudo-code Service.

**Đợt review kỹ thuật độc lập (2026/07/23)**: sau khi viết xong 00-12, user gửi 1 bản phân tích kỹ thuật (`dsr_design_analysis.md`) chỉ ra 4 vấn đề. Đã verify độc lập từng điểm trước khi sửa — cả 4 đều xác nhận đúng:
- **3 lỗi hiệu năng N+1** (đều ở `12_詳細設計書`): (1) `DisasterService::store` gửi thông báo bằng vòng lặp N lần `INSERT` riêng lẻ thay vì 1 câu bulk insert — với 1.000 nhân viên dễ vượt timeout NFR-001 (60s); (2) `SafetyReportService::upsertMine` dùng SELECT-rồi-INSERT/UPDATE kèm bắt lỗi UNIQUE thủ công thay vì dùng `upsert()` nguyên tử của Eloquent (1 query, DB tự xử lý); (3) batch `SendReportReminders` chạy N câu `SELECT EXISTS` để check trùng thông báo trong batch thay vì gom 1 query rồi lọc trong RAM.
- **1 rủi ro nghiệp vụ nghiêm trọng** (map ghim cứu hộ, ở `10_API設計`/`06_画面設計`/`12_詳細設計書`): thiết kế cũ khi 1 báo cáo `needs_help` không có toạ độ (do lỗi GPS/network) sẽ tự động fallback về toạ độ **văn phòng làm việc thường ngày** để vẽ ghim đỏ — khiến đội cứu hộ tưởng nạn nhân đang gặp nạn tại văn phòng trong khi họ có thể đang ở nhà. Đây là lỗi có thể ảnh hưởng thật tới an toàn tính mạng nên được ưu tiên sửa cao nhất. Đã tách rõ: chỉ vẽ ghim khi báo cáo có toạ độ thật hoặc khi người đó **chưa báo cáo** (dùng địa điểm làm việc như một gợi ý "đáng lẽ ở đâu"); báo cáo có rồi nhưng không toạ độ thì đưa vào 1 danh sách chữ riêng "vị trí: chưa xác định", không vẽ ghim.

Đã sửa đồng bộ 4 file: `06_画面設計`(v1.1, thêm quy tắc UI-007), `10_API設計`(v1.1, tách `map_pins`/`unlocated_reports`), `11_基本設計書`(v1.1, sửa lại mockup minh hoạ), `12_詳細設計書`(v1.1, sửa cả 4 đoạn pseudo-code liên quan + sequence diagram).

`13_インフラ設計` chốt 2 điểm: (1) **không tạo S3 bucket ở bản phát hành đầu** — vì không có FUNC nào cần upload file, tạo hạ tầng cho tính năng chưa dùng chỉ tốn thêm chi phí quản lý IAM và bề mặt tấn công thừa; sẽ tạo khi thật sự cần tính năng đính kèm tài liệu BCP trong tương lai; (2) API key của Google Maps **không thể giấu được** vì gọi trực tiếp từ browser — nên bảo vệ bằng giới hạn referrer + cảnh báo vượt hạn mức chứ không phải theo kiểu giữ bí mật như secret của Reverb (PMS). Nhờ 2 quyết định này, hạ tầng DSR đơn giản hơn hẳn PMS (không container Reverb, không WebSocket port, không S3).

`14_セキュリティ設計` chốt nốt 2 điểm mà `02_要件定義書` 15章 đã để ngỏ từ đầu: (1) cách lưu token ở SPA (localStorage + bộ giải pháp giảm thiểu đi kèm bắt buộc: CSP, cấm `v-html`, hết hạn 8h, thu hồi phía server); (2) chính sách riêng tư cho dữ liệu vị trí — phạm vi truy cập giữ nguyên theo ma trận quyền 8 chương, còn thời hạn lưu trữ thì đi theo vòng đời của `safety_reports` (không có tính năng xoá riêng), kèm ghi chú rõ ràng: nếu triển khai thật ở Nhật thì cần rà lại theo Luật bảo vệ thông tin cá nhân (APPI), tài liệu này chỉ đưa ra mặc định hợp lý cho phạm vi đồ án. Điểm kỹ thuật đáng chú ý: CSP không thể dùng `script-src 'self'` thuần như PMS vì phải tải Google Maps SDK từ `maps.googleapis.com` — đây là một đánh đổi có ghi nhận rõ ràng (nới lỏng CSP một chút để đổi lấy tính năng bản đồ), không phải bỏ sót.

`15_単体試験仕様書` chốt trọng tâm test: (1) ma trận quyền chỉ 33 ô (11 thao tác × 3 role) — nhỏ hơn hẳn 80 ô của PMS, hệ quả trực tiếp của mô hình role phẳng; (2) **bộ test UT-DASH-005/006 là ưu tiên số 1** — test trực tiếp cho lỗi map ghim sai vị trí vừa fix ở `12_詳細設計書` v1.1, đảm bảo báo cáo `needs_help` không toạ độ tuyệt đối không lọt vào `map_pins` mà phải rơi vào `unlocated_reports`; (3) test riêng cho atomic upsert (gửi trùng lúc 2 request báo cáo lần đầu chỉ tạo đúng 1 dòng) và test đếm số câu query để xác nhận bulk insert/batch N+1 đã thực sự sửa (không chỉ sửa trên giấy).

`16_結合試験仕様書` không có phần test S3/WebSocket như PMS (vì DSR không dùng 2 thứ đó) — thay vào đó phần tương đương là **test Frontend↔Google Maps/Geocoding API thật** (7章). Test quan trọng nhất toàn tài liệu là `IT-MAP-005`: mở dashboard thật trên trình duyệt, xác nhận **bằng mắt** báo cáo không toạ độ không hề xuất hiện ghim nào trên bản đồ và nằm đúng trong danh sách "vị trí chưa xác định" — đây là bước kiểm tra lại lỗi map ghim sai vị trí ở tầng thực tế (không chỉ dừng ở cấu trúc JSON response như test đơn vị `UT-DASH-005/006`).

`17_システム試験仕様書` chốt 4 kịch bản E2E theo đúng dòng thời gian ứng phó thiên tai thật (Admin tạo thiên tai → nhân viên nhận thông báo → báo cáo → quản lý xem dashboard → Admin xem tổng), thay vì kịch bản "vận hành team" như PMS. Điểm hay: `ST-REC-003` (2 trình duyệt gửi báo cáo lần đầu cùng lúc) là bước xác nhận cuối cùng cho thiết kế atomic upsert — đã qua đủ 3 tầng kiểm chứng (unit `UT-RPT-007` → integration `IT-DB-001` → system `ST-REC-003`), và tài liệu không mắc lại lỗi trích dẫn nhầm sang tài liệu `24_性能試験仕様書` không tồn tại mà PMS từng bị.

`18_UAT` xoay quanh cảm nhận thực tế của 3 vai trò (Admin/Trưởng phòng ban/Nhân viên) bằng ngôn ngữ nghiệp vụ BCP thay vì mã lỗi kỹ thuật. Điểm hay nhất là `UAT-MGR-004`: xác nhận bằng cảm nhận thực tế rằng khi nhìn bản đồ, người xem không hiểu nhầm "chưa xác định vị trí" thành "đang ở văn phòng" — đây là bài test cảm nhận cuối cùng cho đúng lỗi map ghim sai vị trí đã fix trước đó (sau khi đã test ở tầng JSON response và tầng browser thật).

`19_リリース手順書` thêm 1 bước không có ở PMS: **xác nhận API key Google Maps** (đúng domain, đúng giới hạn referrer, đã bật cảnh báo vượt hạn mức) trước khi deploy, và checklist xác nhận **không lỡ tạo S3 bucket** — đúng theo quyết định "không cấp hạ tầng cho tính năng chưa dùng" ở `13_インフラ設計`.

`20_運用保守手順書` đặt ưu tiên xử lý sự cố theo đúng thứ tự rủi ro thực tế của hệ thống này: sự cố liên quan tới lộ dữ liệu vị trí (map ghim sai) được xếp ưu tiên xử lý cao nhất, cao hơn cả sự cố về quyền hạn — phản ánh đúng bản chất đây là hệ thống an toàn tính mạng, không phải hệ thống quản lý dự án thông thường như PMS.

**Lúc rà soát tổng thể cuối cùng (grep chéo toàn bộ 21 file)**, phát hiện thêm 1 chỗ tài liệu tự mâu thuẫn nhỏ: `01_企画書` §13 (bảng chi phí) vẫn ghi AWS S3 tốn "vài chục yên/tháng" như đang chạy thật, trong khi `13_インフラ設計` (viết sau) đã chốt không tạo S3 ở bản đầu — đã sửa về ¥0 kèm tham chiếu đúng quyết định, cùng lúc dọn luôn 1 câu tham chiếu "07_機能一覧 (dự kiến viết)" dù tài liệu đó đã xong từ lâu. Đây là ví dụ thực tế cho việc tại sao phải rà soát chéo toàn bộ bộ tài liệu ở mốc hoàn thành, không chỉ rà từng cặp file liền kề lúc viết.

Toàn bộ 21 file `docs/00_開発計画書.md` ~ `docs/20_運用保守手順書.md` đã hoàn thành nội dung thật, HTML tương ứng đã sinh và validate OK. Xem chi tiết trạng thái từng file tại [`docs/README.md`](docs/README.md).

## Việc đã làm (pha thiết kế — hoàn thành theo §19 `/system-design`)

Toàn bộ 21 tài liệu thiết kế (`docs/00_開発計画書.md` ~ `docs/20_運用保守手順書.md`) đã viết xong nội dung theo đúng thứ tự phụ thuộc, kèm 1 đợt review kỹ thuật độc lập (phát hiện 3 lỗi hiệu năng N+1 + 1 rủi ro nghiệp vụ nghiêm trọng về hiển thị vị trí, đã verify và sửa toàn bộ) và 1 đợt rà soát chéo toàn bộ 21 file ở mốc hoàn thành.

## Việc chưa làm (follow-up)

* `diagrams/` (ER đã xong — `er/dsr_erd.drawio`; use case, activity, class, screen-flow còn lại)
* `guide/` (giáo trình học theo chương, sau khi có code)
* `backend/`, `frontend/` (chưa code)
