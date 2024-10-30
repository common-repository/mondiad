<?php

namespace Mondiad;

require_once 'mondiad-api.php';
require_once 'mondiad-db.php';

class Mondiad {

  const MONDIAD_SITE_URL = 'https://publisher.mondiad.com';
  const SIGN_UP_URL = 'https://members.mondiad.com/registration?role=publisher';
  const API_STATIC_URL = 'https://ss.mrmnd.com';

  const WORKER_URL = Mondiad::API_STATIC_URL . '/worker.js';

  const STATIC_ROOT_URL = WP_PLUGIN_URL . '/mondiad/';

  const INDEX_PAGE = 'mondiad';
  const SETTINGS_PAGE = 'mondiad_settings';

  const METABOX_INPAGE_ID = 'mondiad-metabox-inpage-select';
  const METABOX_CLASSIC_ID = 'mondiad-metabox-classic-select';
  const METABOX_AD_DISABLED = 'disabled';

  const LOGIN = 'mondiad_login';
  const LOGIN_CLIENT_ID = 'mondiad_login_username';
  const LOGIN_SECRET = 'mondiad_login_password';

  const LOGOUT = 'mondiad_logout';
  const LOGOUT_CLEAN = 'mondiad_clean_data';

  const SITE_CHANGE = 'mondiad_change_site';

  const SITE_SELECT = 'mondiad_select_site';
  const SITE_SELECT_ID = 'mondiad_select_site_id';

  const SITE_SEARCH = 'mondiad_search_site';
  const SITE_SEARCH_NAME = 'mondiad_search_site_name';

  const AD_SELECT_ID = 'mondiad_select_ad_id';
  const AD_SELECT_UUID = 'mondiad_select_ad_uuid';

  const AD_SELECT_IN = 'mondiad_select_ad_inpage';
  const AD_CHANGE_ACTIVITY_IN = 'mondiad_change_activity_inpage';

  const AD_SELECT_CLASSIC = 'mondiad_select_ad_classic';
  const AD_CHANGE_ACTIVITY_CLASSIC = 'mondiad_change_activity_classic';

  const AD_CHANGE_ACTIVITY_NATIVE = 'mondiad_change_activity_native';
  const AD_CHANGE_ACTIVITY_BANNER = 'mondiad_change_activity_banner';

  /** @var Mondiad $instance */
  private static $instance = null;
  /** @var MondiadApi $api_service */
  private $api_service = null;
  /** @var MondiadDb $db_service */
  private $db_service = null;
  private $js_vars = [];

  public static function getInstance() {
    if (static::$instance === null) {
      static::$instance = new static();
    }
    return static::$instance;
  }

  private function __construct() {
    $this->api_service = new MondiadApi();
    $this->db_service = new MondiadDb();
  }

  private function __clone() {
  }

  public function __wakeup() {
    throw new \Exception('Cannot unserialize singleton');
  }

  function ping() {
    if (!$this->is_authorised()) {
      return false;
    }
    $response = $this->api_service->ping($this->db_service->get_api_key());
    return $this->check_response($response);
  }

  function is_authorised() {
    if (empty($this->db_service->get_api_key())) {
      return false;
    }
    // check and update token
    $expired = $this->db_service->get_auth_expiration_date();
    if ($expired === null) {
      return false;
    }
    $utc_tz = new \DateTimeZone('UTC');
    if (new \DateTime('now', $utc_tz) > new \DateTime($expired, $utc_tz)) {
      // if expired, make refresh call
      $this->refresh_token();
    }
    return true;
  }

  function is_website_selected() {
    return !empty($this->db_service->get_current_website_id());
  }

  function is_inpage_enabled() {
    return !empty($this->db_service->get_inpage_enabled());
  }

  function is_classic_enabled() {
    return !empty($this->db_service->get_classic_enabled());
  }

  function is_native_enabled() {
    return !empty($this->db_service->get_native_enabled());
  }

