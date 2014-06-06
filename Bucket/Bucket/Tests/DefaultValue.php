<?php
namespace Bucket\Tests;

class DefaultValue {
  public $val;
  function __construct($val = 42) {
    $this->val = $val;
  }
}
?>