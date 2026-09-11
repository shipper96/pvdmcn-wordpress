# PVDMCN WordPress Rebuild

Repo phục dựng `pvdmcn.com.vn` trên WordPress, dựa trên crawl Joomla cũ, các file Google Drive còn cứu được và tài liệu công bố chính thức.

## Cấu trúc

- `wp-content/themes/pvdmcn-demo/` — theme phục dựng giao diện Joomla cũ, hiện version 0.4.
- `wp-content/plugins/pvdmcn-rebuild/` — content model + dữ liệu phục dựng + sync, version 0.4.
- `recovery/` — bằng chứng crawl và manifest, không dùng trực tiếp ở frontend.

## Deploy

Sau khi GitHub deploy code mới vào WordPress demo, plugin tự kiểm tra version khi quản trị viên mở WP Admin. Nếu version dữ liệu thay đổi, nó tự chạy sync một lần.

Có thể ép sync bằng WP-CLI:

```bash
wp plugin activate pvdmcn-rebuild
wp theme activate pvdmcn-demo
wp pvdmcn sync
wp cache flush
```

## Nguyên tắc dữ liệu

- `RECOVERED_HTML`: nội dung/tiêu đề còn trong HTML HTTrack.
- `RECOVERED_LOCAL`: file gốc được cứu từ Google Drive cũ và đóng trong repo.
- `RECOVERED_PUBLIC_FILE`: còn file public trực tiếp ở nguồn công bố.
- `RECOVERED_PUBLIC_INDEX`: còn bản ghi/tên file ở HNX/CafeF nhưng chưa đóng file vào repo.
- `RECONSTRUCTED`: nội dung được tái dựng từ tài liệu chính thức, không giả là nguyên văn Joomla cũ.
- `MISSING_BODY`: chỉ cứu được URL/menu, chưa có body đáng tin cậy.

## Lưu ý

Bản demo nên tiếp tục để `blog_public=0` cho đến khi nội dung được duyệt và chuyển sang tên miền chính.
