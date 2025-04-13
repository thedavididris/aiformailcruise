<?php

/**
 * The CustomerController class provides actions that are accessible to the customer role.
 * 
 * These actions include generating email subject lines and templates using the OpenAI API and checking the spam score of an email.
 */

namespace Rubbylab\AiContentToolkit\Controllers;

use Illuminate\Http\Request;
use Acelle\Http\Controllers\Controller as BaseController;
use Acelle\Library\Lockable;
use Illuminate\Support\Facades\Auth;
use Rubbylab\AiContentToolkit\AiContentToolkit;
use Rubbylab\AiContentToolkit\Services\SpamScore;
use Rubbylab\AiContentToolkit\Utils\ContentGenerator;
use Rubbylab\AiContentToolkit\Services\SubjectLineScore;

class CustomerController extends BaseController
{

    /** @var AiContentToolkit */
    public $aiContentToolkit;

    public function __construct()
    {
        parent::__construct();
        $this->aiContentToolkit = AiContentToolkit::initialize();
    }

    public function checkScore(Request $request, $type)
    {
        if ($type == 'subject') {
            return $this->checkSubjectScore($request);
        }

        if ($type == 'spam') {
            return $this->checkSpamScore($request);
        }

        throw new \InvalidArgumentException("Invalid type: $type");
    }

    /**
     * Action for checking the score of a subject line.
     *
     * @return string Returns JSON-encoded data about the subject line score.
     */
    public function checkSubjectScore(Request $request)
    {
        $aiContentToolkit = $this->aiContentToolkit;
        $customer = $request->user();

        // Get the subject line from the request data.
        $subject = $request->input('subject', '');
        if (empty($subject)) return response()->json(['message' => trans('ai-content-toolkit::messages.empty_subject')]);

        // Check limit
        if ($this->hasExceededLimit(AiContentToolkit::LOG_TYPE_SENDCHECKIT, $customer)) {
            return response()->json(['message' => trans('ai-content-toolkit::messages.sendcheckit_rate_limit_exhausted')]);
        }

        // Get the system email and name for sending the check-it email.
        $systemEmail = $aiContentToolkit->getOption('sendcheckit_email');
        $systemName = $aiContentToolkit->getOption('sendcheckit_name');

        // If system email and name are not set, use customer email and first name.
        $email = empty($systemEmail) ? $customer->email : $systemEmail;
        $name = empty($systemName) ? $customer->first_name : $systemName;

        // Set the cache directory
        $cacheDir = storage_path('framework/cache/data');

        // Get the URL for checking the subject line score.
        $subjectScore = new SubjectLineScore([
            'name' => $name,
            'email' => $email,
            'cacheDir' => $cacheDir,
            'crawler' => ['css' => [file_get_contents($aiContentToolkit->plugin->getStoragePath('assets/css/subjectscore.css'))]],
            'provider' => 'sendcheckit'
        ]);


        $data = $subjectScore->getScore($subject);

        if (!empty($data['preview_html']))
            $this->logLimit(AiContentToolkit::LOG_TYPE_SENDCHECKIT, $customer->id);

        // We currently need only the preivew and full html to send as JSON string
        return response()->json([
            'preview_html' => $data['preview_html'],
            'full_html' => $data['full_html'],
        ]);
    }


    /**
     * Retrieves the spam score of an email address
     *
     * @return string The spam score and HTML content of the page
     */
    public function checkSpamScore(Request $request)
    {
        // Get the customer object and request object
        $customer = $request->user();

        // Get the email from the POST or GET request
        $email = $request->input('email');

        // Check limit
        if ($this->hasExceededLimit(AiContentToolkit::LOG_TYPE_MAILTESTER, $customer)) {
            return response()->json(['message' => trans('ai-content-toolkit::messages.mailtester_rate_limit_exhausted')]);
        }

        // If email is empty, return error message
        if (empty($email)) {
            return response()->json(['message' => trans('ai-content-toolkit::messages.empty_email')]);
        }

        // Set the cache directory
        $cacheDir = storage_path('framework/cache/data');

        // Create a new SpamScore object
        $spamScore = new SpamScore(['cacheDir' => $cacheDir]);
        $data = $spamScore->getScore($email);

        if (!empty($data['score']))
            $this->logLimit(AiContentToolkit::LOG_TYPE_MAILTESTER, $customer->id);

        // Return the data as JSON
        return response()->json($data);
    }


