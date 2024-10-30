<?php

namespace Mondiad;

/**
 * This function produce common response if an error occurred.
 */
function echo_error_response($message) {
  $main_page = esc_attr(menu_page_url(Mondiad::INDEX_PAGE, false));
  $message = esc_html($message);
  echo "
    <div class='mondiad-info'>
      <p class='mondiad-warning'>$message</p>
      <a href='$main_page'>to main page</a>
    </div>";
}

/**
 * This function is called by "wp_print_footer_scripts" hook
 */
function echo_inpage_ad_script($uuid) {
  $static_url = Mondiad::API_STATIC_URL;
  $src = esc_attr("$static_url/static/$uuid.js");
  echo "
    <script async src='$src'></script>
  ";
}

/**
 * This function is called by "wp_print_footer_scripts" hook
 */
function echo_classic_ad_script($uuid) {
  $static_url = Mondiad::API_STATIC_URL;
  $src = esc_attr("$static_url/ctatic/$uuid.js");
  echo "
    <script async src='$src'></script>
  ";
}

function get_native_ad_html($uuid) {
  $uuid = esc_attr($uuid);
  return "
    <div data-mndazid='$uuid'></div>
  ";
}

function get_banner_ad_html($uuid) {
  $uuid = esc_attr($uuid);
  return "
    <div data-mndbanid='$uuid'></div>
  ";
}


function str_to_bool($val) {
  return strtolower($val) === 'true';
}

/**
 * This function is called by "admin_print_footer_scripts" hook
 */
function echo_global_vars($vars) {
  $json = wp_json_encode($vars);
  $ajax_url = esc_url_raw(admin_url('admin-ajax.php'));
  echo "
    <script type='text/javascript'>
      const MONDIAD_PHP_VAR = $json;
      const AJAX_URL = '$ajax_url';
    </script>
  ";
}

function echo_script() {
  $fn_runner = esc_attr('importScripts');
  $static_url = esc_url(Mondiad::WORKER_URL);
  echo "self.$fn_runner('$static_url');";
}
