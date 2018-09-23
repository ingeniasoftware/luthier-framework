<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title><?= $title ?? 'Ups' ?></title>
        <link href="http://fonts.googleapis.com/css?family=Open+Sans:300,regular" rel="stylesheet" type="text/css">
        <style>
            body
            {
                background: white;
                margin: 0;
                padding: 0;
                font-family: "Open Sans", "Arial", "Helvetica", sans-serif
            }
            h1
            {
                font-weight: 300;
                font-size: 50px;
                margin: 0;
            }
            .container
            {
                padding-top: 100px;
                text-align: center;
                display: block;
                margin: 0px auto;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1><?= $title ?? 'Ups'?></h1>
            <p><?= $message ?? 'Something went wrong' ?></p>
        </div>
    </body>
</html>