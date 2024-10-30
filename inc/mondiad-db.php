<?php

namespace Mondiad;

use http\Exception\UnexpectedValueException;

class MondiadDb {

  const INPAGE_POST_META_ID = 'mondiad_adzone_inpage';
  const INPAGE_POST_META_CONTENT = 'mondiad_adzone_inpage_content';
  const CLASSIC_POST_META_ID = 'mondiad_adzone_classic';
  const CLASSIC_POST_META_CONTENT = 'mondiad_adzone_classic_content';

  const USER_CREDENTIALS_OPTION = 'mondiad-user-credentials';
  const API_KEY_OPTION = 'mondiad-api-key';
  const API_KEY_REFRESH_OPTION = 'mondiad-api-key-refresh';
  const API_KEY_EXPIRED_OPTION = 'mondiad-api-key-expired';
  const WEBSITE_ID_OPTION = 'mondiad-website-id';
  const WEBSITE_NAME_OPTION = 'mondiad-website-name';
  const WEBSITE_STATUS_OPTION = 'mondiad-website-status';
  const INPAGE_AD_ID_OPTION = 'mondiad-inpage-ad-id';
  const INPAGE_AD_UUID_OPTION = 'mondiad-inpage-ad-uuid';
  const INPAGE_AD_ENABLED_OPTION = 'mondiad-inpage-ad-enabled';
  const CLASSIC_AD_ID_OPTION = 'mondiad-classic-ad-id';
  const CLASSIC_AD_UUID_OPTION = 'mondiad-classic-ad-uuid';
  const CLASSIC_AD_ENABLED_OPTION = 'mondiad-classic-ad-enabled';
  const NATIVE_AD_ENABLED_OPTION = 'mondiad-native-ad-enabled';
  const BANNER_AD_ENABLED_OPTION = 'mondiad-banner-ad-enabled';

  const USER_MESSAGE_OPTION = 'mondiad-last-user-message';

  function clean_up($clean_api_key) {
    if ($clean_api_key) {
      delete_option($this::USER_CREDENTIALS_OPTION);
      $this->delete_api_key();
    }
    delete_option($this::WEBSITE_ID_OPTION);
    delete_option($this::WEBSITE_NAME_OPTION);
    delete_option($this::WEBSITE_STATUS_OPTION);
    delete_option($this::INPAGE_AD_ID_OPTION);
    delete_option($this::INPAGE_AD_UUID_OPTION);
    delete_option($this::INPAGE_AD_ENABLED_OPTION);
    delete_option($this::CLASSIC_AD_ID_OPTION);
    delete_option($this::CLASSIC_AD_UUID_OPTION);
    delete_option($this::CLASSIC_AD_ENABLED_OPTION);
    delete_option($this::NATIVE_AD_ENABLED_OPTION);
    delete_option($this::BANNER_AD_ENABLED_OPTION);
    delete_option($this::USER_MESSAGE_OPTION);
    $this->clean_pages_meta();
    $this->clean_shortcodes();
  }

  function delete_api_key() {
    delete_option($this::API_KEY_OPTION);
    delete_option($this::API_KEY_REFRESH_OPTION);
    delete_option($this::API_KEY_EXPIRED_OPTION);
  }

  function clean_pages_meta() {
    global $wpdb;
    $sql = "DELETE FROM wp_postmeta where meta_key LIKE 'mondiad_%'";
    $res = $wpdb->query($sql);
    if ($res === false) {
      error_log('Mondiad:: Clean meta error');
    }
  }

  private function clean_shortcodes() {
    global $wpdb;
    foreach (['mondiad-native-ad', 'mondiad-banner-ad'] as $shortcode) {
      $sql = 'UPDATE wp_posts SET post_content = REGEXP_REPLACE(post_content, "\\\\['. $shortcode .'(.*?)\\]", "");';
      $res = $wpdb->query($sql);
      if ($res === false) {
        error_log('Mondiad:: Clean shortcodes error');
      }
    }
  }

  function check_deleted_ads_meta($ad_id, $ad_content) {
    global $wpdb;
    $sql = $wpdb->prepare("DELETE FROM wp_postmeta where meta_key LIKE 'mondiad_%' and (meta_value=%s or meta_value=%s)", $ad_id, $ad_content);
    $res = $wpdb->query($sql);
    if ($res === false) {
      error_log('Mondiad:: Clean meta error');
      return 0;
    }
    return $res / 2;  // delete 2 records for every page. Return affected pages count
  }