  function is_banner_enabled() {
    return !empty($this->db_service->get_banner_enabled());
  }

  function get_all_ads($show_deleted = false) {
    $stored_website = $this->get_current_website();
    if (!$stored_website)
      return null;
    $response = $this->api_service->get_site_with_ads($this->db_service->get_api_key(), $stored_website->id);
    if (!$this->check_response($response)) {
      $this->check_website_deleted($response);
      return null;
    }
    /* @var Website $response_website */
    $response_website = $response->data;
    $this->check_website_status_changed($response_website, $stored_website->status);
    $default_classic = $this->get_default_ad_classic();
    $default_in = $this->get_default_ad_in();

    // group records by ad type
    $new_data = [];
    /* @var AdZone $ad */
    foreach ($response_website->adZones as $item) {
      $ad = (object)$item;
      $this->check_ad_deleted($ad, $default_classic, $default_in);
      if (!$show_deleted && $ad->status == Website::STATUS_DELETED) {
        continue;
      }
      $new_data[$ad->type][$ad->id] = $ad;
    }
    return $new_data;
  }

  function get_default_ad_in() {
    return $this->db_service->get_default_ad_in();
  }

  function get_default_ad_classic() {
    return $this->db_service->get_default_ad_classic();
  }

  function get_websites($name = '') {
    if (!$this->is_authorised()) {
      return null;
    }
    $response = $this->api_service->get_websites($this->db_service->get_api_key(), $name);
    if (!$this->check_response($response)) {
      return null;
    }
    return $response->data;
  }

  function get_current_website() {
    return $this->db_service->get_selected_website();
  }

  function get_post_meta_inpage_id($post_id) {
    return $this->db_service->get_post_meta_inpage($post_id)['id'];
  }

  function get_post_meta_classic_id($post_id) {
    return $this->db_service->get_post_meta_classic($post_id)['id'];
  }

  function clean_up($clean_api_key) {
    $this->db_service->clean_up($clean_api_key);
  }

  function on_save_page($post_id) {
    if (array_key_exists($this::METABOX_INPAGE_ID, $_POST)) {
      $this->save_inpage_metadata($post_id);
    }
    if (array_key_exists($this::METABOX_CLASSIC_ID, $_POST)) {
      $this->save_classic_metadata($post_id);
    }
  }

  function add_inline_scripts() {
    global $post;
    if ($post) {
      $website = $this->get_current_website();
      if ($website && $website->status == Website::STATUS_ACCEPTED) {
        if ($this->is_inpage_enabled()) {
          $this->add_inpage_scripts($post);
        }
        if ($this->is_classic_enabled()) {
          $this->add_classic_scripts($post);
        }
      }
    }
  }

  function setup_js_vars() {
    $this->load_native_ads();
    $this->pass_js_vars();
  }

  /* form handlers section */

  function login_async() {
    $client = $this->get_http_post_value($this::LOGIN_CLIENT_ID, '');
    $secret = $this->get_http_post_value($this::LOGIN_SECRET, '');
    if (!$client)
      $this->json_error_exit('Client ID is required', 400);
    if (!$secret)
      $this->json_error_exit('Secret is required', 400);

    $response = $this->api_service->login($client, $secret);
    if ($response->status != 200) {
      $this->json_error_exit($response->message, $response->status);
    }
    $this->check_user_credentials($client, $secret);
    $this->db_service->set_api_key($response->data);
    $this->json_ok_exit();
  }

  function logout_async() {
    $clean_data = $this->get_http_post_value($this::LOGOUT_CLEAN, null);
    if (str_to_bool($clean_data)) {
      $this->clean_up(true);
    } else {
      $this->db_service->delete_api_key();
    }
    $this->json_ok_exit();
  }

  function change_website_async() {
    $this->clean_up(false);
    $this->json_ok_exit();
  }

