<?php

namespace Bucket\Tests;

require_once DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Utilities' . DIRECTORY_SEPARATOR . 'Autoloader.php';
require_once 'simpletest/unit_tester.php';
if (realpath($_SERVER['PHP_SELF']) == __FILE__) {
  error_reporting(E_ALL | E_STRICT);
  require_once 'simpletest/autorun.php';
}

use Bucket\BucketContainer;
use Bucket\BucketCreationException;
use Bucket\BucketScope;
use Utilities\AutoloaderStats;

function test_autoload_fail($classname) {
    throw new TriedToAutoloadException($classname);
}

class TestOfBucketAutoload extends \UnitTestCase {
  function test_undefined_class_triggers_autoload() {
      $bucket = new BucketContainer();
      
      // We should ensure the exception class is loaded before catching the exception
      $loaded = new BucketCreationException();
      
      $count = AutoloaderStats::$autoloaderCallCount;
      try {
          $bucket->create('RequireUndefinedClass');
      } catch (BucketCreationException $e){}
      $newCount = AutoloaderStats::$autoloaderCallCount;
      $this->assertEqual($newCount, $count+1);
      
  }
  function test_autoload_gets_canonical_classname() {
      $bucket = new BucketContainer();
      try{
          $bucket->create('RequireUndefinedClass');
      } catch (BucketCreationException $e) {
          $this->assertTrue(strpos($e->getMessage(), 'RequireUndefinedClass') !== FALSE);
          return;
      }
      $this->fail();
  }
}

class TestOfBucketResolving extends \UnitTestCase {
  function test_can_create_empty_container() {
    $bucket = new BucketContainer();
  }
  function test_can_create_class_with_no_dependencies() {
    $bucket = new BucketContainer();
    $this->assertIsA($bucket->create('Bucket\Tests\NoDependencies'), 'Bucket\Tests\NoDependencies');
  }
  function test_can_create_class_with_class_dependency() {
    $bucket = new BucketContainer();
    $o = $bucket->create('Bucket\Tests\SingleClassDependency');
    $this->assertIsA($o, 'Bucket\Tests\SingleClassDependency');
    $this->assertIsA($o->val, 'Bucket\Tests\NoDependencies');
  }
  function test_can_create_class_with_default_value() {
    $bucket = new BucketContainer();
    $o = $bucket->create('Bucket\Tests\DefaultValue');
    $this->assertIsA($o, 'Bucket\Tests\DefaultValue');
    $this->assertEqual($o->val, 42);
  }
  function test_barks_on_untyped_dependency() {
    $bucket = new BucketContainer();
    try {
        $bucket->create('Bucket\Tests\UnTypedDependency');
      $this->fail("Expected exception");
    } catch (BucketCreationException $ex) {
      $this->pass("Exception caught");
    }
  }
  function test_barks_on_interface_dependency_when_unregistered() {
    $bucket = new BucketContainer();
    try {
        $bucket->create('Bucket\Tests\DependsOnInterface');
      $this->fail("Expected exception");
    } catch (BucketCreationException $ex) {
      $this->pass("Exception caught");
    }
  }
  function test_can_create_class_with_interface_dependency() {
    $bucket = new BucketContainer();
    $bucket->registerImplementation('Bucket\Tests\AnInterface', 'Bucket\Tests\ConcreteImplementation');
    $o = $bucket->create('Bucket\Tests\DependsOnInterface');
    $this->assertIsA($o, 'Bucket\Tests\DependsOnInterface');
    $this->assertIsA($o->val, 'Bucket\Tests\ConcreteImplementation');
  }
  function test_can_set_different_implementation_for_concrete_class() {
    $bucket = new BucketContainer();
    $bucket->registerImplementation('Bucket\Tests\NoDependencies', 'Bucket\Tests\ExtendsNoDependencies');
    $o = $bucket->create('Bucket\Tests\SingleClassDependency');
    $this->assertIsA($o, 'Bucket\Tests\SingleClassDependency');
    $this->assertIsA($o->val, 'Bucket\Tests\ExtendsNoDependencies');
  }
}

