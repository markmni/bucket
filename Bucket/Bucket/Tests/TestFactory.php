<?php
namespace Bucket\Tests;

class TestFactory {
  public $invoked = false;
  function new_bucket_tests_nodependencies($container) {
    $this->invoked = true;
    return new NoDependencies();
  }
  function new_bucket_tests_concreteimplementation($container) {
    $this->invoked = true;
    return new NoDependencies();
  }
}
?>