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
        // The Loader Class
        $this->load = new Loader();

        // The Twig Class (alias)
        $this->view = new Twig();
    }
}