class TestOfBucketContainer extends \UnitTestCase {
  function test_get_creates_new_object() {
    $bucket = new BucketContainer();
    $this->assertIsA($bucket->get('Bucket\Tests\NoDependencies'), 'Bucket\Tests\NoDependencies');
  }
  function test_get_returns_same_instance_on_subsequent_calls() {
    $bucket = new BucketContainer();
    $this->assertSame(
      $bucket->get('Bucket\Tests\NoDependencies'),
      $bucket->get('Bucket\Tests\NoDependencies'));
  }
}

class TestOfBucketFactory extends \UnitTestCase {
  function test_container_delegates_to_factory_method() {
    $factory = new TestFactory();
    $bucket = new BucketContainer($factory);
    $this->assertIsA($bucket->get('Bucket\Tests\NoDependencies'), 'Bucket\Tests\NoDependencies');
    $this->assertTrue($factory->invoked);
  }
  function test_container_can_return_different_implementation() {
    $bucket = new BucketContainer(new TestFactory());
    $this->assertIsA($bucket->get('Bucket\Tests\ConcreteImplementation'), 'Bucket\Tests\NoDependencies');
  }
  function test_container_delegates_to_factory_callback() {
    $factory = new TestFactory();
    $factory->new_bucket_tests_defaultvalue = function($container) {
      return new \StdClass();
    };
    $bucket = new BucketContainer($factory);
    $this->assertIsA($bucket->get('Bucket\Tests\DefaultValue'), 'StdClass');
  }
  function test_callback_takes_precedence_over_method() {
    $factory = new TestFactory();
    $factory->new_bucket_tests_nodependencies = function($container) {
      return new \StdClass();
    };
    $bucket = new BucketContainer($factory);
    $this->assertIsA($bucket->get('Bucket\Tests\NoDependencies'), 'StdClass');
  }
  function test_container_can_take_array_of_callbacks_as_argument() {
     $bucket = new BucketContainer(
       array(
         'Bucket\Tests\DefaultValue' => function($container) {
           return new \StdClass();
         }
       )
     );
     $this->assertIsA($bucket->get('Bucket\Tests\DefaultValue'), 'StdClass');
  }
  function test_underscore_call_is_callable() {
    $factory = new TestUnderscoreCallFactory();
    $bucket = new BucketContainer($factory);
    $this->assertIsA($bucket->get('StdClass'), 'StdClass');
    $this->assertTrue($factory->invoked);
  }
}

class TestOfBucketScope extends \UnitTestCase {
  function test_a_child_scope_uses_parent_factory() {
    $factory = new TestFactory();
    $bucket = new BucketContainer($factory);
    $scope = $bucket->makeChildContainer();
    $this->assertIsA($scope->get('Bucket\Tests\NoDependencies'), 'Bucket\Tests\NoDependencies');
    $this->assertTrue($factory->invoked);
  }
  function test_get_on_a_child_scope_returns_same_instance_on_subsequent_calls() {
    $factory = new TestFactory();
    $bucket = new BucketContainer($factory);
    $scope = $bucket->makeChildContainer();
    $this->assertSame(
      $scope->get('Bucket\Tests\NoDependencies'),
      $scope->get('Bucket\Tests\NoDependencies'));
  }
  function test_get_on_a_child_scope_returns_parent_state() {
    $factory = new TestFactory();
    $bucket = new BucketContainer($factory);
    $scope = $bucket->makeChildContainer();
    $o = $bucket->get('Bucket\Tests\NoDependencies');
    $this->assertSame(
      $o,
      $scope->get('Bucket\Tests\NoDependencies'));
  }
  function test_parent_scope_doesnt_see_child_state() {
    $factory = new TestFactory();
    $bucket = new BucketContainer($factory);
    $scope = $bucket->makeChildContainer();
    $o = $scope->get('Bucket\Tests\NoDependencies');
    $this->assertFalse($o === $bucket->get('Bucket\Tests\NoDependencies'));
  }
  function test_setting_an_instance_and_getting_it_should_return_same_instance() {
    $bucket = new BucketContainer();
    $obj = new \StdClass();
    $bucket->set($obj);
    $this->assertSame($bucket->get('StdClass'), $obj);
  }
}
?>