<?php

class AppException extends Exception {
  private $_type;
  private $_data;
  private $error;

  private $_hash = [
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
    'UnknownError' => [
      'code' => 400,
      'message' => "Unknown Error"
    ],
    'UnprocessableEntity' => [
      'code' => 422,
      'message' => "Unprocessable Entity"
    ]
  ];

  public function __construct(string $type = 'UnknownError', $data = null)
  {
    parent::__construct($type);

    $this->_type = $type;
    $this->_data = $data;

    if(array_key_exists($this->_type, $this->_hash))
      $this->error = $this->_hash[$this->_type];
    else
      $this->error = $this->_hash['UnknownError'];
  }

  public function code(): int
  {
    return $this->_data['code'] ?? $this->error['code'];
  }

  public function data() {
    return $this->_data;
  }

  public function message(): string
  {
    return $this->error['message'];
  }

  public function toArray(): array
  {
    $result = [];
    $result['code'] = $this->code();
    $result['error'] = [
      'message' => $this->error['message'],
      'type' => $this->_type
    ];
    return $result;
  }

  public function type(): string
  {
    return $this->_type;
  }

  public function __toString(): string
  {
    $string = $this->_type;
    if(count($this->_data)) $string .= ': ' . json_encode($this->_data);
    return $string;
  }
}
?>