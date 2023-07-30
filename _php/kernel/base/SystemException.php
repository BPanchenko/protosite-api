<?php
namespace base;

class SystemException extends \Exception {
  protected string $type;
  protected array|null $data;
  protected array $error;

  protected $hash = [
    'UnknownError' => [
      'code' => 500,
      'message' => "Unknown Error"
    ]
  ];

  protected $unknown_error = [
    'code' => 500,
    'message' => "Unknown Error"
  ];

  public function __construct(string $type, array $data = null)
  {
    parent::__construct($type);

    $this->type = $type;
    $this->data = $data;

    if(array_key_exists($type, $this->hash))
      $this->error = $this->hash[$type];
    else
      $this->error = $this->unknown_error;
  }

  public function code(): int
  {
    return $this->data['code'] ?? $this->error['code'];
  }

  public function data() {
    return $this->data;
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
      'type' => $this->type
    ];
    return $result;
  }

  public function getType(): string
  {
    return $this->type;
  }

  public function __toString(): string
  {
    $string = $this->type;
    if(!is_null($this->data) && count($this->data)) {
      $string .= ': ' . json_encode($this->data);
    }
    return $string;
  }
}
?>
