<?php

namespace Mondiad;

class TemplateWebsites {
  public static function websites() {
    TemplateLayout::echo_layout_header();
    TemplateWebsites::echo_body();
    TemplateLayout::echo_layout_footer();
  }

  public static function echo_body() {
    $mondiad = Mondiad::getInstance();

    if (!$mondiad->ping()) {
      echo_error_response('Can not call Mondiad API');
    }

    $mondiad->add_js_var('websites_page', true);

    echo "
    <div class='mondiad-websites-body'>
      <div id='mondiad_website_table_react'></div>
    </div>";
  }
}