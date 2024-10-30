<?php

namespace Mondiad;

class LoginResponse {
  /** @var string */
  public $token;
  /** @var string */
  public $refreshToken;
  /** @var string */
  public $expired;
  /** @var int */
  public $durationSeconds;
  /** @var string */
  public $scheme;
}