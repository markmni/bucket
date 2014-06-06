<?php
namespace Bucket\Tests;

class TriedToAutoloadException extends \Exception {
  public $classname;
  function __construct($classname) {
    $this->classname = $classname;
    parent::__construct();
  }
}
?>