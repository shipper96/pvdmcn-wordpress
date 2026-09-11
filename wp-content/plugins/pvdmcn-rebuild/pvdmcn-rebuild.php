<?php
/**
 * Plugin Name: PVDMCN Rebuild Toolkit
 * Description: Đồng bộ cấu trúc, nội dung phục dựng, tài liệu lịch sử và cấu hình giao diện cho bản rebuild pvdmcn.com.vn.
 * Version: 0.7.0
 * Author: PVDMCN Recovery Project
 */
if (!defined('ABSPATH')) exit;

define('PVDMCN_REBUILD_VERSION', '0.7.0');
define('PVDMCN_REBUILD_DIR', plugin_dir_path(__FILE__));

/* -------------------------------------------------------------------------
 * Content model
 * ---------------------------------------------------------------------- */
function pvdmcn_register_content_types() {
    register_post_type('pvdmcn_document', [
        'labels' => [
            'name' => 'Tài liệu PVDMCN', 'singular_name' => 'Tài liệu',
            'add_new_item' => 'Thêm tài liệu', 'edit_item' => 'Sửa tài liệu'
        ],
        'public' => true, 'show_in_rest' => true, 'menu_icon' => 'dashicons-media-document',
        'supports' => ['title','editor','excerpt','custom-fields'],
        'has_archive' => 'tai-lieu', 'rewrite' => ['slug' => 'tai-lieu']
    ]);
    register_taxonomy('pvdmcn_doc_type', ['pvdmcn_document'], [
        'labels' => ['name'=>'Loại tài liệu','singular_name'=>'Loại tài liệu'],
        'public'=>true,'show_in_rest'=>true,'hierarchical'=>true,'rewrite'=>['slug'=>'loai-tai-lieu']
    ]);
    register_taxonomy('pvdmcn_doc_year', ['pvdmcn_document'], [
        'labels' => ['name'=>'Năm tài liệu','singular_name'=>'Năm'],
        'public'=>true,'show_in_rest'=>true,'hierarchical'=>false,'rewrite'=>['slug'=>'nam-tai-lieu']
    ]);
}
add_action('init', 'pvdmcn_register_content_types');

/* -------------------------------------------------------------------------
 * Editable site settings (CMS ownership)
 * ---------------------------------------------------------------------- */
function pvdmcn_default_site_settings() {
    return [
        'company_name' => 'CÔNG TY CỔ PHẦN HÓA PHẨM DẦU KHÍ DMC - MIỀN BẮC',
        'address' => 'Thôn Tế Xuyên - xã Đình Xuyên - Gia Lâm - Hà Nội',
        'phone' => '024.3827.1483',
        'fax' => '024.3878.0902',
        'email' => 'dmcmb@pvdmcn.com.vn',
        'website_label' => 'pvdmcn.com.vn',
        'website_url' => 'https://pvdmcn.com.vn/',
        'mail_url' => 'http://mail.pvdmcn.com.vn:3000/',
        'footer_credit' => 'Original site design: iColor',
        'logo_id' => 0,
        'slider_1_id' => 0,
        'slider_2_id' => 0,
        'slider_3_id' => 0,
        'show_recovery_notice' => 1,
        'recovery_notice' => 'Bản demo phục dựng: giao diện dựa trên HTML/CSS và tài nguyên còn lại của website Joomla cũ; nội dung được khôi phục dần từ tài liệu gốc.',
        'panel_news_title' => 'Tin tức / Công bố mới phục hồi',
        'panel_documents_title' => 'Tài liệu doanh nghiệp',
        'panel_products_title' => 'Product',
        'panel_ir_title' => 'Investor Relations',
        'panel_activities_title' => 'Activities',
    ];
}

function pvdmcn_get_site_settings() {
    $saved = get_option('pvdmcn_site_settings', []);
    if (!is_array($saved)) $saved = [];
    return array_merge(pvdmcn_default_site_settings(), $saved);
}

function pvdmcn_ensure_site_settings() {
    $saved = get_option('pvdmcn_site_settings', []);
    if (!is_array($saved)) $saved = [];
    $merged = array_merge(pvdmcn_default_site_settings(), $saved);
    if ($merged !== $saved) update_option('pvdmcn_site_settings', $merged, false);
    return $merged;
}

