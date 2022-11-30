<?php
namespace app\utils;

use Psr\Container\ContainerInterface;

class Container implements ContainerInterface {
    public $services = [];

    function add($name, $callback){
        $this->services[$name] = $callback;
    }

    public function get(string $name, $args = []){
        return $this->services[$name]($args);
    }

    public function has(string $id): bool{        
        return array_key_exists($id, $this->services);
    }
  
}