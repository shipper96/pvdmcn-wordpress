# Changelog

## 0.7.0

- Thêm menu **PVDMCN** riêng trong WordPress Admin.
- Thêm màn hình **PVDMCN → Cấu hình giao diện** để chỉnh trực tiếp trong CMS:
  - logo;
  - 3 ảnh slideshow;
  - tên công ty, địa chỉ, điện thoại, fax, email, website, link Mail;
  - dòng credit footer;
  - tiêu đề các khối Tin tức, Tài liệu, Product, Investor Relations, Activities;
  - bật/tắt và sửa dòng cảnh báo bản demo.
- Theme đọc các cấu hình này từ database; Git pull không ghi đè chúng.
- Thêm meta box **PVDMCN · Link tài liệu** trong từng Tài liệu PVDMCN. URL nhập thủ công luôn ưu tiên Drive/fallback từ seed.
- Bảo vệ nội dung CMS khỏi auto-sync: nếu trang/bài đã được sửa thủ công, các bản Git sau không tự ghi đè nội dung đó.
- Bảo vệ menu WordPress khỏi auto-sync. Menu hiện có được giữ nguyên; chỉ `--force-menu` mới dựng lại menu từ seed.
- `wp pvdmcn sync` mặc định an toàn với CMS. Có thể dùng `--force-content` và/hoặc `--force-menu` khi thực sự muốn ghi đè.
- Giữ nguyên logic v0.6: tài liệu ưu tiên mở trực tiếp Google Drive, không chép nội dung PDF dài vào WordPress.


## 0.6.0

- Đưa Investor Relations về đúng logic site Joomla cũ: tiêu đề tài liệu trỏ thẳng tới Google Drive khi có link Drive đã phục hồi.
- Không chép nội dung dài của PDF vào bài WordPress; mỗi mục tài liệu chỉ còn tiêu đề + link.
- Bài “TÀI LIỆU HỌP ĐHĐCĐ THƯỜNG NIÊN NĂM 2020” giữ 15 link Google Drive gốc.
- Bổ sung link Drive đã phục hồi cho BCTN 2017, Điều lệ sửa đổi 2019, Q1/2019 và giải trình hủy niêm yết.
- CafeF/HNX/local Media chỉ còn là fallback khi không có Drive.
- Archive, shortcode và homepage mở tài liệu trực tiếp thay vì qua trang trung gian.

## 0.4.0

- Đưa 5 file Google Drive người quản trị cứu được vào repo và tự nhập vào Media Library.
- Tạo/sync 38 page theo cấu trúc Joomla cũ.
- Giữ label menu gần nguyên bản: News, Product, Quality control, Activities, Investor Relations, Customers, dịch vụ, QUY CHẾ, NỘI QUY.
- Phục dựng body các trang Giới thiệu, Sản phẩm, Hóa chất dung dịch khoan, Sản phẩm lọc hóa dầu, Sản phẩm khác, Quality control, Sản xuất kinh doanh, Dịch vụ, Logistics và Liên hệ từ nguồn chính thức.
- Thêm 14 mục tài liệu doanh nghiệp và 4 bài còn sót trong HTML homepage 2020.
- Theme 0.4: sidebar 3 khối, cờ EN/VI gốc, layout nội dung 705/242 giống site cũ hơn.
- Auto-sync theo version sau Git deploy.

## 0.3.0

- Phục dựng khung visual từ CSS/assets Joomla: 960px, menu xanh/đỏ, slideshow 960×242, logo/banner/slider ảnh gốc.
