<?php

/** @var \Laravel\Lumen\Routing\Router $router */

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


$router->group(['prefix' => 'api'], function () use ($router) {
    $router->get('/', function () use ($router) {
        return "API";
    });

    $router->get('menu/{name}', 'ModuleController@show');

    //Whitelabel
    $router->get('get/{cluster}/whitelabel', 'WhitelabelController@showClusterWhitelabel');

    $router->group(['prefix' => 'auth'], function () use ($router) {
        $router->post('login', 'AuthController@login');
        $router->post('logout', 'AuthController@logout');
    });

    $router->group(['prefix' => 'admin'], function () use ($router) {
        $c = 'AdminController';
        $router->get('list', $c . '@show');
        $router->post('store', $c . '@store');
        $router->put('update', $c . '@update');
        $router->put('delete', $c . '@destroy');
        $router->put('update/status', $c . '@updateStatus');
        $router->put('change-password', $c . '@updatePassword');

        $router->get('logs', $c . '@showLogs');

        $router->get('location', $c . '@showLocation');
    });

    $router->group(['prefix' => 'role'], function () use ($router) {
        $c = 'RoleController';
        $router->get('list', $c . '@show');
        $router->post('store', $c . '@store');
        $router->put('update', $c . '@update');
        $router->put('update/status', $c . '@updateStatus');
        $router->put('delete', $c . '@destroy');
    });

    $router->group(['prefix' => 'module'], function () use ($router) {
        $c = 'ModuleController';
        $router->get('list', $c . '@show');
        $router->post('store', $c . '@store');
        $router->put('update', $c . '@update');
        $router->put('update/status', $c . '@updateStatus');
        $router->put('delete', $c . '@destroy');
    });

    $router->group(['prefix' => 'cluster'], function () use ($router) {
        $c = 'ClusterController';
        $router->get('list', $c . '@show');
        $router->post('store', $c . '@store');
        $router->put('update', $c . '@update');
        $router->put('update/status', $c . '@updateStatus');
        $router->put('delete', $c . '@destroy');
    });

    $router->group(['prefix' => 'sync'], function () use ($router) {

        //Bank Sync
        $router->get('get/{cluster_slug}/blacklisted/banks', 'BankController@show');
        $router->post('data/banks', 'BankController@syncBlacklistedBanks');

        //Game Sync
        $router->get('get/{cluster_slug}/{vendor_slug}/games', 'GameController@show');
        $router->post('data/games', 'GameController@syncGames');

        //Promotion Sync
        $router->get('get/{cluster_slug}/promotions', 'PromotionController@show');
        $router->post('data/promotions', 'PromotionController@syncPromotions');

        //Slot Banner Sync
        $router->get('get/{cluster_slug}/slotbanners', 'SlotBannerController@show');
        $router->post('data/slotbanners', 'SlotBannerController@syncSlotBanners');

        //Provider
        $router->get('get/{cluster_slug}/vendors', 'ProviderController@show');
    });

    $router->group(['prefix' => 'reports'], function () use ($router) {
        $router->post('turnover-winloss', 'ReportsController@showTurnoverWinloss');
        $router->post('turnover-winloss-refetch', 'ReportsController@refetchTurnoverWinloss');
        $router->post('create-logs', 'ReportsController@createLogs');
    });
});
