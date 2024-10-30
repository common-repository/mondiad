<?php

namespace Mondiad;

class TemplateLogin {
  public static function login() {
    TemplateLayout::echo_layout_header();
    TemplateLogin::echo_body();
    TemplateLayout::echo_layout_footer();
  }

  public static function echo_body() {
    $mondiad = Mondiad::getInstance();

    $mondiad->add_js_var('login_page', true);
    $mondiad->add_js_var('settings_url', $mondiad::MONDIAD_SITE_URL . '/profile/api');
    $mondiad->add_js_var('sign_up_url', $mondiad::SIGN_UP_URL);

    echo "
    <div class='mondiad-login-body'>
      <div id='mondiad_login_react'></div>
    </div>";
  }
}