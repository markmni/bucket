<?php

namespace Bucket;

/**
 * Internally used by `bucket_Container` to hold instances.
 */
class BucketScope
{
    protected $top;
    protected $instances = array();
    protected $implementations = array();
    
    function __construct(BucketScope $top = null)
    {
        $this->top = $top;
    }
    
    function has($classname)
    {
        return isset($this->instances[$classname]) || ($this->top && $this->top->has($classname));
    }
    
    function get($classname)
    {
        return isset($this->instances[$classname])
            ? $this->instances[$classname]
            : ($this->top
                ? $this->top->get($classname)
                : null);
    }
    
    function set($classname, $instance)
    {
        return $this->instances[$classname] = $instance;
    }
    
    function getImplementation($interface)
    {
        $index = strtolower($interface);
        return isset($this->implementations[$index])
            ? $this->implementations[$index]
            : ($this->top
                ? $this->top->getImplementation($interface)
                : $interface);
    }
    
    function setImplementation($interface, $use_class)
    {
        $this->implementations[$interface] = $use_class;
    }
    
}
?>
