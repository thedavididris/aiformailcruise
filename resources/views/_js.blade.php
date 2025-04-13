<?php

// Get the customer spam test email
$customerTestMail = $aiContentToolkit->getCustomerSpamTestEmail();
$isRandomEmail = $aiContentToolkit->getOption('mailtester_email') != $customerTestMail;

// Set up URLs for various API endpoints used by the plugin
$subjectScoreUrl = route('aiContentToolkitScoreUrl', ['type' => 'subject']);
$spamScoreUrl = route('aiContentToolkitScoreUrl', ['type' => 'spam']);
$generatorUrl = route('aiContentToolkitGenerateUrl');
$campaignTestUrl = action('CampaignController@sendTestEmail');

// Determine whether content generation is allowed based on whether an OpenAI API key has been set
$allowContentGeneration = !empty($aiContentToolkit->getOption('openai_api_key'));

// Set up translations for various strings used by the plugin js methods on client
$jsTranslations = json_encode([
    'generate' => trans('ai-content-toolkit::messages.generate'),
    'regenerate' => trans('ai-content-toolkit::messages.regenerate'),
    'suggestion_list' => trans('ai-content-toolkit::messages.suggestion_list'),
    'suggestion_list_hint' => trans('ai-content-toolkit::messages.suggestion_list_hint'),
    'prompt' => trans('ai-content-toolkit::messages.prompt'),
    'prompt_label' => trans('ai-content-toolkit::messages.prompt_label'),
    'prompt_placeholder' => trans('ai-content-toolkit::messages.prompt_placeholder'),
    'prompt_placeholder_hint' => trans('ai-content-toolkit::messages.prompt_placeholder_hint'),
    'block_prompt_label' => trans('ai-content-toolkit::messages.block_prompt_label'),
    'block_prompt_placeholder' => trans('ai-content-toolkit::messages.block_prompt_placeholder'),
    'block_prompt_placeholder_hint' => trans('ai-content-toolkit::messages.block_prompt_placeholder_hint'),
    'view_full_report' => trans('ai-content-toolkit::messages.view_full_report'),
    'check_spam_score' => trans('ai-content-toolkit::messages.check_spam_score'),
    'check_spam_score_hint' => trans('ai-content-toolkit::messages.check_spam_score_hint'),
    'empty_content' => trans('ai-content-toolkit::messages.empty_content'),
    'template_form_not_found' => trans('ai-content-toolkit::messages.template_form_not_found'),
    'error_saving_form' => trans('ai-content-toolkit::messages.error_saving_form'),
    'test_form_not_found' => trans('ai-content-toolkit::messages.test_form_not_found'),
    'spam_test_email_not_found' => trans('ai-content-toolkit::messages.spam_test_email_not_found'),
    'try_agian_later' => trans('ai-content-toolkit::messages.try_agian_later'),
    'confirm_spam_score' => trans('ai-content-toolkit::messages.confirm_spam_score'),
    'campaign_id_not_found' => trans('ai-content-toolkit::messages.campaign_id_not_found'),
]);

// Set up inline JavaScript that defines various global variables used by the plugin
$inlineScript = "
    window.AICONTENT_SUBJECT_SCORE_URL = '$subjectScoreUrl';
    window.AICONTENT_SPAM_SCORE_URL = '$spamScoreUrl';
    window.AICONTENT_CONTENT_GENERATOR_URL = '$generatorUrl';
    window.ALLOW_CONTENT_GENERATION = '$allowContentGeneration';
    window.AI_CONTENT_TOOLKIT_TRANSLATIONS=$jsTranslations;
    window._ai_lang=window.AI_CONTENT_TOOLKIT_TRANSLATIONS;
    window.AICONTENT_CAMPAIGN_TEST_URL='$campaignTestUrl';
";

?>
<script>
    {!! $inlineScript !!}
</script>
<script data-customer-spam-test-email="{{ $customerTestMail }}" {{ $isRandomEmail ? 'data-is-random-email ' : '' }}
    type="text/javascript" src="{{ $aiContentToolkit->getAssetUrl('assets/js/main.js') }}"></script>
<link href="{{ $aiContentToolkit->getAssetUrl('assets/css/styles.css') }}" rel="stylesheet" type="text/css">
<link href="{{ $aiContentToolkit->getAssetUrl('assets/css/subjectscore.css') }}" rel="stylesheet" type="text/css">

