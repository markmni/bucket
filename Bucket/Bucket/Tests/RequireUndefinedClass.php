<?php
namespace Bucket\Tests;

class RequireUndefinedClass {
  function __construct(ClassThatDoesntExist $autoloaded) {}
}
?>