# Deploy/update từ GitHub

Repo chỉ cần cập nhật hai thư mục sau vào WordPress demo:

```text
wp-content/themes/pvdmcn-demo/
wp-content/plugins/pvdmcn-rebuild/
```

Plugin v0.6 có auto-sync theo version. Sau Git deploy, lần đầu một quản trị viên mở WP Admin, plugin sẽ tự đồng bộ dữ liệu mới một lần.

Khuyến nghị vẫn chạy post-deploy bằng WP-CLI nếu workflow GitHub cho phép:

```bash
wp plugin activate pvdmcn-rebuild
wp theme activate pvdmcn-demo
wp pvdmcn sync
wp cache flush
```

Kiểm tra demo không index:

```bash
wp option get blog_public
# phải là 0
```

Nếu có cache tầng server/CDN, purge sau deploy.
