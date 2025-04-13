<?php

namespace Rubbylab\AiContentToolkit\Controllers;

use Illuminate\Http\Request;
use Acelle\Http\Controllers\Controller as BaseController;
use Rubbylab\AiContentToolkit\AiContentToolkit;
use Rubbylab\AiContentToolkit\Services\SubjectLineScore;
use Rubbylab\AiContentToolkit\Utils\ContentGenerator;

class TestController extends BaseController
{

    /**
     * Run unit test for canvas block.
     * 
     * This is unit test for check the important component of the extension works fine.
     * Changes are not saved to db on normal run and numbers of process is often simulated.
     */
    public function index(Request $request)
    {

        if (!$request->user()->admin->can('read', new \Acelle\Model\Plugin())) {
            return $this->notAuthorized();
        }

        $this->print("<h2>Testing essential functions</h2>");

        $total = 0;
        $totalPassed = 0;

        $aiContentToolkit = AiContentToolkit::initialize();
        $customer = $request->user();

        // Get the subject line from the request data.
        $subject = $this->demoSubject();

        // Get the system email and name for sending the check-it email.
        $systemEmail = $aiContentToolkit->getOption('sendcheckit_email');
        $systemName = $aiContentToolkit->getOption('sendcheckit_name');

        // If system email and name are not set, use customer email and first name.
        $email = empty($systemEmail) ? $customer->email : $systemEmail;
        $name = empty($systemName) ? $customer->first_name : $systemName;

        // Set the cache directory
        $cacheDir = storage_path('framework/cache/data');


        //subject line score
        $desc = 'should get subject score with score, preview and full html';
        try {
            $total += 1;
            // Get the URL for checking the subject line score.
            $subjectScore = new SubjectLineScore([
                'name' => $name,
                'email' => $email,
                'cacheDir' => $cacheDir,
                'crawler' => ['css' => [file_get_contents($aiContentToolkit->plugin->getStoragePath('assets/css/subjectscore.css'))]],
                'provider' => 'sendcheckit'
            ]);


            $data = $subjectScore->getScore($subject);
            $passed = $data['score'] == '91' && !empty($data['preview_html']) && !empty($data['full_html']);
            if ($passed) $totalPassed += 1;
            if ($passed) $this->success('Passed: ' . $desc);
            else $this->error('Failed: ' . $desc);
        } catch (\Throwable $th) {
            $this->error('Failed: ' . $desc . ' ' . $th->getMessage());
        }



        //generation 
        $suggestionLimits = $aiContentToolkit->getOption('openai_suggestion_limit');
        $context = "Context: I'm writing an email to a potential client to introduce our company and pitch our services. Our company specializes in digital marketing for small businesses.";
        $config = [
            'model' => $aiContentToolkit->getOption('openai_model'),
            'temperature' => $aiContentToolkit->getOption('openai_temperature'),
            'user' => $customer->id
        ];
        $generator = new ContentGenerator($aiContentToolkit->getOption('openai_api_key'));
        $suggestions = [];


        //generate subject line 
        $desc = 'should generate good subject lines';
        $total += 1;
        try {

            $prompt = $aiContentToolkit->getOption('prompt_for_subject');
            $prompt = !empty($prompt) ? $prompt : $aiContentToolkit->defaultSubjectPrompt;
            $suggestions = $generator->generateText($prompt, $context, 60, $suggestionLimits, $config);

            $passed = count($suggestions) == $suggestionLimits && strlen($suggestions[0]) > 10 && strlen($suggestions[0]) < 100;
            if ($passed) $totalPassed += 1;

            if ($passed) $this->success('Passed: ' . $desc);
            else $this->error('Failed: ' . $desc);
        } catch (\Throwable $th) {
            $this->error('Failed: ' . $desc . ' ' . $th->getMessage());
        }


        //generate email content
        $desc = 'should generate good email templates';
        $total += 1;
        try {

            $prompt = $aiContentToolkit->getOption('prompt_for_template');
            $prompt =  !empty($prompt) ? $prompt : $aiContentToolkit->defaultTemplatePrompt;
            $suggestionLimits = 1;
            $suggestions = $generator->generateText($prompt, $context, 1000, $suggestionLimits, $config);

            $passed = count($suggestions) == $suggestionLimits && strlen($suggestions[0]) > 10 && strlen($suggestions[0]) > 200;
            if (stripos($prompt, '[UNSUBSCRIBE_URL]') !== false)
                $passed = $passed && stripos($suggestions[0], '[UNSUBSCRIBE_URL]') !== false;

            if ($passed) $totalPassed += 1;
            if ($passed) $this->success('Passed: ' . $desc);
            else $this->error('Failed: ' . $desc);
        } catch (\Throwable $th) {
            $this->error('Failed: ' . $desc . ' ' . $th->getMessage());
        }

        $percent =  round((100 / $total) * $totalPassed, 2);
        $this->{$percent > 50 ? 'success' : 'error'}("<h4>Total test: $totalPassed of $total passed ($percent %) </h4>");
    }

    public function demoSubject()
    {
        return '🔥 New Software to Make Your Business More Efficient 🔥';
    }

    public function demoTemplate()
    {
        return "<!DOCTYPE html>
        <html>
        <head>
            <title>Introducing Our New Software</title>
        </head>
        <body style=\"background-color: #F0F0F0; font-family: sans-serif; font-size: 18px;\">
        <div style=\"background-color: #FFFFFF; padding: 20px;\">
        <h1 style=\"color: #000000; font-weight: bold; font-size: 24px; text-align: center;\">Introducing Our New Software</h1>
        
        <p>Hi there!</p>
        
        <p>We're excited to announce the launch of our new software. Our software is designed to help you save time and money by streamlining your workflow and improving your performance.</p>
        
        <p>Our software is easy to use, secure, and feature-rich. With our software, you can:</p>
        
        <ul>
            <li>Organize your tasks into manageable projects</li>
            <li>Create custom reports to track performance</li>
            <li>Collaborate with colleagues and clients</li>
            <li>Manage projects from start to finish</li>
        </ul>
        
        <p>Try our software today and see the difference it can make for your business.</p>
        
        <p>We look forward to hearing from you.</p>
        
        <p>Best,<br />
        [COMPANY_FULL_ADDRESS]</p>
        </div>
        
        <div style=\"background-color: #FFFFFF; padding: 20px;\">
        <p style=\"font-size: 14px; color: #888888;\">If you'd like to unsubscribe, please click <a href=\"[UNSUBSCRIBE_URL]\">here</a>.</p>
        </div>
        </body>
        </html>
        ";
    }

    //Print test message
    public function print($message)
    {
        echo "<br/>$message<br/>";
    }

    private function success($message)
    {
        $this->print("<div style='color: green;'>$message</div>");
    }

    private function error($message)
    {
        $this->print("<div style='color: red;'>$message</div>");
    }
}
