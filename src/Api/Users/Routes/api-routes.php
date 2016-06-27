<?php

/*
|--------------------------------------------------------------------------
| Api Routes
|--------------------------------------------------------------------------
*/

$app->group(['prefix' => 'api/v1'], function () use ($app) {
    $app->post('login', 'App\Http\Controllers\Api\AuthController@login');
    $app->post('register', 'App\Http\Controllers\Api\AuthController@register');

    $app->group(['middleware' => 'jwt.auth'], function () use ($app) {
        $app->post('refresh', 'App\Http\Controllers\Api\AuthController@refresh');

        $app->group(['prefix' => 'user'], function () use ($app) {
            $app->get('profile', 'App\Http\Controllers\Api\UserController@getProfile');
            $app->post('profile', 'App\Http\Controllers\Api\UserController@postProfile');
        });
    });
});
