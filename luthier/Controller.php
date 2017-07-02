<?php

/*
 * Luthier Framework
 *
 * (c) 2017 Ingenia Software C.A - Created by Anderson Salas
 *
 */

namespace Luthier;

class Controller
{
    public function __construct()
    {
        $this->load = new Loader();
    }

    private function load($name, $objectName = NULL)
    {
        $class = 'Luthier\\'.$name;

        if($objectName === NULL)
            $objectName = $name;

        $this->$objectName = new $class();
    }
}