function pvdmcn_sanitize_site_settings($input) {
    $current = pvdmcn_get_site_settings();
    $out = $current;
    $text_fields = [
        'company_name','address','phone','fax','website_label','footer_credit',
        'panel_news_title','panel_documents_title','panel_products_title','panel_ir_title','panel_activities_title'
    ];
    foreach ($text_fields as $key) if (array_key_exists($key, $input)) $out[$key] = sanitize_text_field($input[$key]);
    if (array_key_exists('email', $input)) $out['email'] = sanitize_email($input['email']);
    foreach (['website_url','mail_url'] as $key) if (array_key_exists($key, $input)) $out[$key] = esc_url_raw($input[$key]);
    foreach (['logo_id','slider_1_id','slider_2_id','slider_3_id'] as $key) if (array_key_exists($key, $input)) $out[$key] = absint($input[$key]);
    $out['show_recovery_notice'] = empty($input['show_recovery_notice']) ? 0 : 1;
    if (array_key_exists('recovery_notice', $input)) $out['recovery_notice'] = sanitize_textarea_field($input['recovery_notice']);
    return $out;
}

/* -------------------------------------------------------------------------
 * Seed protection: Git may seed defaults, CMS edits remain owned by CMS.
 * ---------------------------------------------------------------------- */
function pvdmcn_json($name) {
    $path = PVDMCN_REBUILD_DIR . 'data/' . $name;
    if (!is_file($path)) return [];
    $data = json_decode(file_get_contents($path), true);
    return is_array($data) ? $data : [];
}

function pvdmcn_find_managed($key, $post_type) {
    $ids = get_posts([
        'post_type'=>$post_type,'post_status'=>'any','posts_per_page'=>1,'fields'=>'ids',
        'meta_key'=>'_pvdmcn_recovery_key','meta_value'=>$key,'suppress_filters'=>true
    ]);
    return $ids ? (int)$ids[0] : 0;
}

