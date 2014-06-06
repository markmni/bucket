<?php
namespace Bucket\Tests;

class TestUnderscoreCallFactory {
  public $invoked = false;
  function __call($name, $args) {
    $this->invoked = true;
    return new \StdClass();
  }
}
?>