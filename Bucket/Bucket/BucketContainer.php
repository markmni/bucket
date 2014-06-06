<?php
namespace Bucket;

/**
 * The main container class.
 */
class BucketContainer
{
    protected $factory;
    protected $scope;
    function __construct($factory = null, $scope = null)
    {
        if (is_array($factory)) {
            $this->factory = new \StdClass();
            foreach ($factory as $classname => $callback) {
                $this->factory->{$this->getFactoryMethodNameForClass($classname)} = $callback;
            }
        } else {
            $this->factory = $factory ? $factory : new \StdClass();
        }
        $this->scope = new BucketScope($scope);
    }
    /**
     * Clones the container, with a new sub-scope.
     */
    function makeChildContainer()
    {
        return new self($this->factory, $this->scope);
    }
    /**
     * Gets a shared instance of a class.
     */
    function get($classname)
    {
        $classname = $this->scope->getImplementation($classname);
        $name = strtolower($classname);
        if (!$this->scope->has($name)) {
            $this->scope->set($name, $this->create($classname));
        }
        return $this->scope->get($name);
    }
    /**
     * Creates a new (transient) instance of a class.
     */
    function create($classname)
    {
        $classname = $this->scope->getImplementation($classname);
        $factoryMethodName = $this->getFactoryMethodNameForClass($classname);
        if (isset($this->factory->{$factoryMethodName})) {
            $obj = call_user_func($this->factory->{$factoryMethodName}, $this);
                if(!$obj) {
                    throw new BucketCreationException(
                    "Factory function " . $factoryMethodName . " did not return an object");
                }
                return $obj;
        }
        if (is_callable(array($this->factory, $factoryMethodName))) {
            $obj = $this->factory->{$factoryMethodName}($this);
                if(!$obj) {
                    throw new BucketCreationException(
                            "Factory function " . $factoryMethodName . " did not return an object");
                }
                return $obj;
        }
        return $this->createThroughReflection($classname);
    }
      
    /**
     * Sets the concrete implementation class to use for an interface/abstract class dependency.
     */
    function registerImplementation($interface, $use_class)
    {
        $this->scope->setImplementation(strtolower($interface), $use_class);
    }
    
    /**
     * Explicitly sets the implementation for a concrete class.
     */
    function set($instance, $classname = null)
    {
        if (!is_object($instance)) {
            throw new \Exception("First argument must be an object");
        }
        $name = strtolower($classname ? $classname : get_class($instance));
        $this->scope->set($name, $instance);
    }
    
    private function getFactoryMethodNameForClass($classname)
    {
        return 'new_' . strtolower(str_replace('\\' , '_' , $classname));
    }
    
    protected function createThroughReflection($classname)
    {
        if (!class_exists($classname)) {
            throw new BucketCreationException("Undefined class $classname");
        } 
        $classname = strtolower($classname);
        $klass = new \ReflectionClass($classname);
        if ($klass->isInterface() || $klass->isAbstract()) { // TODO: is this redundant?
            $candidates = array();
            foreach (get_declared_classes() as $klassname) {
                $candidate_klass = new \ReflectionClass($klassname);
                if (!$candidate_klass->isInterface() && !$candidate_klass->isAbstract()) {
                    if ($candidate_klass->implementsInterface($classname)) {
                        $candidates[] = $klassname;
                    } elseif ($candidate_klass->isSubclassOf($klass)) {
                        $candidates[] = $klassname;
                    }
                }
            }
            throw new BucketCreationException("No implementation registered for '$classname'. Possible candidates are: ". implode(', ', $candidates));
        }
        $dependencies = array();
        $ctor = $klass->getConstructor();
        if ($ctor) {
            foreach ($ctor->getParameters() as $parameter) {
            if (!$parameter->isOptional()) {
                $param_klass = $parameter->getClass();
                if (!$param_klass) {
                    throw new BucketCreationException("Can't auto-assign parameter '" . $parameter->getName() . "' for '" . $klass->getName(). "'");
                    }
                    $dependencies[] = $this->get($param_klass->getName());
                }
            }
            return $klass->newInstanceArgs($dependencies);
        }
        return $klass->newInstance();
    }
}
?>
