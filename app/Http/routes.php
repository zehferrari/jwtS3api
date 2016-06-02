<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$app->get('/', function () use ($app) {
?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>jwtS3api</title>
        <link href="https://fonts.googleapis.com/css?family=Lato:100" rel="stylesheet" type="text/css">
        <style>
            html, body {
                height: 100%;
            }

            body {
                margin: 0;
                padding: 0;
                width: 100%;
                display: table;
                font-weight: 100;
                font-family: 'Lato', sans-serif;
            }

            .container {
                text-align: center;
                display: table-cell;
                vertical-align: middle;
            }

            .content {
                text-align: center;
                display: inline-block;
            }

            .title {
                font-size: 96px;
            }
        </style>
    </head>
    <body>
    <div class="container">
        <div class="content">
            <div class="title">jwtS3api</div>
            <span>restricted access</span>
        </div>
    </div>
    </body>
    </html>
<?php
});

$app->get      ('file',               'FileController@index');
$app->post     ('file',               'FileController@upload');
$app->get      ('file/{id}/metadata', 'FileController@getMetadata');
$app->get      ('file/{obj}/{id}',    'FileController@download');
$app->get      ('file/{id}',          'FileController@download');
$app->delete   ('file/{id}',          'FileController@destroy');
