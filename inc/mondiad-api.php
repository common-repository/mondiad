<?php

namespace Mondiad;

class MondiadApi {

  const API_URL = 'https://gw.api.members.mondiad.com/api/1.0/publisher/';
  const AUTH_API_URL = 'https://gw.api.members.mondiad.com/api/1.0/auth/';

  const OBJECT_STRUCTURE = 1;
  const ARRAY_STRUCTURE = 2;

  function __construct() {
  }

  private function make_post_request($api_key, $method, $params, $return_type) {
    $body = $params ? json_encode($params) : '{}';
    $url = $this::API_URL . $method;
    if (defined('MONDIAD_DEBUG_API') && MONDIAD_DEBUG_API) {
      error_log('Mondiad:: POST url: ' . $url);
      error_log('Mondiad:: POST body: ' . $body);
    }
    $args = [
      'headers' => [
        'Authorization' => 'Bearer ' . $api_key,
        'Content-Type' => 'application/json',
      ],
      'body' => $body
    ];
    $result = wp_remote_post($url, $args);
    if (defined('MONDIAD_DEBUG_API') && MONDIAD_DEBUG_API) {
      error_log('Mondiad:: POST api response:');
      foreach ($result as $k => $v) {
        $val = json_encode($v);
        error_log("$k: $val");
      }
    }

    if (is_wp_error($result)) {
      return new ApiResponse(503, $result->get_error_message(), null);
    }

    $response = $result['response'];
    $response_body = json_decode($result['body'], true);

    $status = $response['code'];
    $message = isset($response_body['message']) ? $response_body['message'] : (isset($response['message']) ? $response['message'] : '');
    if ($status == 400) {
      $body_field_errors = $response_body['fieldErrors'];
      $field_errors = $this->parse_field_errors(isset($body_field_errors) ? $body_field_errors : []);
      $message .= " [$field_errors]";
    }

    if ($status != 200) {
      return new ApiResponse($status, $message, null);
    }

    if ($return_type === $this::ARRAY_STRUCTURE) {
      $data = [];
      if (isset($response_body['data'])) {
        foreach ($response_body['data'] as $item) {
          $obj_item = (object)$item;
          $data[$obj_item->id] = $obj_item;
        }
      }
    } else {
      $data = (object)$response_body;
    }
    return new ApiResponse($status, $message, $data);
  }

  private function make_get_request($api_key, $method, $params, $return_type) {
    $url = $this::API_URL . $method;
    $url_with_params = add_query_arg($params, $url);
    if (defined('MONDIAD_DEBUG_API') && MONDIAD_DEBUG_API) {
      error_log('Mondiad:: GET url: ' . $url_with_params);
    }
    $args = [
      'headers' => [
        'Authorization' => 'Bearer ' . $api_key,
      ],
    ];
    $result = wp_remote_get($url_with_params, $args);
    if (defined('MONDIAD_DEBUG_API') && MONDIAD_DEBUG_API) {
      error_log('Mondiad:: GET api response:');
      foreach ($result as $k => $v) {
        $val = json_encode($v);
        error_log("$k: $val");
      }
    }

    if (is_wp_error($result)) {
      return new ApiResponse(503, $result->get_error_message(), null);
    }

    $response = $result['response'];
    $response_body = json_decode($result['body'], true);

    $status = $response['code'];
    $message = isset($response_body['message']) ? $response_body['message'] : (isset($response['message']) ? $response['message'] : '');
    if ($status == 400) {
      $body_field_errors = $response_body['fieldErrors'];
      $field_errors = $this->parse_field_errors(isset($body_field_errors) ? $body_field_errors : []);
      $message .= " [$field_errors]";
    }

    if ($status != 200) {
      return new ApiResponse($status, $message, null);
    }

    if ($return_type === $this::ARRAY_STRUCTURE) {
      $data = [];
      if (isset($response_body['data'])) {
        foreach ($response_body['data'] as $item) {
          $obj_item = (object)$item;
          $data[$obj_item->id] = $obj_item;
        }
      }
    } else {
      $data = (object)$response_body;
    }
    return new ApiResponse($status, $message, $data);
  }

  function parse_field_errors($data) {
    $acc = '';
    foreach ($data as $field => $error) {
      $acc .= "$field: $error \n";
    }
    return $acc;
  }

