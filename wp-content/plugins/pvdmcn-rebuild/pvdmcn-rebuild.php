<?php
/**
 * Plugin Name: PVDMCN Rebuild Toolkit
 * Description: Đồng bộ cấu trúc, nội dung phục dựng và tài liệu lịch sử cho bản rebuild pvdmcn.com.vn.
 * Version: 0.6.0
 * Author: PVDMCN Recovery Project
 */
if (!defined('ABSPATH')) exit;

define('PVDMCN_REBUILD_VERSION', '0.6.0');
define('PVDMCN_REBUILD_DIR', plugin_dir_path(__FILE__));

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

register_activation_hook(__FILE__, function(){
    pvdmcn_register_content_types();
    pvdmcn_sync_all();
});

add_action('admin_init', function(){
    if (!current_user_can('manage_options')) return;
    if (get_option('pvdmcn_rebuild_version') !== PVDMCN_REBUILD_VERSION) {
        pvdmcn_sync_all();
    }
});

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

function pvdmcn_upsert_post($row, $post_type='page') {
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
    if ($id) {
        $payload['ID'] = $id;
        $result = wp_update_post(wp_slash($payload), true);
    } else {
        $result = wp_insert_post(wp_slash($payload), true);
    }
    if (is_wp_error($result)) return 0;
    $id=(int)$result;
    update_post_meta($id,'_pvdmcn_recovery_key',$row['key']);
    update_post_meta($id,'_pvdmcn_managed',1);
    update_post_meta($id,'_pvdmcn_recovery_status',$row['status'] ?? 'UNKNOWN');
    if (!empty($row['old_url'])) update_post_meta($id,'_pvdmcn_old_url',$row['old_url']);
    if (!empty($row['source_urls'])) update_post_meta($id,'_pvdmcn_source_urls',$row['source_urls']);
    return $id;
}

function pvdmcn_sync_pages() {
    $rows=pvdmcn_json('pages.json'); $ids=[];
    foreach($rows as $row) $ids[$row['key']]=pvdmcn_upsert_post($row,'page');
    foreach($rows as $row) {
        if (empty($row['parent_key']) || empty($ids[$row['key']]) || empty($ids[$row['parent_key']])) continue;
        wp_update_post(['ID'=>$ids[$row['key']], 'post_parent'=>$ids[$row['parent_key']]]);
    }
    if (!empty($ids['home'])) { update_option('show_on_front','page'); update_option('page_on_front',$ids['home']); }
    return $ids;
}

function pvdmcn_sync_posts() {
    $ids=[]; foreach(pvdmcn_json('posts.json') as $row) $ids[] = pvdmcn_upsert_post($row,'post'); return array_filter($ids);
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
    // Giữ đúng logic site cũ: nếu đã khôi phục được link Google Drive thì ưu tiên link đó.
    $old = $row['old_url'] ?? '';
    if ($old && strpos($old, 'drive.google.com') !== false) return $old;
    if (!empty($row['file_url'])) return $row['file_url'];
    return '';
}

function pvdmcn_document_url_from_post($post_id) {
    $drive = get_post_meta($post_id, '_pvdmcn_drive_url', true);
    if ($drive) return $drive;
    $primary = get_post_meta($post_id, '_pvdmcn_primary_url', true);
    if ($primary) return $primary;
    $fallback = get_post_meta($post_id, '_pvdmcn_fallback_url', true);
    return $fallback ?: get_permalink($post_id);
}

function pvdmcn_sync_documents() {
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

        // Không chép nội dung PDF vào WordPress. Bài tài liệu chỉ giữ đúng một link.
        $body=$primary_url
            ? '<p><a class="pvdmcn-document-link" href="'.esc_url($primary_url).'" target="_blank" rel="noopener">'.esc_html($row['title']).'</a></p>'
            : '';

        $payload=[
            'key'=>$row['key'],'title'=>$row['title'],'slug'=>$row['slug'],'date'=>$row['date'] ?? '',
            'status'=>$row['status'] ?? 'UNKNOWN','old_url'=>$row['old_url'] ?? '',
            'source_urls'=>array_values(array_filter([$row['old_url'] ?? '',$row['file_url'] ?? '',$row['source_url'] ?? ''])),
            'content_html'=>$body,'excerpt'=>''
        ];
        $newid=pvdmcn_upsert_post($payload,'pvdmcn_document'); if (!$newid) continue;
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

function pvdmcn_sync_menu($page_ids) {
    $name='PVDMCN Main Menu'; $menu=wp_get_nav_menu_object($name); $menu_id=$menu?(int)$menu->term_id:wp_create_nav_menu($name);
    if (is_wp_error($menu_id)) return 0;
    foreach((array)wp_get_nav_menu_items($menu_id) as $item) wp_delete_post($item->ID,true);
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
    return $menu_id;
}

function pvdmcn_sync_all() {
    $pages=pvdmcn_sync_pages(); $posts=pvdmcn_sync_posts(); $docs=pvdmcn_sync_documents(); $menu=pvdmcn_sync_menu($pages); flush_rewrite_rules();
    update_option('pvdmcn_rebuild_version',PVDMCN_REBUILD_VERSION);
    return ['pages'=>count(array_filter($pages)),'posts'=>count($posts),'documents'=>$docs,'menu_id'=>$menu];
}

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

// Nếu ai truy cập permalink của CPT tài liệu, chuyển thẳng sang tài liệu gốc.
add_action('template_redirect',function(){
    if (!is_singular('pvdmcn_document')) return;
    $url=pvdmcn_document_url_from_post(get_queried_object_id());
    if ($url && $url !== get_permalink(get_queried_object_id())) {
        wp_redirect($url,302,'PVDMCN-Rebuild');
        exit;
    }
});


add_action('admin_menu',function(){ add_management_page('PVDMCN Rebuild','PVDMCN Rebuild','manage_options','pvdmcn-rebuild','pvdmcn_admin'); });
function pvdmcn_admin(){
    if(!current_user_can('manage_options'))return; $msg='';
    if(!empty($_POST['pvdmcn_sync']) && check_admin_referer('pvdmcn_sync')){ $r=pvdmcn_sync_all(); $msg=sprintf('Đã sync %d page, %d bài, %d tài liệu.',$r['pages'],$r['posts'],$r['documents']); }
    echo '<div class="wrap"><h1>PVDMCN Rebuild Toolkit</h1>';
    if($msg) echo '<div class="notice notice-success"><p>'.esc_html($msg).'</p></div>';
    echo '<p>Version '.esc_html(PVDMCN_REBUILD_VERSION).'. Sync chỉ quản lý nội dung có meta <code>_pvdmcn_recovery_key</code>.</p><form method="post">'; wp_nonce_field('pvdmcn_sync'); echo '<input type="hidden" name="pvdmcn_sync" value="1">'; submit_button('Đồng bộ dữ liệu phục dựng'); echo '</form></div>';
}

if(defined('WP_CLI') && WP_CLI){
    WP_CLI::add_command('pvdmcn sync',function(){ $r=pvdmcn_sync_all(); WP_CLI::success(sprintf('Synced %d pages, %d posts, %d documents; menu %d',$r['pages'],$r['posts'],$r['documents'],$r['menu_id'])); });
}
