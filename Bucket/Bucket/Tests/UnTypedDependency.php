<?php
namespace Bucket\Tests;

class UnTypedDependency {
  public $val;
  function __construct($val) {
    $this->val = $val;
  }
}
?>