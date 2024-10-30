<?php

namespace Mondiad;

class TemplateMetabox {
  public static function metabox() {
    global $post;
    $mondiad = Mondiad::getInstance();

    if (!$mondiad->ping()) {
      echo_error_response('Can not call Mondiad API');
      return;
    }

    $website = $mondiad->get_current_website();
    if ($website === null) {
      echo_error_response('Can not load website');
      return;
    }

    $mondiad->add_js_var('website_status', $website->status);

    $ads = $mondiad->get_all_ads();
    if ($ads === null) {
      $mondiad->append_message('Can not load ads list');
      $ads = [];
    }

    $current_inpage = $mondiad->get_post_meta_inpage_id($post->ID);
    $current_classic = $mondiad->get_post_meta_classic_id($post->ID);
    $is_block_editor = TemplateMetabox::is_block_editor();

    $mondiad->add_js_var('meta_box_data', $ads);
    $mondiad->add_js_var('metabox_initial_inpage', $current_inpage);
    $mondiad->add_js_var('metabox_initial_classic', $current_classic);
    $mondiad->add_js_var('metabox_is_block_editor', $is_block_editor);


    echo "
      <div class='mondiad-metabox-wrap'>
        <div id='mondiad_meta_box_react'></div>
      </div>";
  }

  public static function is_block_editor() {
    if (!function_exists('get_current_screen')) {
      return false;
    }
    $screen = get_current_screen();
    if (!$screen->is_block_editor) {
      return false;
    }
    return true;
  }
}