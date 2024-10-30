<?php

namespace Mondiad;

class TemplateAdzones {
  public static function adzones() {
    TemplateLayout::echo_layout_header();
    TemplateAdzones::echo_body();
    TemplateLayout::echo_layout_footer();
  }

  public static function echo_body() {
    $mondiad = Mondiad::getInstance();

    if (!$mondiad->ping()) {
      echo_error_response('Can not call Mondiad API');
    }

    $website = $mondiad->get_current_website();
    if ($website === null) {
      echo_error_response('Can not load website');
    }

    $mondiad->add_js_var('index_url', esc_url_raw(admin_url('admin.php?page='. $mondiad::INDEX_PAGE)));
    $mondiad->add_js_var('website_name', $website->name);
    $mondiad->add_js_var('website_status', $website->status);

    $ads = $mondiad->get_all_ads();
    if ($ads === null) {
      $mondiad->append_message('Can not load ads list');
      $ads = [];
    }

    $mondiad->add_js_var('ads_page', true);
    $mondiad->add_js_var('ads_list', $ads);

    $default_in = $mondiad->get_default_ad_in();
    $default_in_id = $default_in ? $default_in->id : null;
    $mondiad->add_js_var('ads_inpage_default', $default_in_id);

    $default_cl = $mondiad->get_default_ad_classic();
    $default_cl_id = $default_cl ? $default_cl->id : null;
    $mondiad->add_js_var('ads_classic_default', $default_cl_id);

    $mondiad->add_js_var('ads_inpage_enabled', $mondiad->is_inpage_enabled());
    $mondiad->add_js_var('ads_classic_enabled', $mondiad->is_classic_enabled());
    $mondiad->add_js_var('ads_native_enabled', $mondiad->is_native_enabled());
    $mondiad->add_js_var('ads_banner_enabled', $mondiad->is_banner_enabled());

    $mondiad->add_js_var('website_name', $website->name);
    $mondiad->add_js_var('website_status', $website->status);

    echo "
      <div class='mondiad-adzones-body'>
        <div id='mondiad_ads_table_react'></div>
      </div>
    ";
  }
}