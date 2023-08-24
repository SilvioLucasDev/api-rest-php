<?php

namespace App\infra\exceptions;

use Exception;

class OperationException extends Exception
{
  protected $code = 500;
  protected $message = 'Database operation error';

  public function __construct(string $message) {
    if($message) {
      $this->message = 'Database operation error: ' . $message;
    }
  }
}