  function select_website_async() {
    $site_id = $this->get_http_post_value($this::SITE_SELECT_ID, null);
    if (!is_numeric($site_id)) {
      $this->json_error_exit('Numeric site id is required', 400);
    }

    /* @var ?Website $website */
    $website = $this->get_website_by_id($site_id);
    if ($website === null) {
      $this->json_error_exit();
    }

    $name = htmlspecialchars($website->name);
    $status = htmlspecialchars($website->status);
    $this->db_service->set_current_website(intval($site_id), $name, $status);

    $this->json_ok_exit();
  }

  function search_website_async() {
    $name = $this->get_http_post_value($this::SITE_SEARCH_NAME, '');
    $websites = $this->get_websites($name);
    if ($websites === null) {
      $this->json_error_exit('Can not receive websites');
    }
    $this->json_ok_exit(array_values($websites));
  }

  function select_inpage_ad_async() {
    $this->set_default_ad(array($this->db_service, 'set_current_ad_in'));
  }

  function select_classic_ad_async() {
    $this->set_default_ad(array($this->db_service, 'set_current_ad_classic'));
  }

  function change_inpage_enabled_async() {
    $this->change_enabled_base(array($this->db_service, 'get_inpage_enabled'), array($this->db_service, 'set_inpage_enabled'));
  }

  function change_classic_enabled_async() {
    $this->change_enabled_base(array($this->db_service, 'get_classic_enabled'), array($this->db_service, 'set_classic_enabled'));
  }

  function change_native_enabled_async() {
    $this->change_enabled_base(array($this->db_service, 'get_native_enabled'), array($this->db_service, 'set_native_enabled'));
  }

  function change_banner_enabled_async() {
    $this->change_enabled_base(array($this->db_service, 'get_banner_enabled'), array($this->db_service, 'set_banner_enabled'));
  }

  function get_native_ad_shortcode_replacer($atts) {
    if (empty($atts['uuid'])) {
      return '';
    }
    if (!$this->is_native_enabled()) {
      return '';
    }
    $website = $this->get_current_website();
    if (!$website || $website->status != Website::STATUS_ACCEPTED) {
      return '';
    }
    return get_native_ad_html($atts['uuid']);
  }

  function get_banner_ad_shortcode_replacer($atts) {
    if (empty($atts['uuid'])) {
      return '';
    }
    if (!$this->is_banner_enabled()) {
      return '';
    }
    $website = $this->get_current_website();
    if (!$website || $website->status != Website::STATUS_ACCEPTED) {
      return '';
    }
    return get_banner_ad_html($atts['uuid']);
  }


  function handle_classic_root_js_request($wp) {
    $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\.js$/';
    if (empty($wp->request) || !preg_match($pattern, $wp->request)) {
      return;
    }
    if (!$this->is_classic_enabled()) {
      status_header(404);
      exit;
    }
    $website = $this->get_current_website();
    if (!$website || $website->status != Website::STATUS_ACCEPTED) {
      status_header(404);
      exit;
    }
    status_header(200);
    nocache_headers();
    header('Content-Type: application/x-javascript');
    echo_script();
    exit;
  }

  function add_js_var($key, $value) {
    $this->js_vars[$key] = $value;
  }

  function append_message($message) {
    $this->db_service->append_message($message);
  }

  /* private section */

  private function change_enabled_base($get_fn, $update_fn) {
    $old_state = $get_fn();
    if ($old_state === null) {
      $this->json_error_exit("Database query error");
    }
    $update_fn(!$old_state);
    $new_state = $get_fn();
    if ($new_state == $old_state) {
      $this->json_error_exit("Database query error");
    }
    $this->json_ok_exit($new_state);
  }

  private function set_default_ad($update_fn) {
    $ad_id = $this->get_http_post_value($this::AD_SELECT_ID, null);
    $ad_uuid = sanitize_text_field($this->get_http_post_value($this::AD_SELECT_UUID, ''));

    if (!is_numeric($ad_id))
      $this->json_error_exit('Numeric AdZone id is required', 400);
    if (!$ad_uuid)
      $this->json_error_exit('AdZone UUID is required', 400);

    $update_fn(intval($ad_id), $ad_uuid);
    $this->json_ok_exit();
  }

