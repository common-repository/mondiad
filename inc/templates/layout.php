<?php

namespace Mondiad;

class TemplateLayout {

  public static function echo_layout_header() {
    $mondiad = Mondiad::getInstance();
    $logo_url = esc_attr($mondiad::STATIC_ROOT_URL . 'img/transparent.png');
    $mondiad_url = esc_attr($mondiad::MONDIAD_SITE_URL);

    echo "
      <div class='wrap mondiad-layout'>
        <div class='mondiad-header'>
          <img class='mondiad-logo' src='$logo_url'>
          <div class='mondiad-sub-logo'>
            Visit our site to fine tune your ads. <a href='$mondiad_url' title='Mondiad' target='_blank'>Open management panel.</a>
          </div>
        </div>
        <div class='mondiad-layout-body'>";
  }

  public static function echo_layout_footer() {
    echo "
        </div>
      </div>
    ";
  }
}