  function set_current_ad_in($id, $uuid) {
    update_option($this::INPAGE_AD_ID_OPTION, $id);
    update_option($this::INPAGE_AD_UUID_OPTION, $uuid);
  }

  function delete_current_ad_in() {
    delete_option($this::INPAGE_AD_ID_OPTION);
    delete_option($this::INPAGE_AD_UUID_OPTION);
  }

  function set_current_ad_classic($id, $uuid) {
    update_option($this::CLASSIC_AD_ID_OPTION, $id);
    update_option($this::CLASSIC_AD_UUID_OPTION, $uuid);
  }

  function delete_current_ad_classic() {
    delete_option($this::CLASSIC_AD_ID_OPTION);
    delete_option($this::CLASSIC_AD_UUID_OPTION);
  }

  function set_current_website($id, $name, $status) {
    update_option($this::WEBSITE_ID_OPTION, $id);
    update_option($this::WEBSITE_NAME_OPTION, $name);
    update_option($this::WEBSITE_STATUS_OPTION, $status);
    // all ad types enabled by default
    update_option($this::INPAGE_AD_ENABLED_OPTION, true);
    update_option($this::CLASSIC_AD_ENABLED_OPTION, true);
    update_option($this::NATIVE_AD_ENABLED_OPTION, true);
    update_option($this::BANNER_AD_ENABLED_OPTION, true);
  }

  function set_current_website_status($status) {
    update_option($this::WEBSITE_STATUS_OPTION, $status);
  }

  function get_current_website_id() {
    return $this->get_option_and_log($this::WEBSITE_ID_OPTION);
  }

  function get_api_key() {
    return $this->get_option_and_log($this::API_KEY_OPTION);
  }

  function set_api_key($response) {
    /* @var LoginResponse $response */
    update_option($this::API_KEY_OPTION, $response->token);
    update_option($this::API_KEY_REFRESH_OPTION, $response->refreshToken);

    $utc_tz = new \DateTimeZone('UTC');
    $dt = new \DateTime($response->expired, $utc_tz);
    $buffer = new \DateInterval('PT15M'); // 15 min
    $buffer->invert = 1; // -15 min
    $dt->add($buffer);
    update_option($this::API_KEY_EXPIRED_OPTION, $dt->format( \DateTime::ATOM ));
  }

  function get_refresh_auth_token() {
    return $this->get_option_and_log($this::API_KEY_REFRESH_OPTION);
  }

  function get_user_credentials() {
    return $this->get_option_and_log($this::USER_CREDENTIALS_OPTION);
  }

  function set_user_credentials($client) {
    update_option($this::USER_CREDENTIALS_OPTION, $client);
  }

  function get_auth_expiration_date() {
    return $this->get_option_and_log($this::API_KEY_EXPIRED_OPTION);
  }

  function get_inpage_enabled() {
    $sql_bool = $this->get_option_and_log($this::INPAGE_AD_ENABLED_OPTION, true);
    if ($sql_bool === null) {
      return null;
    }
    return $this->from_sql_bool($sql_bool);
  }

  function set_inpage_enabled($state) {
    $sql_bool = $this->to_sql_bool($state);
    update_option($this::INPAGE_AD_ENABLED_OPTION, $sql_bool);
  }

  function get_classic_enabled() {
    $sql_bool = $this->get_option_and_log($this::CLASSIC_AD_ENABLED_OPTION, true);
    if ($sql_bool === null) {
      return null;
    }
    return $this->from_sql_bool($sql_bool);
  }

  function set_classic_enabled($state) {
    $sql_bool = $this->to_sql_bool($state);
    update_option($this::CLASSIC_AD_ENABLED_OPTION, $sql_bool);
  }

  function get_native_enabled() {
    $sql_bool = $this->get_option_and_log($this::NATIVE_AD_ENABLED_OPTION, true);
    if ($sql_bool === null) {
      return null;
    }
    return $this->from_sql_bool($sql_bool);
  }

