<?php

require __DIR__.'/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;



$app = (new Stack\Builder())
    ->push('Stack\Auth\BasicAuthentication', [
        'firewall' => [
            ['path' => '/', 'anonymous' => true],
            ['path' => '/login'],
        ],
        'authenticator' => function ($username, $password) {
            if ('admin' === $username && 'default' === $password) {
                return 'admin-user-token';
            }
        },
        'realm' => 'Authentication Required',
    ])
    ->resolve($app);

$request = Request::createFromGlobals();
$response = $app->handle($request)->send();
$app->terminate($request, $response);
