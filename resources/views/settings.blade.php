@extends('layouts.core.backend')

@section('title', trans('ai-content-toolkit::messages.ai-content-toolkit'))

@section('page_header')

    <div class="page-title">
        <ul class="breadcrumb breadcrumb-caret position-right">
            <li class="breadcrumb-item"><a href="{{ action('HomeController@index') }}">{{ trans('messages.home') }}</a></li>
            <li class="breadcrumb-item"><a
                    href="{{ action('Admin\PluginController@index') }}">{{ trans('messages.plugins') }}</a></li>
            <li class="breadcrumb-item active">{{ trans('messages.update') }}</li>
        </ul>
    </div>

@endsection


@section('content')
    <h3 class="">{{ trans('ai-content-toolkit::messages.ai-content-toolkit') }}</h3>
    <p>
        {!! trans('ai-content-toolkit::messages.settings.intro') !!}
    </p>
    <form enctype="multipart/form-data" action="" method="POST" class="form-validate-jquery">
        {{ csrf_field() }}

        <div>
            <div class="row">
                <div class="col-lg-12">
                    @include('helpers.form_control', [
                        'type' => 'select_tag',
                        'multiple' => true,
                        'name' => 'customer_groups[]',
                        'label' => trans('ai-content-toolkit::messages.settings.plan'),
                        'quick_note' => trans('ai-content-toolkit::messages.settings.plan.help'),
                        'value' => $aiContentToolkit->getOption('customer_groups', []),
                        'options' => $aiContentToolkit->getPlanSelectOptions(),
                        'include_blank' => trans('messages.choose'),
                    ])
                </div>
            </div>
            <hr class="my-5 opacity-25">
            <!-- open ai configuations --->
            <div class="row">
                <div class="col-lg-6">
                    @include('helpers.form_control', [
                        'type' => 'text',
                        'class' => '',
                        'name' => 'openai_api_key',
                        'value' => $aiContentToolkit->getOption('openai_api_key'),
                        'label' => trans('ai-content-toolkit::messages.openai_api_key'),
                        'help_class' => 'payment',
                    ])
                </div>
                <div class="col-lg-6">
                    @include('helpers.form_control', [
                        'type' => 'select',
                        'name' => 'openai_model',
                        'label' => trans('ai-content-toolkit::messages.openai_model'),
                        'value' => $aiContentToolkit->getOption('openai_model'),
                        'options' => $aiContentToolkit->getOpenAiModelSelectOptions(),
                        'rules' => ['openai_model' => 'required'],
                    ])
                </div>
                <div class="col-lg-6">
                    <div class="form-group">
                        <label
                            for="openai_temperature">{{ trans('ai-content-toolkit::messages.openai_temperature') }}</label>
                        <input class="form-control has-help-text" step="0.1" min="0" max="1"
                            name="openai_temperature" id="openai_temperature" type="number"
                            value="{{ $aiContentToolkit->getOption('openai_temperature', 0.6) }}">
                        <span
                            class="quick_note small">{{ trans('ai-content-toolkit::messages.openai_temperature.help') }}</span>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="form-group">
                        <label
                            for="openai_suggestion_limit">{{ trans('ai-content-toolkit::messages.openai_suggestion_count') }}</label>
                        <input class="form-control has-help-text" min="1" max="10"
                            name="openai_suggestion_limit" id="openai_suggestion_limit" type="number"
                            value="{{ $aiContentToolkit->getOption('openai_suggestion_limit', 3) }}">
                        <span
                            class="quick_note small">{{ trans('ai-content-toolkit::messages.openai_suggestion_count.help') }}</span>
                    </div>
                </div>
            </div>
            <hr class="my-5 opacity-25">
            <!-- tempalte content generation -->
            <div class="row">
                <div class="col-lg-6">
                    @include('helpers.form_control', [
                        'type' => 'textarea',
                        'class' => '',
                        'name' => 'prompt_for_subject',
                        'value' => $aiContentToolkit->getOption(
                            'prompt_for_subject',
                            $aiContentToolkit->defaultSubjectPrompt),
                        'label' => trans('ai-content-toolkit::messages.prompt_for_subject'),
                        'quick_note' => trans('ai-content-toolkit::messages.prompt_for_subject.help'),
                    ])
                </div>
                <div class="col-lg-6">
                    @include('helpers.form_control', [
                        'type' => 'textarea',
                        'class' => '',
                        'name' => 'prompt_for_template',
                        'value' => $aiContentToolkit->getOption(
                            'prompt_for_template',
                            $aiContentToolkit->defaultTemplatePrompt),
                        'label' => trans('ai-content-toolkit::messages.prompt_for_template'),
                        'quick_note' => trans('ai-content-toolkit::messages.prompt_for_template.help'),
                    ])
                </div>
                <div class="col-lg-6">
                    @include('helpers.form_control', [
                        'type' => 'textarea',
                        'class' => '',
                        'name' => 'prompt_for_block',
                        'value' => $aiContentToolkit->getOption(
                            'prompt_for_block',
                            $aiContentToolkit->defaultBlockPrompt),
                        'label' => trans('ai-content-toolkit::messages.prompt_for_block'),
                        'quick_note' => trans('ai-content-toolkit::messages.prompt_for_block.help'),
                    ])
                </div>
            </div>
            <hr class="my-5 opacity-25">
            <!-- subject line score -->
            <div class="row">
                <div class="col-lg-6">
                    @include('helpers.form_control', [
                        'type' => 'text',
                        'class' => '',
                        'placeholder' => trans('ai-content-toolkit::messages.sendcheckit_name.placeholder'),
                        'name' => 'sendcheckit_name',
                        'value' => $aiContentToolkit->getOption('sendcheckit_name'),
                        'label' => trans('ai-content-toolkit::messages.sendcheckit_name'),
                        'quick_note' => trans('ai-content-toolkit::messages.sendcheckit_name.help'),
                    ])
                </div>
                <div class="col-lg-6">
                    @include('helpers.form_control', [
                        'type' => 'text',
                        'class' => '',
                        'placeholder' => trans('ai-content-toolkit::messages.sendcheckit_email.placeholder'),
                        'name' => 'sendcheckit_email',
                        'value' => $aiContentToolkit->getOption('sendcheckit_email'),
                        'label' => trans('ai-content-toolkit::messages.sendcheckit_email'),
                        'quick_note' => trans('ai-content-toolkit::messages.sendcheckit_email.help'),
                    ])
                </div>
            </div>
            <hr class="my-5 opacity-25">
            <!-- spam score -->
            <div class="row">
                <div class="col-lg-6">
                    @include('helpers.form_control', [
                        'type' => 'text',
                        'class' => '',
                        'name' => 'mailtester_email',
                        'value' => $aiContentToolkit->getOption('mailtester_email'),
                        'placeholder' => trans('ai-content-toolkit::messages.mailtester_email.placeholder'),
                        'label' => trans('ai-content-toolkit::messages.mailtester_email'),
                        'quick_note' => trans('ai-content-toolkit::messages.mailtester_email.help'),
                    ])
                </div>
                <div class="col-lg-6">
                    @include('helpers.form_control', [
                        'type' => 'text',
                        'class' => '',
                        'name' => 'mailtester_username',
                        'value' => $aiContentToolkit->getOption('mailtester_username'),
                        'placeholder' => trans('ai-content-toolkit::messages.mailtester_username.placeholder'),
                        'label' => trans('ai-content-toolkit::messages.mailtester_username'),
                        'quick_note' => trans('ai-content-toolkit::messages.mailtester_username.help'),
                    ])
                </div>
            </div>
            <div class="my-5">
                <hr class="opacity-25" />
                <h4><?= trans('ai-content-toolkit::messages.plan_limitation');?></h4>
            </div>
            <!-- limitations -->
            <?php foreach($aiContentToolkit->getPlanSelectOptions() as $option):?>
            <div class="row mb-4 plan_limit" id="limit_<?= $option['value'];?>">
                <div class="col-lg-3">
                    <?= $option['text'];?>
                </div>
                <div class="col-lg-6">
                    @include('helpers.form_control', [
                        'type' => 'number',
                        'class' => '',
                        'name' => 'daily_openai_limit_'.$option['value'],
                        'value' => $aiContentToolkit->getOption('daily_openai_limit_'.$option['value']),
                        'placeholder' => trans('ai-content-toolkit::messages.daily_openai_limit.placeholder'),
                        'label' => trans('ai-content-toolkit::messages.daily_openai_limit'),
                    ])
                    @include('helpers.form_control', [
                        'type' => 'number',
                        'class' => '',
                        'name' => 'daily_sendcheckit_limit_'.$option['value'],
                        'value' => $aiContentToolkit->getOption('daily_sendcheckit_limit_'.$option['value']),
                        'placeholder' => trans('ai-content-toolkit::messages.daily_sendcheckit_limit.placeholder'),
                        'label' => trans('ai-content-toolkit::messages.daily_sendcheckit_limit'),
                    ])
                    @include('helpers.form_control', [
                        'type' => 'number',
                        'class' => 'mb-5',
                        'name' => 'daily_mailtester_limit_'.$option['value'],
                        'value' => $aiContentToolkit->getOption('daily_mailtester_limit_'.$option['value']),
                        'placeholder' => trans('ai-content-toolkit::messages.daily_mailtester_limit.placeholder'),
                        'label' => trans('ai-content-toolkit::messages.daily_mailtester_limit'),
                    ])
                </div>
            </div>
            <?php endforeach;?>
        </div>
        <div class="flex justify-content-end">
            <button type="submit" class="btn btn-primary me-1">{{ trans('messages.save') }}</button>
            <a class="btn btn-default"
                href="{{ action('Admin\PluginController@index') }}">{{ trans('cashier::messages.cancel') }}</a>
                <div class="col-md-2 flex justify-content-end">
                    <a class="btn btn-secondary ml-5"
                href="{{ route('aiContentToolkitTest') }}" target="_blank">{{ trans('ai-content-toolkit::messages.run_tests') }}</a>
                </div>
        </div>

    </form>

    <script>
        "use strict";
        function updateLimitations(){
            let plans = $('[name="customer_groups[]"]').val();
            $(".plan_limit").addClass('d-none');
            for (const plan in plans) {
                const element = plans[plan];
                $(`#limit_${element}`).removeClass('d-none');
            }
        }

        $(function() {
            $('[name="customer_groups[]"]').on('change', updateLimitations);
            updateLimitations();
        });
    </script>

@endsection
