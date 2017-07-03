<?php

/*
 * Luthier Framework
 *
 * (c) 2017 Ingenia Software C.A - Created by Anderson Salas
 *
 */

// Set the application folder:
$application_folder = 'application';

// Set the system folder.
$system_folder ='luthier';

if(!file_exists( __DIR__  . '/../' . $application_folder ))
{
    echo 'The application folder isn\'t configured correctly';
    exit(1);
}

if(!file_exists( __DIR__  . '/../' . $system_folder ))
{
    echo 'The system folder isn\'t configured correctly';
    exit(1);
}

require __DIR__ . '/../luthier/Bootstrap.php';