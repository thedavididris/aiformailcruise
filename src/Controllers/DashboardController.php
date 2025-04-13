<?php

namespace Rubbylab\AiContentToolkit\Controllers;

use Illuminate\Http\Request;
use Acelle\Http\Controllers\Controller as BaseController;
use Acelle\Model\Plugin;
use Rubbylab\AiContentToolkit\AiContentToolkit;

class DashboardController extends BaseController
{
    public function index(Request $request)
    {
        // authorize
        if (!$request->user()->admin->can('read', new \Acelle\Model\Plugin())) {
            return $this->notAuthorized();
        }

        // Get the plugin record in the plugin table
        $aiContentToolkit = AiContentToolkit::initialize();
        if ($request->isMethod('post')) {

            $validator = $aiContentToolkit->saveSettings($request->all());

            // redirect if fails
            if ($validator->fails()) {
                return response()->view('ai-content-toolkit::settings', [
                    'aiContentToolkit' => $aiContentToolkit,
                    'errors' => $validator->errors(),
                ], 400);
            }

            return redirect()->back()->with('alert-success', trans('ai-content-toolkit::messages.settings.updated'));
        }

        return view('ai-content-toolkit::settings', [
            'aiContentToolkit' => $aiContentToolkit,
        ]);
    }
}