    /**
     * Generates email subject lines or templates using OpenAI
     *
     * @param string $type The type of content to generate ('subject' or 'template')
     *
     * @return string An array of suggested subject lines or templates
     */
    public function generate(Request $request, $type)
    {
        // Get the customer object and request object
        $customer = $request->user();

        $aiContentToolkit = $this->aiContentToolkit;

        // Get the context from the POST or GET request
        $context = $request->input('context');

        // If context is empty, return error message
        if (empty($context)) {
            return response()->json(['message' => trans('ai-content-toolkit::messages.empty_context')]);
        }

        // Check limit
        if ($this->hasExceededLimit(AiContentToolkit::LOG_TYPE_OPENAI, $customer)) {
            return response()->json(['message' => trans('ai-content-toolkit::messages.openai_rate_limit_exhausted')]);
        }

        $config = [
            'model' => $aiContentToolkit->getOption('openai_model'),
            'temperature' => $aiContentToolkit->getOption('openai_temperature'),
            'user' => $customer->id
        ];

        $generator = new ContentGenerator($aiContentToolkit->getOption('openai_api_key'));
        $suggestions = [];

        try {
            // If generating for subjec line
            if ($type == 'subject') {
                $prompt = $aiContentToolkit->getOption('prompt_for_subject');
                $prompt = !empty($prompt) ? $prompt : $aiContentToolkit->defaultSubjectPrompt;
                $suggestions = $generator->generateText($prompt, $context, 60, $aiContentToolkit->getOption('openai_suggestion_limit'), $config);
            }

            // Generating for email template and campaign
            if ($type == 'template') {
                $prompt = $aiContentToolkit->getOption('prompt_for_template');
                $prompt =  !empty($prompt) ? $prompt : $aiContentToolkit->defaultTemplatePrompt;
                $suggestions = $generator->generateText($prompt, $context, 1000, 1, $config);
            }

            // Generating for email block or segment
            if ($type === 'block') {
                $prompt = $aiContentToolkit->getOption('prompt_for_block');
                $prompt =  !empty($prompt) ? $prompt : $aiContentToolkit->defaultBlockPrompt;
                $suggestions = $generator->generateText($prompt, $context, 300, $aiContentToolkit->getOption('openai_suggestion_limit'), $config);

                foreach ($suggestions as $key => $suggestion) {
                    $suggestions[$key] = strip_tags($suggestion, ['b', 'u', 'i', 'strong', 'em', 'br', 'span', 's', 'sup', 'sub']);
                }
            }

            $this->logLimit(AiContentToolkit::LOG_TYPE_OPENAI, $customer->id);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()]);
        }

        // If suggestions is empty, most like network failure, return error message
        if (empty($suggestions)) return response()->json(['message' => trans('ai-content-toolkit::messages.empty_suggestions')]);

        // Return suggestions as JSON
        return response()->json(['suggestions' => $suggestions]);
    }

    /**
     * Authorize user access. Confirm subscription access
     *
     * @param User $user
     * @return void
     * @throws Exception When not logged in or subscription not allowed.
     */
    private function authorizeUsage($user)
    {
        // Check for auth
        $customer = $user->customer ?? null;
        if (!$customer)
            throw new \Exception(trans('ai-content-toolkit::messages.authentication_required'), 1);

        // Ensure subscription fit
        $groups = $this->aiContentToolkit->getOption('customer_groups', []);
        $subscription = $customer->getCurrentActiveSubscription();
        if (!$subscription || (!empty($groups) && !in_array($subscription->plan->uid, $groups)))
            throw new \Exception(trans('ai-content-toolkit::messages.subscription_not_fit'), 1);
    }

    /**
     * Method to check if user has exceeded daily limit for a provider
     *
     * @param string $logType
     * @param User $user
     * @return boolean
     */
    private function hasExceededLimit($logType, $user)
    {
        $this->authorizeUsage($user);

        $aiContentToolkit = $this->aiContentToolkit;

        $logs = (array)$aiContentToolkit->getOption('logs', []);

        $today = date('y-m-d');
        $currentLimit  = (int)($logs[$today][$logType][$user->id] ?? 0);
        $planid = $user->customer->getCurrentActiveSubscription()->plan->uid;

        $dailyQuota = (int)$aiContentToolkit->getOption('daily_' . $logType . '_limit_' . $planid, '-1');

        if ($dailyQuota === -1) return false;

        return $currentLimit >= $dailyQuota;
    }

    /**
     * Method to log daily provider usage for the customer
     *
     * @param string $logType
     * @param string $userId
     * @return void
     */
    public function logLimit($logType, $userId)
    {
        $aiContentToolkit = $this->aiContentToolkit;

        $lock = new Lockable(storage_path('locks/ai-content-toolkit'));
        $lock->getExclusiveLock(function () use ($aiContentToolkit, $logType, $userId) {

            $logs = (array)$aiContentToolkit->getOption('logs', []);

            $today = date('y-m-d');
            $twoDaysAgo = date('y-m-d', strtotime('-2 days'));

            $currentUsage  = $logs[$today][$logType][$userId] ?? 0;
            $logs[$today][$logType][$userId] = $currentUsage + 1;

            if (isset($logs[$twoDaysAgo])) {
                unset($logs[$twoDaysAgo]);
            }


            // Update 
            $aiContentToolkit->plugin->updateData(['logs' => $logs]);
        }, $timeout = 5);
    }
}
