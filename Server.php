<?php

/*
 * Luthier Framework
 *
 * (c) 2017 Ingenia Software C.A - Created by Anderson Salas
 *
 */

$php = PHP_BINARY;
$dir = __DIR__ . '/public';

echo "Luthier local development server started at localhost:8086 \n";
echo "Press CTRL+C to exit \n";

passthru("{$php} -S localhost:8086 -t $dir");