  private function save_inpage_metadata($post_id) {
    $ad_id = $this->get_http_post_value($this::METABOX_INPAGE_ID, null);

    if ($ad_id === $this::METABOX_AD_DISABLED) {  // disabled
      $this->db_service->update_inpage_post_meta($post_id, $this::METABOX_AD_DISABLED, '');
      return;
    }
    if (is_numeric($ad_id)) {  // custom ad selected
      $ads = $this->get_all_ads();
      if ($ads === null) {
        return;  // error api response, do nothing
      }
      if (isset($ads[AdZone::TYPE_IN_PAGE_PUSH][$ad_id])) {  // success
        $html_code = $ads[AdZone::TYPE_IN_PAGE_PUSH][$ad_id]->adZoneUuidId;
        $this->db_service->update_inpage_post_meta($post_id, $ad_id, $html_code);
        return;
      }
    }
    // not set or something incorrect(default)
    $this->db_service->update_inpage_post_meta($post_id, '', '');
  }

  private function save_classic_metadata($post_id) {
    $ad_id = $this->get_http_post_value($this::METABOX_CLASSIC_ID, null);

    if ($ad_id === $this::METABOX_AD_DISABLED) {  // disabled
      $this->db_service->update_classic_post_meta($post_id, $this::METABOX_AD_DISABLED, '');
      return;
    }
    if (is_numeric($ad_id)) {  // custom ad selected
      $ads = $this->get_all_ads();
      if ($ads === null) {
        return;  // error api response, do nothing
      }
      if (isset($ads[AdZone::TYPE_CLASSIC_PUSH][$ad_id])) {  // success
        $html_code = $ads[AdZone::TYPE_CLASSIC_PUSH][$ad_id]->adZoneUuidId;
        $this->db_service->update_classic_post_meta($post_id, $ad_id, $html_code);
        return;
      }
    }
    // not set or something incorrect(default)
    $this->db_service->update_classic_post_meta($post_id, '', '');
  }

  private function add_inpage_scripts($post) {
    $post_meta = $this->db_service->get_post_meta_inpage($post->ID);
    if ($post_meta['id'] === $this::METABOX_AD_DISABLED) {
      return;
    }

    $uuid = $post_meta['uuid'];
    if (!is_numeric($post_meta['id'])) {
      $default_add = $this->get_default_ad_in();
      // if default not selected
      if ($default_add === null) {
        return;
      }
      $uuid = $default_add->adZoneUuidId;
    }
    echo_inpage_ad_script($uuid);
  }

  private function add_classic_scripts($post) {
    $post_meta = $this->db_service->get_post_meta_classic($post->ID);
    if ($post_meta['id'] === $this::METABOX_AD_DISABLED) {
      return;
    }

    $uuid = $post_meta['uuid'];
    if (!is_numeric($post_meta['id'])) {
      $default_add = $this->get_default_ad_classic();
      // if default not selected
      if ($default_add === null) {
        return;
      }
      $uuid = $default_add->adZoneUuidId;
    }
    echo_classic_ad_script($uuid);
  }

  private function check_user_credentials($client, $secret) {
    // if user logged in with new credentials, then clean db data
    $stored_user = $this->db_service->get_user_credentials();
    if ($stored_user) {
      if ($stored_user === $client) {
        return;
      } else {
        $this->clean_up(true);
      }
    }
    $this->db_service->set_user_credentials($client);
  }

  private function to_main_page($error = '') {
    $page = $this::INDEX_PAGE;
    if (is_array($error)) {
      $error = $this->array_to_text($error);
    }
    $this->append_message($error);
    wp_redirect(esc_url_raw(admin_url('admin.php?page='. $page)));
  }

  private function array_to_text($array) {
    $acc = '';
    foreach ($array as $k => $v) {
      $acc .= "$k: $v ";
    }
    return $acc;
  }

