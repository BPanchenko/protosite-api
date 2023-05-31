<?php

class AppException extends \base\SystemException {
  protected $default_error = [
    'code' => 400,
    'message' => "Unknown Error"
  ];

  protected $_hash = [
    'AccessDenied' => [
      'code' => 401,
      'message' => "Access denied"
    ],
    'EmailExists' => [
      'code' => 409,
      'message' => "Email is already registered"
    ],
    'MethodNotAllowed' => [
      'code' => 405,
      'message' => "Method is not supported"
    ],
    'ManyRequestCheckpoints' => [
      'code' => 405,
      'message' => "Many checkpoints in the request"
    ],
    'WrongEmail' => [
      'code' => 400,
      'message' => "Invalid email"
    ],
    'WrongFileType' => [
      'code' => 400,
      'message' => "Not registered file type"
    ],
    'WrongPassword' => [
      'code' => 400,
      'message' => "Invalid password"
    ],
    'Unauthorized' => [
      'code' => 401,
      'message' => "Access denied"
    ],
    'UnprocessableEntity' => [
      'code' => 422,
      'message' => "Unprocessable Entity"
    ]
  ];
}
?>