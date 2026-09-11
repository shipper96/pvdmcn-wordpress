# Deploy/update từ GitHub

Repo chỉ cần cập nhật hai thư mục sau vào WordPress demo:

```text
wp-content/themes/pvdmcn-demo/
wp-content/plugins/pvdmcn-rebuild/
```

## Sau `git pull`

Theme/plugin v0.7 có auto-sync theo version. Lần đầu một quản trị viên mở WP Admin, plugin sẽ tự đồng bộ **an toàn** một lần.

Khuyến nghị post-deploy:

```bash
cd ~/demo.gamen.pro/public
wp plugin activate pvdmcn-rebuild
wp theme activate pvdmcn-demo
wp pvdmcn sync
wp cache flush
wp rewrite flush
```

`wp pvdmcn sync` từ v0.7 **không ghi đè** nội dung hoặc menu mà quản trị viên đã sửa trong CMS.

Chỉ khi thật sự muốn đưa nội dung về seed trong Git mới dùng:

```bash
wp pvdmcn sync --force-content
```

Chỉ khi thật sự muốn xóa chỉnh sửa menu thủ công và dựng lại menu từ seed:

```bash
wp pvdmcn sync --force-menu
```

Có thể ép cả hai:

```bash
wp pvdmcn sync --force-content --force-menu
```

## CMS

Đăng nhập:

```text
https://demo.gamen.pro/wp-admin/
```

Các phần người quản trị tự sửa được:

```text
PVDMCN → Cấu hình giao diện
Pages → nội dung trang
Posts → tin/bài
Tài liệu PVDMCN → tài liệu + Link tài liệu hiển thị
Appearance → Menus → menu
Media → ảnh/file
```

## Chống index demo

```bash
wp option get blog_public
# phải là 0
```

Nếu cần:

```bash
wp option update blog_public 0
```

Nếu có cache tầng server/CDN, purge sau deploy.
