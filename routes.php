<?php

// Client View Groups
Route::group(['middleware' => ['web'], 'namespace' => '\Rubbylab\AiContentToolkit\Controllers'], function () {
    Route::match(['get', 'post'], 'plugins/rubbylab/ai-content-toolkit/test', 'TestController@index')->name('aiContentToolkitTest');
    Route::match(['get', 'post'], 'plugins/rubbylab/ai-content-toolkit', 'DashboardController@index');
    Route::match(['get', 'post'], '/plugins/rubbylab/ai-content-toolkit/check-score/{type}', 'CustomerController@checkScore')->name('aiContentToolkitScoreUrl');
    Route::match(['get', 'post'], '/plugins/rubbylab/ai-content-toolkit/generate/{type?}', 'CustomerController@generate')->name('aiContentToolkitGenerateUrl');
});