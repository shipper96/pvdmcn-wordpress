<?php
$slides=[
 ['key'=>'slider_1_id','fallback'=>'assets/images/hero/01.jpg','alt'=>'DMC Miền Bắc'],
 ['key'=>'slider_2_id','fallback'=>'assets/images/hero/02.jpg','alt'=>'Hoạt động DMC Miền Bắc'],
 ['key'=>'slider_3_id','fallback'=>'assets/images/hero/03.jpg','alt'=>'DMC Miền Bắc'],
];
?>
<div class="old-slider" aria-label="Hình ảnh DMC Miền Bắc"><?php foreach($slides as $slide): ?><div class="old-slide"><img src="<?php echo esc_url(pvdmcn_demo_media_url($slide['key'],$slide['fallback'])); ?>" alt="<?php echo esc_attr($slide['alt']); ?>"></div><?php endforeach; ?></div>