  private function get_http_post_value($key, $default_value) {
    return isset($_POST[$key]) ? sanitize_text_field($_POST[$key]) : $default_value;
  }

  private function refresh_token() {
    $refresh = $this->db_service->get_refresh_auth_token();
    if ($refresh === null) {
      $this->db_service->delete_api_key();
      $this->to_main_page('Inconsistent db data. Logout...');
      exit;
    }
    $response = $this->api_service->refresh_token($refresh);
    if (!$this->check_response($response, true)) {
      $this->db_service->delete_api_key(); // if can not refresh token, logout
      $this->to_main_page();
      exit;
    }
    $this->db_service->set_api_key($response->data);
  }

  private function check_response(ApiResponse $response, $skip_auth_check = false) {
    if (!$skip_auth_check && ($response->status == 401 || $response->status == 403)) {
      $this->db_service->delete_api_key();
    }
    if ($response->status != 200) {
      error_log('Mondiad:: ' . $response->message);
      $this->append_message($response->message);
    }
    return $response->status == 200;
  }

  private function load_native_ads() {
    if (!$this->is_authorised()) {
      return;
    }
    if (!function_exists('get_current_screen')) {
      return;
    }
    $screen = get_current_screen();
    if (!(isset($screen->is_block_editor) && $screen->is_block_editor)) {
      return;
    }
    $ads = $this->get_all_ads();
    if (isset($ads[AdZone::TYPE_NATIVE])) {
      $this->js_vars['nativeAds'] = array_values($ads[AdZone::TYPE_NATIVE]);
    }
    if (isset($ads[AdZone::TYPE_BANNER])) {
      $this->js_vars['bannerAds'] = array_values($ads[AdZone::TYPE_BANNER]);
    }
  }

  private function pass_js_vars() {
    $messages = $this->db_service->get_all_messages();
    if ($messages) {
      $this->js_vars['messages'] = $messages;
    }
    echo_global_vars($this->js_vars);
  }

  private function json_error_exit($message = 'Some error occurred', $code = 500) {
    $ajax_response = ['code' => $code, 'status' => $message, 'data' => null];
    echo wp_json_encode($ajax_response);
    wp_die();
  }

  private function json_ok_exit($value = 'OK') {
    $ajax_response = ['code' => 200, 'status' => 'OK', 'data' => ['value' => $value]];
    echo wp_json_encode($ajax_response);
    wp_die();
  }

  private function check_website_deleted(ApiResponse $response) {
    if ($response->status == 404) {
      $this->db_service->set_current_website_status(Website::STATUS_DELETED);
    }
  }

  private function check_website_status_changed($website, $current_status) {
    if ($website->status != $current_status) {
      $this->db_service->set_current_website_status($website->status);
    }
  }

  /* @var AdZone $ad */
  private function check_ad_deleted($ad, $default_classic, $default_in) {
    if ($ad->status == Website::STATUS_DELETED) {
      if ($default_classic && $ad->id == $default_classic->id) {
        $this->db_service->delete_current_ad_classic();
        $this->append_message("Classic Push adZone '$ad->name' is deleted. We have reset it's 'default' status");
      }
      if ($default_in && $ad->id == $default_in->id) {
        $this->db_service->delete_current_ad_in();
        $this->append_message("In-page Push adZone '$ad->name' is deleted. We have reset it's 'default' status");
      }
      $count = $this->db_service->check_deleted_ads_meta($ad->id, $ad->adZoneUuidId);
      if ($count) {
        $this->append_message("AdZone '$ad->name' is deleted. We have removed it from $count pages");
      }
    }
  }

  private function get_website_by_id($id) {
    if (!$this->is_authorised()) {
      return null;
    }
    $response = $this->api_service->get_site_with_ads($this->db_service->get_api_key(), $id);
    if (!$this->check_response($response)) {
      return null;
    }
    return $response->data;
  }
}
