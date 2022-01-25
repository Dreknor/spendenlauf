<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
Route::get('projects', 'ProjectsController@index');

Auth::routes(['verify' => true]);
Route::get('image/{media_id}', 'ImageController@getImage');
Route::get('user/{user_id}/sendVerification', 'UserController@sendVerification');

Route::get('/home', 'HomeController@index')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('laeufer', 'LaeuferController');
    Route::resource('teams', 'TeamsController');
    Route::resource('sponsoren', 'SponsorController');
    Route::resource('sponsorings', 'SponsoringController');

    Route::get('laeufer/{laeufer}/addTeam', 'LaeuferController@addTeam');
    Route::post('laeufer/{laeufer}/addTeam', 'LaeuferController@storeTeam');
    Route::put('laeufer/{laeufer}/removeTeam', 'LaeuferController@removeTeam');

    Route::group(['middleware' => ['permission:edit user']], function () {
        Route::resource('users', 'UserController');
    });

    Route::group(['middleware' => ['permission:show auswertung']], function () {
        Route::get('auswertung', 'AuswertungsController@index');
    });

    Route::group(['middleware' => ['permission:edit projekt']], function () {
        Route::resource('projects', 'ProjectsController', ['except'    => 'index']);
    });

    Route::group(['middleware' => ['permission:import export']], function () {
        Route::get('export/laeufer', 'ExportController@laeufer');
        Route::get('export/sponsoren', 'ExportController@sponsoren');
        Route::get('export/projects', 'ExportController@projects');
    });

    Route::group(['middleware' => ['permission:send mail']], function () {
        Route::get('sponsor/sendMail/{sponsor}', 'SponsorController@sendMail');
        Route::get('import/runden', 'ImportController@import');
        Route::post('import/runden', 'ImportController@importFile');
    });
});
