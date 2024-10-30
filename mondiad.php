<?php
/**
 * Plugin Name:       Mondiad Advertising
 * Description:       Place and manage advertising on your site. Take full control of the monetization of your website and maximize your revenue with our service.
 * Author:            mondiad
 * Author URI:        https://mondiad.com/
 * Version:           1.1.4
 * Requires at least: 5.2
 * Tested up to:      6.6
 * Requires PHP:      5.4
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
*/

namespace Mondiad;

require_once 'inc/mondiad-class.php';
require_once 'inc/templates/meta-box.php';
require_once 'inc/templates/login.php';
require_once 'inc/templates/websites.php';
require_once 'inc/templates/adzones.php';
require_once 'inc/templates/settings.php';
require_once 'inc/templates/php-helper.php';
require_once 'inc/templates/layout.php';
require_once 'inc/models/website.php';
require_once 'inc/models/adzone.php';
require_once 'inc/models/login-response.php';

add_action('init', __NAMESPACE__.'\\init', 0);
add_action('add_meta_boxes', __NAMESPACE__.'\\add_metaboxes');
add_action('admin_menu', __NAMESPACE__.'\\add_menu');
add_action('admin_enqueue_scripts', __NAMESPACE__.'\\register_static');
add_action('wp_enqueue_scripts', __NAMESPACE__.'\\register_static_public');
register_uninstall_hook(__FILE__ , __NAMESPACE__ . '\\uninstall_plugin');


function init() {
  $mondiad = Mondiad::getInstance();

  $role = get_role('administrator');
  if (!empty($role))
    $role->add_cap('mondiad_admin_access');

  // on save page hook
  add_action('save_post', array($mondiad, 'on_save_page'));
  // add scripts to call advertising
  add_action('wp_print_footer_scripts', array($mondiad, 'add_inline_scripts'));
  // handle classic push script
  add_action('parse_request', array($mondiad, 'handle_classic_root_js_request'));
  // provide Mondiad js variables
  add_action('admin_print_footer_scripts', array($mondiad, 'setup_js_vars'));

  // post ajax
  add_action('wp_ajax_' . $mondiad::LOGIN, array($mondiad, 'login_async'));
  add_action('wp_ajax_' . $mondiad::LOGOUT, array($mondiad, 'logout_async'));

  add_action('wp_ajax_' . $mondiad::SITE_CHANGE, array($mondiad, 'change_website_async'));
  add_action('wp_ajax_' . $mondiad::SITE_SELECT, array($mondiad, 'select_website_async'));
  add_action('wp_ajax_' . $mondiad::SITE_SEARCH, array($mondiad, 'search_website_async'));

  add_action('wp_ajax_' . $mondiad::AD_CHANGE_ACTIVITY_IN, array($mondiad, 'change_inpage_enabled_async'));
  add_action('wp_ajax_' . $mondiad::AD_CHANGE_ACTIVITY_CLASSIC, array($mondiad, 'change_classic_enabled_async'));
  add_action('wp_ajax_' . $mondiad::AD_CHANGE_ACTIVITY_NATIVE, array($mondiad, 'change_native_enabled_async'));
  add_action('wp_ajax_' . $mondiad::AD_CHANGE_ACTIVITY_BANNER, array($mondiad, 'change_banner_enabled_async'));

  add_action('wp_ajax_' . $mondiad::AD_SELECT_IN, array($mondiad, 'select_inpage_ad_async'));
  add_action('wp_ajax_' . $mondiad::AD_SELECT_CLASSIC, array($mondiad, 'select_classic_ad_async'));

  add_shortcode( 'mondiad-native-ad', array($mondiad, 'get_native_ad_shortcode_replacer'));
  add_shortcode( 'mondiad-banner-ad', array($mondiad, 'get_banner_ad_shortcode_replacer'));

  if (function_exists('register_block_type_from_metadata')) {
    register_block_type_from_metadata(__DIR__);
  }
}

