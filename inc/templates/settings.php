<?php

namespace Mondiad;

class TemplateSettings
{
  public static function settings() {
    TemplateLayout::echo_layout_header();
    TemplateSettings::echo_body();
    TemplateLayout::echo_layout_footer();
  }

  public static function echo_body() {
    $mondiad = Mondiad::getInstance();

    if (!$mondiad->ping()) {
      echo_error_response('Can not call Mondiad API');
    }

    $mondiad->add_js_var('settings_page', true);
    $mondiad->add_js_var('index_url', esc_url_raw(admin_url('admin.php?page='. $mondiad::INDEX_PAGE)));
    if ($mondiad->is_website_selected()) {
      $website = $mondiad->get_current_website();
      $mondiad->add_js_var('current_domain', $website->name);
    }
    echo "
    <div class='mondiad-settings-body'>
        <div id='mondiad_settings_react'></div>
    </div>";
  }
}