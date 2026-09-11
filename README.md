# PVDMCN WordPress Rebuild

Bản phục dựng `pvdmcn.com.vn` trên WordPress từ cấu trúc Joomla cũ, HTTrack, Google Drive và các tài liệu doanh nghiệp còn lưu trữ.

## Kiến trúc

- `wp-content/themes/pvdmcn-demo/`: giao diện phục dựng gần site Joomla cũ.
- `wp-content/plugins/pvdmcn-rebuild/`: content model, seed/recovery data, document links và CMS settings.
- `recovery/`: bằng chứng phục hồi, URL map và ghi chú nguồn.

## Từ v0.7: CMS là nơi sửa nội dung

Đăng nhập WordPress tại:

```text
https://demo.gamen.pro/wp-admin/
```

### Có thể sửa trực tiếp trong CMS

- **PVDMCN → Cấu hình giao diện**: logo, slideshow, footer, thông tin công ty, tiêu đề các block trang chủ.
- **Pages**: nội dung Giới thiệu, Sản phẩm, Dịch vụ, Hoạt động…
- **Posts**: tin tức/công bố.
- **Tài liệu PVDMCN**: tiêu đề tài liệu, năm/loại và `Link tài liệu hiển thị`.
- **Appearance → Menus**: menu.
- **Media**: ảnh/file.

Các thay đổi trên được lưu trong database WordPress và **không bị `git pull` ghi đè**.

## GitHub quản lý gì?

Git quản lý:

- PHP/theme/layout;
- CSS;
- plugin logic;
- seed phục hồi mặc định;
- tài nguyên giao diện gốc còn cứu được.

Không dùng Git để quản lý nội dung vận hành hằng ngày trong database.

## Đồng bộ

Sau deploy:

```bash
wp pvdmcn sync
wp cache flush
```

Từ v0.7, lệnh trên bảo vệ nội dung/menu đã sửa trong CMS.

Chỉ dùng các cờ sau khi thật sự muốn ghi đè:

```bash
wp pvdmcn sync --force-content
wp pvdmcn sync --force-menu
```

## Tài liệu

Giữ nguyên nguyên tắc site cũ:

```text
Tiêu đề tài liệu → Google Drive gốc
```

Nếu Drive không còn, plugin mới dùng file local/CafeF/HNX làm fallback. Không chép nội dung dài từ PDF thành bài WordPress.
