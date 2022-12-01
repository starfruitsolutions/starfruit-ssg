<?php
namespace app\utils;

class DataSource {
    public $data;

    function setData($data){
        $this->data = $data;
    }

    function getData(){
        return $this->data;
    }

    function fetch(){
        pass;
    }

    function commit(){
        pass;
    }

}