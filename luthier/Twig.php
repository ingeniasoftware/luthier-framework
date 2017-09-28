<?php

/*
 * Luthier Framework
 *
 * (c) 2017 Ingenia Software C.A - Created by Anderson Salas
 *
 */

namespace Luthier;

use \Twig_Environment;
use \Twig_Loader_Filesystem;
use \Symfony\Component\HttpFoundation\Response;

class Twig
{
    protected $twig;

    protected $loader;

    public function __construct()
    {
        $directories = [ VIEW_PATH ];
        $cache_dir   = APP_PATH . '/Storage/Cache';

        $this->loader = new Twig_Loader_Filesystem($directories);
        $this->twig   = new Twig_Environment($this->loader, ['cache' => $cache_dir ]);
    }

    public function instance()
    {
        return $this->twig;
    }

    public function loader()
    {
        return $this->loader;
    }

    public function render(String $name, $vars = [], Bool $return = FALSE)
    {
        if($vars === NULL)
            $vars = [];

        $template = $this->twig->loadTemplate($name);

        if(!$return)
        {
            return new Response($template->render($vars));
        }
        else
        {
            echo $template->render($vars);
        }
    }
}
