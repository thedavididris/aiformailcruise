<?php

return [
    'ai-content-toolkit' => 'AI content toolkit',
    'generate' => 'Generate',
    'regenerate' => 'Regenerate',
    'suggestion_list' => 'Suggestions',
    'suggestion_list_hint' => 'Click on any of the suggestions to use.',
    'prompt' => 'AI Content Prompt',
    'prompt_label' => 'Email description',
    'prompt_placeholder' => 'Brief us about the email you are trying to send.',
    'prompt_placeholder_hint' => 'Write about what the email is all about i.e type, content, audience, structure, template style e.t.c',
    'block_prompt_label' => 'Block content description',
    'block_prompt_placeholder' => 'Brief us about the content of this paragraph.',
    'block_prompt_placeholder_hint' => 'Write about what the block is all about i.e convincing paragraph for subscribe to our newsletter',
    'view_full_report' => 'View full score report and possible improvements',
    'check_spam_score' => 'Check spam score',
    'check_spam_score_hint' => 'Ensure your template does not contain sensitive data before sending for spam analysis !',
    'empty_content' => 'Empty content',
    'template_form_not_found' => 'Template form is missing',
    'error_saving_form' => 'Error saving %s form.',
    'test_form_not_found' => 'Test form is missing',
    'spam_test_email_not_found' => 'Spam test email not found!',
    'try_agian_later' => 'Try again later !',
    'confirm_spam_score' => 'Checking spam score will automatically save the current editor content, are you sure you want to continue ?',
    'empty_subject' => 'Subject can not be empty',
    'empty_email' => 'Emaill can not be empty',
    'empty_context' => 'Context can not be empty, tell us about your email',
    'empty_suggestions' =>  'Oops something went wrong, ensure you have an active internet!',
    'campaign_id_not_found' => 'Error detecting campaign id',

    'settings.intro' => '<p dir="auto">Configure the extension below The 
        OpenAI API authentication key can be found in your
        <a target="_blank href="https://platform.openai.com/account/api-keys" rel="nofollow">
        OpenAI Account</a>.</p>
    ',
    'settings.updated' => 'Ai toolkit settings were updated',

    'settings.plan' => 'Plans',
    'settings.plan.help' => 'Select customer plan(s) where this module will be available. Leave blank to allow for all customers',

    'openai_api_key' => 'OpenAI Api key',
    'openai_model' => 'OpenAI model',

    'sendcheckit_name' => 'Sendcheckit name',
    'sendcheckit_name.placeholder' => 'Leave empty to use the customer firstname.',
    'sendcheckit_name.help' => 'Name to use for sendcheckit.com. Leave empty to use the customer firstname.',

    'sendcheckit_email' => 'Sendcheckit email',
    'sendcheckit_email.placeholder' => 'Leave empty to use the customer email.',
    'sendcheckit_email.help' => 'Email address to use for sendcheckit.com. Leave empty to use the customer email.',

    'mailtester_email' => 'Mail-tester.com email',
    'mailtester_email.placeholder' => 'Leave empty to use dynamic email',
    'mailtester_email.help' => 'Mail-tester.com email address. Leave blank if you dont have active subscription on mail-tester.com . When blank random emails are used to simulate the free option of mail-tester.com',

    'mailtester_username' => 'Mail-tester.com username',
    'mailtester_username.placeholder' => 'Leave empty to use public username i.e test',
    'mailtester_username.help' => 'Mail-tester.com username. Leave blank if you dont have active subscription on mail-tester.com . When blank, \'test\' username is used with dynamic id and email address to simulate the free option of mail-tester.com',

    'prompt_for_template' => 'Prompt For Template',
    'prompt_for_template.help' => 'Email content/template prompt. Leave blank to use default one shown as placeholder',

    'prompt_for_block' => 'Prompt For Block',
    'prompt_for_block.help' => 'Pro builder block/segment content prompt. Leave blank to use default one shown as placeholder',

    'prompt_for_subject' => 'Prompt For Subject',
    'prompt_for_subject.help' => 'Subject line prompt. Leave blank to use default one shown as placeholder',

    'openai_suggestion_count' => 'OpenAI Suggestion count',
    'openai_suggestion_count.help' => 'Number of generated text at a time for subject line and email content',

    'openai_temperature' => 'OpenAI Temperature',
    'openai_temperature.help' => 'min. of 0 and max. of 1',
    'run_tests' => 'Run tests',

    'daily_openai_limit' => 'Daily OpenAI prompt limit',
    'daily_openai_limit.placeholder' => 'Leave empty or set to -1 for unlimited',

    'daily_mailtester_limit' => 'Daily deliverability report check limit (mailtester)',
    'daily_mailtester_limit.placeholder' => 'Leave empty or set to -1 for unlimited',

    'daily_sendcheckit_limit' => 'Daily subject line check limit',
    'daily_sendcheckit_limit.placeholder' => 'Leave empty or set to -1 for unlimited',

    'plan_limitation' => 'Plans limitation',

    'sendcheckit_rate_limit_exhausted' => 'You have exhausted your daily quota for subject line score',
    'openai_rate_limit_exhausted' => 'You have exhausted your daily quota for AI content writing',
    'mailtester_rate_limit_exhausted' => 'You have exhausted your daily quota for deliverability and spam report',

    'subscription_not_fit' => 'AI assistant not supported. Kindly upgrade to a plan with AI capabilities',
    'authentication_required' => 'Looks like your session expired ! Kindly login again',

    'ai_credit' => "Daily AI",
    'subjectline_credit' => "Daily Subject line score",
    'spamscore_credit' => "Daily Spam/Deliverability report",
];