  private function make_auth_post($method, $params) {
    $body = json_encode($params);
    $url = $this::AUTH_API_URL . $method;
    if (defined('MONDIAD_DEBUG_API') && MONDIAD_DEBUG_API) {
      error_log('Mondiad:: AUTH url: ' . $url);
      error_log('Mondiad:: AUTH body: ' . $body);
    }
    $args = [
      'headers' => [
        'Content-Type' => 'application/json',
      ],
      'body' => $body
    ];
    $result = wp_remote_post($url, $args);
    if ($result instanceof \WP_Error) {
      error_log($result->get_error_message());
    }
    if (defined('MONDIAD_DEBUG_API') && MONDIAD_DEBUG_API) {
      error_log('Mondiad:: AUTH api response:');
      foreach ($result as $k => $v) {
        $val = json_encode($v);
        error_log("$k: $val");
      }
    }

    if (is_wp_error($result)) {
      return new ApiResponse(503, $result->get_error_message(), null);
    }

    $response = $result['response'];
    $response_body = json_decode($result['body'], true);

    $status = $response['code'];
    $message = isset($response_body['message']) ? $response_body['message'] : (isset($response['message']) ? $response['message'] : '');
    if ($status == 400) {
      $body_field_errors = $response_body['fieldErrors'];
      $field_errors = $this->parse_field_errors(isset($body_field_errors) ? $body_field_errors : []);
      $message .= " [$field_errors]";
    }

    if ($status != 200) {
      return new ApiResponse($status, $message, null);
    }

    $data = (object)$response_body['data'];
    return new ApiResponse($status, $message, $data);
  }

  function login($clientId, $secret) {
    $params = ['grantType' => 'client_credentials', 'clientId' => $clientId, 'clientSecret' => $secret];
    return $this->make_auth_post('login', $params);
  }

  function refresh_token($token) {
    $params = ['token' => $token];
    return $this->make_auth_post('refreshToken', $params);
  }

  function get_site_with_ads($api_key, $website_id) {
    return $this->make_get_request($api_key, 'website/get/' . $website_id, [], $this::OBJECT_STRUCTURE);
  }

  function get_inpage_ads($api_key, $website_id) {
    $params = ['websiteId' => $website_id, 'types' => [AdZone::TYPE_IN_PAGE_PUSH]];
    return $this->make_post_request($api_key, 'adzone/list', $params, $this::ARRAY_STRUCTURE);
  }

  function get_classic_ads($api_key, $website_id) {
    $params = ['websiteId' => $website_id, 'types' => [AdZone::TYPE_CLASSIC_PUSH]];
    return $this->make_post_request($api_key, 'adzone/list', $params, $this::ARRAY_STRUCTURE);
  }

  function get_native_ads($api_key, $website_id) {
    $params = ['websiteId' => $website_id, 'types' => [AdZone::TYPE_NATIVE]];
    return $this->make_post_request($api_key, 'adzone/list', $params, $this::ARRAY_STRUCTURE);
  }

  function get_banner_ads($api_key, $website_id) {
    $params = ['websiteId' => $website_id, 'types' => [AdZone::TYPE_BANNER]];
    return $this->make_post_request($api_key, 'adzone/list', $params, $this::ARRAY_STRUCTURE);
  }

  function create_ad($api_key, $ad) {
    $params = array_filter((array)$ad, function ($value) { return $value != null; });
    return $this->make_post_request($api_key, 'adzone/create', $params, $this::OBJECT_STRUCTURE);
  }

  function get_websites($api_key, $name = '') {
    return $this->make_get_request($api_key, 'website/list', ['domain' => $name], $this::ARRAY_STRUCTURE);
  }

  function create_website($api_key, $website) {
    $params = array_filter((array)$website, function ($value) { return $value != null; });
    return $this->make_post_request($api_key, 'website/create', $params, $this::OBJECT_STRUCTURE);
  }

  function ping($api_key) {
    // make simple lightweight call and check is it success
    return $this->make_get_request($api_key, 'website/list', ["id" => "ping"], $this::ARRAY_STRUCTURE);
  }
}

class ApiResponse {
  /** @var int */
  public $status;
  /** @var string */
  public $message;
  /** @var array|object|null */
  public $data;

  function __construct($status, $message, $data) {
    $this->status = $status;
    $this->message = $message;
    $this->data = $data;
  }
}