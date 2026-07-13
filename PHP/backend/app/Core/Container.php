<?php
namespace App\Core;

use Exception;
use ReflectionClass;

class Container
{
    private $bindings = [];
    private $instances = [];

    /**
     * Bind a class/interface to an implementation
     */
    public function bind($abstract, $concrete = null)
    {
        if (is_null($concrete)) {
            $concrete = $abstract;
        }
        $this->bindings[$abstract] = $concrete;
    }

    /**
     * Bind a class as a singleton (only instantiated once)
     */
    public function singleton($abstract, $concrete = null)
    {
        if (is_null($concrete)) {
            $concrete = $abstract;
        }
        
        // If it's already an object but NOT a closure, store it directly
        if (is_object($concrete) && !($concrete instanceof \Closure)) {
            $this->instances[$abstract] = $concrete;
        } else {
            $this->bindings[$abstract] = $concrete;
            $this->instances[$abstract] = null; // Mark for singleton instantiation
        }
    }

    /**
     * Resolve and return an instance of the class with auto-wiring
     */
    public function resolve($abstract)
    {
        // Check if it's already instantiated as a singleton
        if (array_key_exists($abstract, $this->instances) && is_object($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // Get the concrete implementation to resolve
        $concrete = $this->bindings[$abstract] ?? $abstract;

        // If it's a Closure, evaluate it to get the instance
        if ($concrete instanceof \Closure) {
            $instance = $concrete($this);
            // Cache if singleton
            if (array_key_exists($abstract, $this->instances)) {
                $this->instances[$abstract] = $instance;
            }
            return $instance;
        }

        try {
            $reflector = new ReflectionClass($concrete);

            if (!$reflector->isInstantiable()) {
                throw new Exception("Target [{$concrete}] is not instantiable.");
            }

            $constructor = $reflector->getConstructor();

            if (is_null($constructor)) {
                $instance = new $concrete;
            } else {
                $parameters = $constructor->getParameters();
                $dependencies = $this->getDependencies($parameters);
                $instance = $reflector->newInstanceArgs($dependencies);
            }

            // If it's registered as a singleton, cache the instance
            if (array_key_exists($abstract, $this->instances)) {
                $this->instances[$abstract] = $instance;
            }

            return $instance;
        } catch (\ReflectionException $e) {
            throw new Exception("Container failed to resolve {$abstract}: " . $e->getMessage());
        }
    }

    private function getDependencies($parameters)
    {
        $dependencies = [];
        foreach ($parameters as $parameter) {
            $dependency = $parameter->getType();
            if ($dependency === null || $dependency->isBuiltin()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new Exception("Cannot resolve primitive dependency: {$parameter->name}");
                }
            } else {
                $dependencies[] = $this->resolve($dependency->getName());
            }
        }
        return $dependencies;
    }
}
