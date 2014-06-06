<?php
namespace Bucket\Tests;

class DependsOnInterface {
  public $val;
  function __construct(AnInterface $val) {
    $this->val = $val;
  }
}
?>