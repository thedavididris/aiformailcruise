<?php

namespace Rubbylab\AiContentToolkit;

use Acelle\Model\Plan;
use Acelle\Model\Plugin;
use Illuminate\Support\Facades\Validator;
use Ramsey\Uuid\Uuid;
use Rubbylab\AiContentToolkit\Utils\ContentGenerator;

class AiContentToolkit
{
    const NAME = 'rubbylab/ai-content-toolkit';

    public const LOG_TYPE_OPENAI = 'openai';
    public const LOG_TYPE_SENDCHECKIT = 'sendcheckit';
    public const LOG_TYPE_MAILTESTER = 'mailtester';

    //default prompts
    public $defaultSubjectPrompt = "higly converting subject line free of spam words for the above described email, include an emoji if possible";
    public $defaultTemplatePrompt = "long html email content free of spam words without subject line, apply some nice styled html email template, it required to use following tags:  [COMPANY_FULL_ADDRESS], [UNSUBSCRIBE_URL]";
    public $defaultBlockPrompt = "small paragraph block. Email friendly html formatted for WYSIWYG and should be free of spam words. Exclude extra note or hints about the generated content";

    public $mailTesterSuffix = "@srv1.mail-tester.com";

    public $plugin;

    public function __construct()
    {
        $this->plugin = Plugin::where('name', self::NAME)->first();
    }

    public static function initialize()
    {
        return (new self());
    }

    //a method to get the plugin data using key
    public function getOption($key, $default = '')
    {
        $data = $this->plugin->getData();
        if (isset($data[$key])) return $data[$key];
        return $default;
    }

    /**
     * Get friendly formated quota value
     *
     * @param string $key
     * @return mixed
     */
    public function printQuotaOption($key)
    {
        $value = (int)$this->getOption($key);
        return $value === -1 ? trans('messages.unlimited') : $value;
    }

    /**
     * Get list of supported Open AI models for completion and text generation
     *
     * @return array
     */
    public function getOpenAiModelSelectOptions()
    {
        $models =  [
            'text-davinci-003',
            'text-davinci-002',
            'gpt-3.5-turbo',
        ];
        $list = [];
        foreach ($models as $model) {
            $list[] = ['value' => $model, 'text' => $model];
        }
        return $list;
    }

    public function getPlanSelectOptions()
    {
        $plans = Plan::getAvailablePlans();
        $list = [];
        foreach ($plans as $plan) {
            $list[] = ['value' => $plan->uid, 'text' => $plan->name];
        }
        return $list;
    }

    /**
     * Method to get the public url of an asset
     *
     * @param string $file
     * @return string
     */
    public function getAssetUrl($file)
    {
        $absPath = $this->plugin->getStoragePath($file);
        if (file_exists($absPath)) {
            return \Acelle\Helpers\generatePublicPath($absPath, true);
        }
        return $file;
    }

    /**
     * Save plugin data
     *
     * @param array $params
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public function saveSettings($params)
    {
        // make validator
        $validator = Validator::make($params, [
            'openai_model' => 'required',
        ]);

        // redirect if fails
        if ($validator->fails()) {
            return $validator;
        }

        $openAiKey = $params['openai_api_key'];

        if (!empty($openAiKey)) {
            // test service
            $validator->after(function ($validator) use ($openAiKey) {
                try {
                    $generator = new ContentGenerator($openAiKey);
                    $count = 2;
                    $suggestions = $generator->generateText('A professional email subject line', 'An email to my softwarde development team', 60, $count);
                    if (empty($suggestions) || count($suggestions) !== $count)
                        throw new \Exception("Inavlid open ai key", 1);
                } catch (\Exception $e) {
                    $validator->errors()->add('field', 'Can not connect to OpenAI. Error: ' . $e->getMessage());
                }
            });
        }

        // save settings
        $this->plugin->updateData($params);

        return $validator;
    }

    /**
     * Get a spam test email address for the customer.
     * It uses the provided id or customer uid if no id is provided.
     *
     * @return string The email address
     */
    public function getCustomerSpamTestEmail()
    {

        if (!empty($this->getOption('mailtester_email')))
            return $this->getOption('mailtester_email');

        $customer = request()->user();
        if (!$customer) throw new \Exception("Customer not found", 1);

        $uid = $customer->uid;
        $uid = Uuid::uuid4();
        $uid = $uid->toString() . $uid;
        $uid = str_replace('-', '', $uid);
        $mailTesterUsername = $this->getOption('mailtester_username', '');
        $mailTesterUsername = empty($mailTesterUsername) ? 'test' : $mailTesterUsername;
        return $mailTesterUsername . "-" . substr($uid, 0, 9) . $this->mailTesterSuffix;
    }
}
