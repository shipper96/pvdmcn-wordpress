<?php
$company=pvdmcn_demo_opt('company_name','CÔNG TY CỔ PHẦN HÓA PHẨM DẦU KHÍ DMC - MIỀN BẮC');
$address=pvdmcn_demo_opt('address','Thôn Tế Xuyên - xã Đình Xuyên - Gia Lâm - Hà Nội');
$phone=pvdmcn_demo_opt('phone','024.3827.1483');
$fax=pvdmcn_demo_opt('fax','024.3878.0902');
$email=pvdmcn_demo_opt('email','dmcmb@pvdmcn.com.vn');
$website_label=pvdmcn_demo_opt('website_label','pvdmcn.com.vn');
$website_url=pvdmcn_demo_opt('website_url','https://pvdmcn.com.vn/');
?>
<footer class="old-footer"><div class="company-footer"><strong><?php echo esc_html($company); ?></strong><br>Địa chỉ trụ sở: <?php echo esc_html($address); ?><br>Tel: <?php echo esc_html($phone); ?><?php if($fax): ?> | Fax: <?php echo esc_html($fax); ?><?php endif; ?><br>E-mail: <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a><br>Website: <a href="<?php echo esc_url($website_url); ?>"><?php echo esc_html($website_label); ?></a></div><div class="social-footer"><?php foreach(['fa.png','tv.png','tw.png','yo.png'] as $i): if(file_exists(get_template_directory().'/assets/images/ui/'.$i)): ?><img src="<?php echo esc_url(get_template_directory_uri().'/assets/images/ui/'.$i); ?>" alt=""><?php endif; endforeach; ?><div class="credit"><?php echo esc_html(pvdmcn_demo_opt('footer_credit','Original site design: iColor')); ?></div></div></footer></div><?php wp_footer(); ?></body></html>
