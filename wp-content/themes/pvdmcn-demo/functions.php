<?php
if (!defined('ABSPATH')) exit;
add_action('after_setup_theme', function(){
  add_theme_support('title-tag');
  add_theme_support('post-thumbnails');
  add_theme_support('html5',['search-form','gallery','caption','style','script']);
  register_nav_menus(['primary'=>'Menu chính']);
});
add_action('wp_enqueue_scripts', function(){wp_enqueue_style('pvdmcn-demo',get_stylesheet_uri(),[],wp_get_theme()->get('Version'));});
function pvdmcn_demo_status($id=null){$id=$id?:get_the_ID();return get_post_meta($id,'_pvdmcn_recovery_status',true);}
function pvdmcn_demo_docs($limit=10){
 if(!post_type_exists('pvdmcn_document')) return [];
 return get_posts(['post_type'=>'pvdmcn_document','post_status'=>'publish','posts_per_page'=>$limit,'orderby'=>'date','order'=>'DESC']);
}

function pvdmcn_demo_doc_url($id){
 if(function_exists('pvdmcn_document_url_from_post')) return pvdmcn_document_url_from_post($id);
 $u=get_post_meta($id,'_pvdmcn_drive_url',true);
 if(!$u) $u=get_post_meta($id,'_pvdmcn_primary_url',true);
 if(!$u) $u=get_post_meta($id,'_pvdmcn_fallback_url',true);
 return $u?:get_permalink($id);
}
