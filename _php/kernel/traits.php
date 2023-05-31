<?php


trait getSelf
{
  use initByToken;
  public function get_self(): \base\Component {
    $token = $_COOKIE[$this->_cookieTokenName] ?? $_GET[$this->_cookieTokenName];
    if(empty($token)) throw new AppException('Unauthorized');

    $data = $this->_fetchDataByToken($token);
    if(!$data[self::$classModel::$idAttribute]) throw new AppException('Unauthorized', [
        'class' => get_called_class(),
        'access_token' => $token
    ]);

    return $this->create($data)->fetch();
  }
}


trait initByToken
{
  public function initByToken(string $token): self
  {
    $data = $this->_fetchDataByToken($token);

    if(!$data[self::$idAttribute]) {
      throw new AppException('Unauthorized', [
          'class' => get_called_class(),
          'access_token' => $token
      ]);
    } elseif(!$this->isNew() && $this->id != $data[self::$idAttribute]) {
      throw new AppException('Unauthorized', [
          'class' => get_called_class(),
          'access_token' => $token
      ]);
    }
    else {
      $this->set($this->parse($data));
    }

    return $this;
  }

  public function saveToken(string $token): self
  {
    if(is_string($this->tbTokens)){
      $this->tbTokens = \base\Component::initTable($this->tbTokens);
    }

    if(!($this->tbTokens instanceof \PDO)) {
      throw new Error("Table of the tokens is undefined");
    }

    $this->set('access_token', $token);
    $data = $this->toArray();
    unset($data['id']);
    $this->tbTokens->save($data);
    return $this;
  }

  private function _fetchDataByToken(string $token): array
  {
    if(is_string($this->tbTokens)){
      $this->tbTokens = \base\Component::initTable($this->tbTokens);
    }

    $data = $this->tbTokens
        ->select("*")
        ->where("`access_token`=:token", [ 'token' => $token ])
        ->limit(1)
        ->fetch(\PDO::FETCH_ASSOC);

    foreach($data as $key=>$val) {
      if(!$val || array_search($key, ['created', 'updated']) !== false) unset($data[$key]);
    }

    return $data ? $data : [];
  }
}

?>