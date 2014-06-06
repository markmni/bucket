<?php
namespace Bucket\Tests;

class SingleClassDependency {
  public $val;
  function __construct(NoDependencies $val) {
    $this->val = $val;
  }
}
?>