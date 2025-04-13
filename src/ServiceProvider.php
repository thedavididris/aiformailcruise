<?php

namespace Rubbylab\AiContentToolkit;

use Illuminate\Support\ServiceProvider as Base;
use Acelle\Library\Facades\Hook;
use Illuminate\Support\Facades\Auth;
use Rubbylab\AiContentToolkit\AiContentToolkit;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log as LaravelLog;

class ServiceProvider extends Base
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        // Register views path
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'ai-content-toolkit');

        // Register routes file
        $this->loadRoutesFrom(__DIR__ . '/../routes.php');

        // Register translation file
        $this->loadTranslationsFrom(storage_path('app/data/plugins/rubbylab/ai-content-toolkit/lang/'), 'ai-content-toolkit');

        // Register the translation file against Acelle translation management
        Hook::register('add_translation_file', function () {
            return [
                "id" => '#rubbylab/ai-content-toolkit_translation_file',
                "plugin_name" => "rubbylab/ai-content-toolkit",
                "file_title" => "Translation for rubbylab/ai-content-toolkit plugin",
                "translation_folder" => storage_path('app/data/plugins/rubbylab/ai-content-toolkit/lang/'),
                "file_name" => "messages.php",
                "master_translation_file" => realpath(__DIR__ . '/../resources/lang/en/messages.php'),
            ];
        });

        \Illuminate\Support\Facades\View::composer('layouts/core/_favicon', function (View $view) {
            try {
                $aiContentToolkit = AiContentToolkit::initialize();
                if ($aiContentToolkit->plugin->isActive()) {
                    $user = Auth::user();
                    if (!$user) return '';
                    $customer = $user->customer;
                    if (!$customer) return '';
                    echo view('ai-content-toolkit::_js', ['aiContentToolkit' => $aiContentToolkit])->render();
                }
            } catch (\Throwable $th) {
                LaravelLog::warning($th->getMessage());
            }
        });

        \Illuminate\Support\Facades\View::composer(['dashboard', 'account/quota_log'], function (View $view) {
            try {
                $aiContentToolkit = AiContentToolkit::initialize();
                if ($aiContentToolkit->plugin->isActive()) {
                    $user = Auth::user();
                    if (!empty($user->customer))
                        echo view('ai-content-toolkit::_quota_js', ['aiContentToolkit' => $aiContentToolkit, 'user' => $user, 'viewName' => $view->getName()])->render();
                }
            } catch (\Throwable $th) {
                LaravelLog::warning($th->getMessage());
            }
        });

        \Illuminate\Support\Facades\View::composer('plans/_details', function (View $view) {
            try {
                $aiContentToolkit = AiContentToolkit::initialize();
                if ($aiContentToolkit->plugin->isActive()) {
                    $user = Auth::user();
                    if (!empty($user->customer))
                        echo view('ai-content-toolkit::_plan_details_js', ['aiContentToolkit' => $aiContentToolkit, 'plan' => $view->getData()['plan'] ?? ''])->render();
                }
            } catch (\Throwable $th) {
                LaravelLog::warning($th->getMessage());
            }
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
    }
}
