<?php

namespace ph\sen;


class ErrorMessage
{
  private $messages;

  public function __construct() {
  }

  public function log($message) {
    $this->messages[] = $message;
  }

  public function getMessages() {
    return $this->messages;
  }
}