  function get_banner_enabled() {
    $sql_bool = $this->get_option_and_log($this::BANNER_AD_ENABLED_OPTION);
    if ($sql_bool === null) {
      $sql_bool = 1;
    }
    return $this->from_sql_bool($sql_bool);
  }

  function set_native_enabled($state) {
    $sql_bool = $this->to_sql_bool($state);
    update_option($this::NATIVE_AD_ENABLED_OPTION, $sql_bool);
  }

  function set_banner_enabled($state) {
    $sql_bool = $this->to_sql_bool($state);
    update_option($this::BANNER_AD_ENABLED_OPTION, $sql_bool);
  }

  // can read only once
  function get_all_messages() {
    $message = $this->get_option_and_log($this::USER_MESSAGE_OPTION);
    if ($message) {
      delete_option($this::USER_MESSAGE_OPTION);
    }
    if ($message === null) return [];
    $response = json_decode($message);
    return isset($response) ? $response : [];
  }

  function append_message($message) {
    if ($message) {
      $loaded_messages = json_decode($this->read_message());
      $message_array = isset($loaded_messages) ? $loaded_messages : [];
      $message_array[] = $message;
      $json = json_encode($message_array);
      update_option($this::USER_MESSAGE_OPTION, $json);
    }
  }

  private function read_message() {
    return $this->get_option_and_log($this::USER_MESSAGE_OPTION);
  }

  function get_post_meta_inpage($post_id) {
    $id = get_post_meta($post_id, $this::INPAGE_POST_META_ID, true);
    $uuid = get_post_meta($post_id, $this::INPAGE_POST_META_CONTENT, true);
    return ['id' => $id, 'uuid' => $uuid];
  }

  function get_post_meta_classic($post_id) {
    $id = get_post_meta($post_id, $this::CLASSIC_POST_META_ID, true);
    $uuid = get_post_meta($post_id, $this::CLASSIC_POST_META_CONTENT, true);
    return ['id' => $id, 'uuid' => $uuid];
  }

  function get_default_ad_in() {
    $id = $this->get_option_and_log($this::INPAGE_AD_ID_OPTION);
    if ($id === null) {
      return null;
    }
    $ad_zone = new AdZone();
    $ad_zone->id = $id;
    $ad_zone->adZoneUuidId = $this->get_option_and_log($this::INPAGE_AD_UUID_OPTION, true);
    return $ad_zone;
  }

  function get_default_ad_classic() {
    $id = $this->get_option_and_log($this::CLASSIC_AD_ID_OPTION);
    if ($id === null) {
      return null;
    }
    $ad_zone = new AdZone();
    $ad_zone->id = $id;
    $ad_zone->adZoneUuidId = $this->get_option_and_log($this::CLASSIC_AD_UUID_OPTION, true);
    return $ad_zone;
  }

  function get_selected_website() {
    $id = $this->get_option_and_log($this::WEBSITE_ID_OPTION);
    if ($id === null) {
      return null;
    }
    $website = new Website;
    $website->id = $id;
    $website->name = $this->get_option_and_log($this::WEBSITE_NAME_OPTION, true);
    $website->status = $this->get_option_and_log($this::WEBSITE_STATUS_OPTION, true);
    return $website;
  }

  function update_inpage_post_meta($post_id, $ad_id, $ad_content) {
    update_post_meta($post_id, $this::INPAGE_POST_META_ID, $ad_id);
    update_post_meta($post_id, $this::INPAGE_POST_META_CONTENT, $ad_content);
  }

  function update_classic_post_meta($post_id, $ad_id, $ad_content) {
    update_post_meta($post_id, $this::CLASSIC_POST_META_ID, $ad_id);
    update_post_meta($post_id, $this::CLASSIC_POST_META_CONTENT, $ad_content);
  }

  private function to_sql_bool($value) {
    if ($value) {
      return 1;
    }
    return 0;
  }

  private function from_sql_bool($value) {
    if ($value == 1) {
      return true;
    }
    if ($value == 0) {
      return false;
    }
    throw new UnexpectedValueException("Bool converting error. $value is given");
  }

  private function get_option_and_log($option, $is_required = false) {
    $value = get_option($option, null);
    if ($is_required && $value === null) {
      error_log("Mondiad:: Can't read $option option");
    }
    return $value;
  }
}