function add_metaboxes() {
  $mondiad = Mondiad::getInstance();
  if ($mondiad->is_authorised() && $mondiad->is_website_selected()) {
    add_meta_box('mondiad_page_meta_box', 'Mondiad', __NAMESPACE__ . '\\TemplateMetabox::metabox', ['page', 'post'], 'advanced', 'high');
  }
}

function add_menu() {
  $mondiad_plugin_url = plugin_dir_url(__FILE__);
  $mondiad = Mondiad::getInstance();

  if ($mondiad->is_authorised()) {
    if ($mondiad->is_website_selected()) {
      add_menu_page('Mondiad websites','Mondiad', 'mondiad_admin_access', $mondiad::INDEX_PAGE,
        __NAMESPACE__.'\\TemplateAdzones::adzones',$mondiad_plugin_url.'img/mondiad16.png');
      add_submenu_page($mondiad::INDEX_PAGE, 'Mondiad adzones', 'Adzone',
        'mondiad_admin_access', $mondiad::INDEX_PAGE, __NAMESPACE__.'\\TemplateAdzones::adzones');
    } else {
      add_menu_page('Mondiad websites','Mondiad', 'mondiad_admin_access', $mondiad::INDEX_PAGE,
        __NAMESPACE__.'\\TemplateWebsites::websites',$mondiad_plugin_url.'img/mondiad16.png');
      add_submenu_page($mondiad::INDEX_PAGE, 'Mondiad websites', 'Website',
        'mondiad_admin_access', $mondiad::INDEX_PAGE,__NAMESPACE__.'\\TemplateWebsites::websites');
    }
    add_submenu_page($mondiad::INDEX_PAGE, 'Mondiad settings', 'Settings',
      'mondiad_admin_access', $mondiad::SETTINGS_PAGE, __NAMESPACE__.'\\TemplateSettings::settings');
  } else {
    add_menu_page('Mondiad log in','Mondiad','mondiad_admin_access', $mondiad::INDEX_PAGE,
      __NAMESPACE__.'\\TemplateLogin::login',$mondiad_plugin_url.'img/mondiad16.png');
  }
}

function register_static() {
  $mondiad_plugin_url = plugin_dir_url(__FILE__);

  wp_register_style( 'mondiad_main_style', $mondiad_plugin_url . 'js-css/style.css' );
  wp_enqueue_style( 'mondiad_main_style', $mondiad_plugin_url . 'js-css/style.css' );

  wp_register_script( 'mondiad_main_script', $mondiad_plugin_url . 'js-css/script.js' );
  wp_enqueue_script( 'mondiad_main_script', $mondiad_plugin_url . 'js-css/script.js' );

  wp_register_style( 'pnotify_style', $mondiad_plugin_url . 'node_modules/sweetalert2/dist/sweetalert2.css' );
  wp_enqueue_style( 'pnotify_style', $mondiad_plugin_url . 'node_modules/sweetalert2/dist/sweetalert2.css' );

  wp_register_script( 'pnotify_code', $mondiad_plugin_url . 'node_modules/sweetalert2/dist/sweetalert2.all.js' );
  wp_enqueue_script( 'pnotify_code', $mondiad_plugin_url . 'node_modules/sweetalert2/dist/sweetalert2.all.js' );

  wp_register_script( 'mondiad_ui', $mondiad_plugin_url . 'assets/js/mondiad-ui.js', ['wp-element'], time(), true);
  wp_enqueue_script( 'mondiad_ui', $mondiad_plugin_url . 'assets/js/mondiad-ui.js', ['wp-element'], time(), true);
}

function register_static_public() {
  $mondiad = Mondiad::getInstance();
  $static_url = $mondiad::API_STATIC_URL;
  if ($mondiad->is_native_enabled()) {
    wp_register_script('mondiad_native_script', "$static_url/native.js");
    wp_enqueue_script('mondiad_native_script', "$static_url/native.js");
  }
  if ($mondiad->is_banner_enabled()) {
    wp_register_script('mondiad_banner_script', "$static_url/banner.js");
    wp_enqueue_script('mondiad_banner_script', "$static_url/banner.js");
  }
}

function uninstall_plugin() {
  $mondiad = Mondiad::getInstance();
  $mondiad->clean_up(true);
}