function pvdmcn_post_edit_signature($post_id) {
    $p = get_post($post_id);
    if (!$p) return '';
    $state = [
        'title' => (string)$p->post_title,
        'slug' => (string)$p->post_name,
        'content' => (string)$p->post_content,
        'excerpt' => (string)$p->post_excerpt,
        'date' => substr((string)$p->post_date, 0, 10),
    ];
    return hash('sha256', wp_json_encode($state, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
}

function pvdmcn_managed_content_is_safe_to_update($post_id, $force=false) {
    if ($force) return true;
    if ((int)get_post_meta($post_id, '_pvdmcn_manual_protected', true) === 1) return false;
    $last_seed = get_post_meta($post_id, '_pvdmcn_seed_signature', true);
    if (!$last_seed) {
        // Existing content from <=0.6 has no signature. Preserve it conservatively.
        update_post_meta($post_id, '_pvdmcn_manual_protected', 1);
        return false;
    }
    if (!hash_equals((string)$last_seed, (string)pvdmcn_post_edit_signature($post_id))) {
        update_post_meta($post_id, '_pvdmcn_manual_protected', 1);
        return false;
    }
    return true;
}

function pvdmcn_upsert_post($row, $post_type='page', $force=false) {
    $id = pvdmcn_find_managed($row['key'], $post_type);
    $payload = [
        'post_type'=>$post_type,
        'post_status'=>'publish',
        'post_title'=>$row['title'],
        'post_name'=>$row['slug'],
        'post_content'=>$row['content_html'] ?? '',
        'post_excerpt'=>$row['excerpt'] ?? ''
    ];
    if (!empty($row['date'])) $payload['post_date'] = $row['date'] . ' 12:00:00';

    $did_write = false;
    if ($id) {
        if (pvdmcn_managed_content_is_safe_to_update($id, $force)) {
            $payload['ID'] = $id;
            $result = wp_update_post(wp_slash($payload), true);
            if (is_wp_error($result)) return 0;
            $id = (int)$result;
            $did_write = true;
        }
    } else {
        $result = wp_insert_post(wp_slash($payload), true);
        if (is_wp_error($result)) return 0;
        $id = (int)$result;
        $did_write = true;
    }

    update_post_meta($id,'_pvdmcn_recovery_key',$row['key']);
    update_post_meta($id,'_pvdmcn_managed',1);
    update_post_meta($id,'_pvdmcn_recovery_status',$row['status'] ?? 'UNKNOWN');
    if (!empty($row['old_url'])) update_post_meta($id,'_pvdmcn_old_url',$row['old_url']);
    if (!empty($row['source_urls'])) update_post_meta($id,'_pvdmcn_source_urls',$row['source_urls']);
    if ($did_write) {
        delete_post_meta($id, '_pvdmcn_manual_protected');
        update_post_meta($id, '_pvdmcn_seed_signature', pvdmcn_post_edit_signature($id));
    }
    return $id;
}

function pvdmcn_sync_pages($force=false) {
    $rows=pvdmcn_json('pages.json'); $ids=[];
    foreach($rows as $row) $ids[$row['key']]=pvdmcn_upsert_post($row,'page',$force);
    foreach($rows as $row) {
        if (empty($row['parent_key']) || empty($ids[$row['key']]) || empty($ids[$row['parent_key']])) continue;
        $id = $ids[$row['key']];
        if ($force || (int)get_post_meta($id,'_pvdmcn_manual_protected',true) !== 1) {
            wp_update_post(['ID'=>$id, 'post_parent'=>$ids[$row['parent_key']]]);
        }
    }
    if (!empty($ids['home'])) { update_option('show_on_front','page'); update_option('page_on_front',$ids['home']); }
    return $ids;
}

function pvdmcn_sync_posts($force=false) {
    $ids=[]; foreach(pvdmcn_json('posts.json') as $row) $ids[] = pvdmcn_upsert_post($row,'post',$force); return array_filter($ids);
}

function pvdmcn_ensure_term($taxonomy,$name) {
    $found=term_exists($name,$taxonomy); if ($found) return is_array($found)?(int)$found['term_id']:(int)$found;
    $r=wp_insert_term($name,$taxonomy,['slug'=>sanitize_title($name)]); return is_wp_error($r)?0:(int)$r['term_id'];
}

function pvdmcn_import_local_file($rel,$title) {
    $src=PVDMCN_REBUILD_DIR . ltrim($rel,'/'); if (!is_file($src)) return 0;
    $hash=sha1_file($src);
    $found=get_posts(['post_type'=>'attachment','post_status'=>'inherit','posts_per_page'=>1,'fields'=>'ids','meta_key'=>'_pvdmcn_file_hash','meta_value'=>$hash]);
    if ($found) return (int)$found[0];
    $upload=wp_upload_dir(); if (!empty($upload['error'])) return 0;
    wp_mkdir_p($upload['path']);
    $filename=wp_unique_filename($upload['path'],basename($src)); $dest=trailingslashit($upload['path']).$filename;
    if (!copy($src,$dest)) return 0;
    $ft=wp_check_filetype($filename,null);
    $id=wp_insert_attachment(['post_mime_type'=>$ft['type'] ?: 'application/octet-stream','post_title'=>$title,'post_status'=>'inherit'], $dest);
    if (is_wp_error($id)) return 0;
    if (strpos((string)$ft['type'], 'image/') === 0) {
        require_once ABSPATH.'wp-admin/includes/image.php';
        $meta=wp_generate_attachment_metadata($id,$dest); if ($meta) wp_update_attachment_metadata($id,$meta);
    }
    update_post_meta($id,'_pvdmcn_file_hash',$hash); update_post_meta($id,'_pvdmcn_managed',1);
    return (int)$id;
}

function pvdmcn_primary_document_url($row) {
    $old = $row['old_url'] ?? '';
    if ($old && strpos($old, 'drive.google.com') !== false) return $old;
    if (!empty($row['file_url'])) return $row['file_url'];
    return '';
}

function pvdmcn_document_url_from_post($post_id) {
    $override = get_post_meta($post_id, '_pvdmcn_user_document_url', true);
    if ($override) return $override;
    $drive = get_post_meta($post_id, '_pvdmcn_drive_url', true);
    if ($drive) return $drive;
    $primary = get_post_meta($post_id, '_pvdmcn_primary_url', true);
    if ($primary) return $primary;
    $fallback = get_post_meta($post_id, '_pvdmcn_fallback_url', true);
    return $fallback ?: get_permalink($post_id);
}

function pvdmcn_sync_documents($force=false) {
    $count=0;
    foreach(pvdmcn_json('documents.json') as $row) {
        $attachment_id=0;
        $fallback_url=$row['file_url'] ?? '';
        if (!empty($row['local_file'])) {
            $attachment_id=pvdmcn_import_local_file($row['local_file'],$row['title']);
            if ($attachment_id) $fallback_url=wp_get_attachment_url($attachment_id);
        }
        $primary_url=pvdmcn_primary_document_url($row);
        if (!$primary_url) $primary_url=$fallback_url;
        $body=$primary_url
            ? '<p><a class="pvdmcn-document-link" href="'.esc_url($primary_url).'" target="_blank" rel="noopener">'.esc_html($row['title']).'</a></p>'
            : '';
        $payload=[
            'key'=>$row['key'],'title'=>$row['title'],'slug'=>$row['slug'],'date'=>$row['date'] ?? '',
            'status'=>$row['status'] ?? 'UNKNOWN','old_url'=>$row['old_url'] ?? '',
            'source_urls'=>array_values(array_filter([$row['old_url'] ?? '',$row['file_url'] ?? '',$row['source_url'] ?? ''])),
            'content_html'=>$body,'excerpt'=>''
        ];
        $newid=pvdmcn_upsert_post($payload,'pvdmcn_document',$force); if (!$newid) continue;
        if ($attachment_id) update_post_meta($newid,'_pvdmcn_attachment_id',$attachment_id);
        if ($primary_url) update_post_meta($newid,'_pvdmcn_primary_url',$primary_url);
        if ($fallback_url) update_post_meta($newid,'_pvdmcn_fallback_url',$fallback_url);
        if (!empty($row['old_url']) && strpos($row['old_url'],'drive.google.com') !== false) {
            update_post_meta($newid,'_pvdmcn_drive_url',$row['old_url']);
        } else {
            delete_post_meta($newid,'_pvdmcn_drive_url');
        }
        if (!empty($row['type'])) { pvdmcn_ensure_term('pvdmcn_doc_type',$row['type']); wp_set_object_terms($newid,[$row['type']],'pvdmcn_doc_type',false); }
        if (!empty($row['year'])) { pvdmcn_ensure_term('pvdmcn_doc_year',(string)$row['year']); wp_set_object_terms($newid,[(string)$row['year']],'pvdmcn_doc_year',false); }
        $count++;
    }
    return $count;
}

function pvdmcn_sync_menu($page_ids, $force=false) {
    $name='PVDMCN Main Menu'; $menu=wp_get_nav_menu_object($name); $menu_id=$menu?(int)$menu->term_id:wp_create_nav_menu($name);
    if (is_wp_error($menu_id)) return 0;
    $existing=(array)wp_get_nav_menu_items($menu_id);
    if ($existing && !$force) {
        update_option('pvdmcn_menu_seeded',1,false);
        $locations=get_theme_mod('nav_menu_locations',[]); if(empty($locations['primary'])){$locations['primary']=$menu_id;set_theme_mod('nav_menu_locations',$locations);}
        return $menu_id;
    }
    foreach($existing as $item) wp_delete_post($item->ID,true);
    $menu_ids=[];
    foreach(pvdmcn_json('menu.json') as $row) {
        if (empty($page_ids[$row['page_key']])) continue;
        $parent=(!empty($row['parent_key']) && !empty($menu_ids[$row['parent_key']]))?$menu_ids[$row['parent_key']]:0;
        $mid=wp_update_nav_menu_item($menu_id,0,[
            'menu-item-title'=>$row['label'],'menu-item-object'=>'page','menu-item-object-id'=>$page_ids[$row['page_key']],
            'menu-item-type'=>'post_type','menu-item-status'=>'publish','menu-item-parent-id'=>$parent
        ]);
        if (!is_wp_error($mid)) $menu_ids[$row['key']]=(int)$mid;
    }
    $locations=get_theme_mod('nav_menu_locations',[]); $locations['primary']=$menu_id; set_theme_mod('nav_menu_locations',$locations);
    update_option('pvdmcn_menu_seeded',1,false);
    return $menu_id;
}

function pvdmcn_sync_all($force_content=false, $force_menu=false) {
    pvdmcn_ensure_site_settings();
    $pages=pvdmcn_sync_pages($force_content);
    $posts=pvdmcn_sync_posts($force_content);
    $docs=pvdmcn_sync_documents($force_content);
    $menu=pvdmcn_sync_menu($pages,$force_menu);
    flush_rewrite_rules();
    update_option('pvdmcn_rebuild_version',PVDMCN_REBUILD_VERSION);
    return ['pages'=>count(array_filter($pages)),'posts'=>count($posts),'documents'=>$docs,'menu_id'=>$menu];
}

register_activation_hook(__FILE__, function(){
    pvdmcn_register_content_types();
    pvdmcn_ensure_site_settings();
    pvdmcn_sync_all(false,false);
});

add_action('admin_init', function(){
    pvdmcn_ensure_site_settings();
    if (!current_user_can('manage_options')) return;
    if (get_option('pvdmcn_rebuild_version') !== PVDMCN_REBUILD_VERSION) {
        pvdmcn_sync_all(false,false);
    }
});

/* -------------------------------------------------------------------------
 * Front-end document behavior
 * ---------------------------------------------------------------------- */
add_shortcode('pvdmcn_documents',function($atts){
    $a=shortcode_atts(['year'=>'','type'=>'','limit'=>100],$atts); $tax=[];
    if($a['year']) $tax[]=['taxonomy'=>'pvdmcn_doc_year','field'=>'name','terms'=>$a['year']];
    if($a['type']) $tax[]=['taxonomy'=>'pvdmcn_doc_type','field'=>'name','terms'=>$a['type']];
    if(count($tax)>1) $tax['relation']='AND';
    $q=new WP_Query(['post_type'=>'pvdmcn_document','post_status'=>'publish','posts_per_page'=>(int)$a['limit'],'orderby'=>'date','order'=>'DESC','tax_query'=>$tax?:null]);
    ob_start(); echo '<ul class="documents-list">';
    while($q->have_posts()){
        $q->the_post();
        $url=pvdmcn_document_url_from_post(get_the_ID());
        echo '<li><a href="'.esc_url($url).'" target="_blank" rel="noopener">'.esc_html(get_the_title()).'</a></li>';
    }
    wp_reset_postdata(); echo '</ul>'; return ob_get_clean();
});

add_action('template_redirect',function(){
    if (!is_singular('pvdmcn_document')) return;
    $url=pvdmcn_document_url_from_post(get_queried_object_id());
    if ($url && $url !== get_permalink(get_queried_object_id())) {
        wp_redirect($url,302,'PVDMCN-Rebuild');
        exit;
    }
});

/* -------------------------------------------------------------------------
 * Document link override meta box
 * ---------------------------------------------------------------------- */
add_action('add_meta_boxes', function(){
    add_meta_box('pvdmcn-document-link','PVDMCN · Link tài liệu','pvdmcn_document_link_metabox','pvdmcn_document','side','high');
});

function pvdmcn_document_link_metabox($post){
    wp_nonce_field('pvdmcn_save_document_link','pvdmcn_document_link_nonce');
    $override=get_post_meta($post->ID,'_pvdmcn_user_document_url',true);
    $drive=get_post_meta($post->ID,'_pvdmcn_drive_url',true);
    $fallback=get_post_meta($post->ID,'_pvdmcn_fallback_url',true);
    echo '<p><label for="pvdmcn_user_document_url"><strong>Link hiển thị</strong></label></p>';
    echo '<input type="url" class="widefat" id="pvdmcn_user_document_url" name="pvdmcn_user_document_url" value="'.esc_attr($override).'" placeholder="https://drive.google.com/...">';
    echo '<p class="description">Để trống = dùng link Drive/file phục hồi từ seed. Điền URL tại đây sẽ luôn được ưu tiên và không bị Git sync ghi đè.</p>';
    echo '<hr><p><strong>Drive phục hồi:</strong><br><small>'.($drive?'<a href="'.esc_url($drive).'" target="_blank">'.esc_html($drive).'</a>':'—').'</small></p>';
    echo '<p><strong>Fallback:</strong><br><small>'.($fallback?'<a href="'.esc_url($fallback).'" target="_blank">'.esc_html($fallback).'</a>':'—').'</small></p>';
}

add_action('save_post_pvdmcn_document', function($post_id){
    if (!isset($_POST['pvdmcn_document_link_nonce']) || !wp_verify_nonce($_POST['pvdmcn_document_link_nonce'],'pvdmcn_save_document_link')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post',$post_id)) return;
    $url = isset($_POST['pvdmcn_user_document_url']) ? esc_url_raw(wp_unslash($_POST['pvdmcn_user_document_url'])) : '';
    if ($url) update_post_meta($post_id,'_pvdmcn_user_document_url',$url); else delete_post_meta($post_id,'_pvdmcn_user_document_url');
}, 10, 1);

/* -------------------------------------------------------------------------
 * Admin: PVDMCN settings + sync
 * ---------------------------------------------------------------------- */
add_action('admin_menu',function(){
    add_menu_page('PVDMCN','PVDMCN','manage_options','pvdmcn-settings','pvdmcn_settings_page','dashicons-building',3);
    add_submenu_page('pvdmcn-settings','Cấu hình giao diện','Cấu hình giao diện','manage_options','pvdmcn-settings','pvdmcn_settings_page');
    add_submenu_page('pvdmcn-settings','Đồng bộ phục dựng','Đồng bộ phục dựng','manage_options','pvdmcn-rebuild','pvdmcn_admin');
});

add_action('admin_enqueue_scripts', function($hook){
    if (strpos((string)$hook,'pvdmcn') === false) return;
    wp_enqueue_media();
});

function pvdmcn_media_setting_row($settings,$key,$label,$fallback='') {
    $id=absint($settings[$key] ?? 0);
    $url=$id ? wp_get_attachment_image_url($id,'medium') : '';
    if (!$url && $fallback) $url=get_stylesheet_directory_uri().'/'.$fallback;
    echo '<tr><th scope="row">'.esc_html($label).'</th><td>';
    echo '<input type="hidden" id="'.esc_attr($key).'" name="pvdmcn_settings['.esc_attr($key).']" value="'.esc_attr($id).'">';
    echo '<img id="'.esc_attr($key).'-preview" src="'.esc_url($url).'" alt="" style="display:block;max-width:420px;max-height:150px;margin:0 0 8px;border:1px solid #ccd0d4;background:#fff">';
    echo '<button type="button" class="button pvdmcn-media-pick" data-target="'.esc_attr($key).'">Chọn ảnh</button> ';
    echo '<button type="button" class="button pvdmcn-media-clear" data-target="'.esc_attr($key).'">Dùng ảnh mặc định</button>';
    echo '</td></tr>';
}

function pvdmcn_settings_page(){
    if(!current_user_can('manage_options')) return;
    $msg='';
    if (!empty($_POST['pvdmcn_save_settings']) && check_admin_referer('pvdmcn_save_settings')) {
        $input = isset($_POST['pvdmcn_settings']) && is_array($_POST['pvdmcn_settings']) ? wp_unslash($_POST['pvdmcn_settings']) : [];
        update_option('pvdmcn_site_settings', pvdmcn_sanitize_site_settings($input), false);
        $msg='Đã lưu cấu hình giao diện.';
    }
    $s=pvdmcn_get_site_settings();
    echo '<div class="wrap"><h1>PVDMCN · Cấu hình giao diện</h1>';
    echo '<p>Các giá trị tại đây nằm trong database WordPress và <strong>không bị Git pull ghi đè</strong>. Code/layout vẫn được quản lý bằng GitHub.</p>';
    if($msg) echo '<div class="notice notice-success is-dismissible"><p>'.esc_html($msg).'</p></div>';
    echo '<form method="post">'; wp_nonce_field('pvdmcn_save_settings'); echo '<input type="hidden" name="pvdmcn_save_settings" value="1">';
    echo '<h2>Nhận diện & slideshow</h2><table class="form-table" role="presentation">';
    pvdmcn_media_setting_row($s,'logo_id','Logo','assets/images/logo.png');
    pvdmcn_media_setting_row($s,'slider_1_id','Slide 1','assets/images/hero/01.jpg');
    pvdmcn_media_setting_row($s,'slider_2_id','Slide 2','assets/images/hero/02.jpg');
    pvdmcn_media_setting_row($s,'slider_3_id','Slide 3','assets/images/hero/03.jpg');
    echo '</table>';
    echo '<h2>Thông tin công ty</h2><table class="form-table" role="presentation">';
    $fields=[
        'company_name'=>'Tên công ty','address'=>'Địa chỉ','phone'=>'Điện thoại','fax'=>'Fax','email'=>'Email',
        'website_label'=>'Tên website hiển thị','website_url'=>'URL website','mail_url'=>'URL Mail','footer_credit'=>'Dòng credit footer'
    ];
    foreach($fields as $key=>$label){
        $type=$key==='email'?'email':(in_array($key,['website_url','mail_url'],true)?'url':'text');
        echo '<tr><th scope="row"><label for="'.esc_attr($key).'">'.esc_html($label).'</label></th><td><input class="regular-text" type="'.esc_attr($type).'" id="'.esc_attr($key).'" name="pvdmcn_settings['.esc_attr($key).']" value="'.esc_attr($s[$key] ?? '').'"></td></tr>';
    }
    echo '</table>';
    echo '<h2>Trang chủ & sidebar</h2><table class="form-table" role="presentation">';
    foreach([
        'panel_news_title'=>'Tiêu đề khối Tin tức','panel_documents_title'=>'Tiêu đề khối Tài liệu','panel_products_title'=>'Tiêu đề khối Sản phẩm',
        'panel_ir_title'=>'Tiêu đề khối Quan hệ cổ đông','panel_activities_title'=>'Tiêu đề khối Hoạt động'
    ] as $key=>$label){
        echo '<tr><th scope="row"><label for="'.esc_attr($key).'">'.esc_html($label).'</label></th><td><input class="regular-text" type="text" id="'.esc_attr($key).'" name="pvdmcn_settings['.esc_attr($key).']" value="'.esc_attr($s[$key] ?? '').'"></td></tr>';
    }
    echo '<tr><th scope="row">Thông báo bản demo</th><td><label><input type="checkbox" name="pvdmcn_settings[show_recovery_notice]" value="1" '.checked(!empty($s['show_recovery_notice']),true,false).'> Hiển thị</label><p><textarea class="large-text" rows="3" name="pvdmcn_settings[recovery_notice]">'.esc_textarea($s['recovery_notice'] ?? '').'</textarea></p></td></tr>';
    echo '</table>';
    submit_button('Lưu cấu hình'); echo '</form>';
    ?>
    <script>
    jQuery(function($){
      $('.pvdmcn-media-pick').on('click',function(e){
        e.preventDefault(); var target=$(this).data('target');
        var frame=wp.media({title:'Chọn ảnh',button:{text:'Dùng ảnh này'},multiple:false});
        frame.on('select',function(){var a=frame.state().get('selection').first().toJSON(); $('#'+target).val(a.id); $('#'+target+'-preview').attr('src',a.url).show();});
        frame.open();
      });
      $('.pvdmcn-media-clear').on('click',function(e){e.preventDefault(); var target=$(this).data('target'); $('#'+target).val('0'); $('#'+target+'-preview').css('opacity','.45');});
    });
    </script>
    <?php
    echo '</div>';
}

function pvdmcn_admin(){
    if(!current_user_can('manage_options'))return; $msg='';
    if(!empty($_POST['pvdmcn_sync']) && check_admin_referer('pvdmcn_sync')){
        $force_content=!empty($_POST['force_content']); $force_menu=!empty($_POST['force_menu']);
        $r=pvdmcn_sync_all($force_content,$force_menu);
        $msg=sprintf('Đã sync %d page, %d bài, %d tài liệu.',$r['pages'],$r['posts'],$r['documents']);
    }
    echo '<div class="wrap"><h1>PVDMCN · Đồng bộ phục dựng</h1>';
    if($msg) echo '<div class="notice notice-success"><p>'.esc_html($msg).'</p></div>';
    echo '<p>Version '.esc_html(PVDMCN_REBUILD_VERSION).'. Từ v0.7, nội dung đã sửa trong CMS được bảo vệ khỏi Git sync.</p>';
    echo '<form method="post">'; wp_nonce_field('pvdmcn_sync'); echo '<input type="hidden" name="pvdmcn_sync" value="1">';
    echo '<p><label><input type="checkbox" name="force_content" value="1"> <strong>Ghi đè nội dung CMS bằng seed</strong> (chỉ dùng khi thật sự muốn khôi phục bản seed)</label></p>';
    echo '<p><label><input type="checkbox" name="force_menu" value="1"> <strong>Tạo lại menu theo seed</strong> (sẽ xóa chỉnh sửa menu thủ công)</label></p>';
    submit_button('Đồng bộ dữ liệu phục dựng'); echo '</form></div>';
}

if(defined('WP_CLI') && WP_CLI){
    WP_CLI::add_command('pvdmcn sync',function($args,$assoc){
        $force_content=!empty($assoc['force-content']); $force_menu=!empty($assoc['force-menu']);
        $r=pvdmcn_sync_all($force_content,$force_menu);
        WP_CLI::success(sprintf('Synced %d pages, %d posts, %d documents; menu %d',$r['pages'],$r['posts'],$r['documents'],$r['menu_id']));
        if(!$force_content) WP_CLI::log('CMS-edited content was preserved. Use --force-content only when you want seed to overwrite it.');
        if(!$force_menu) WP_CLI::log('Existing menu was preserved. Use --force-menu to rebuild it from seed.');
    });
}
