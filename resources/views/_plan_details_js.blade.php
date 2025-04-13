<?php

use Rubbylab\AiContentToolkit\AiContentToolkit;

try {

    $groups = $aiContentToolkit->getOption('customer_groups', []);
    $planid = $plan->uid;
    
    if(!in_array($planid, $groups)) return;
} catch (\Throwable $th) {
    return;
}

?>
<template id="aicontent-plan-quota">

    <!-- AI -->
    <li class="selfclear hide">
        <div class="unit size1of2">
            <strong>{{ trans('ai-content-toolkit::messages.ai_credit') }}</strong>
        </div>
        <div class="lastUnit size1of2">
            <mc:flag>{!! $aiContentToolkit->printQuotaOption('daily_'.AiContentToolkit::LOG_TYPE_OPENAI.'_limit_' . $planid) !!}</mc:flag>
        </div>
    </li>

    <!-- Sendcheckit -->
    <li class="selfclear hide">
        <div class="unit size1of2">
            <strong>{{ trans('ai-content-toolkit::messages.subjectline_credit') }}</strong>
        </div>
        <div class="lastUnit size1of2">
            <mc:flag>{!! $aiContentToolkit->printQuotaOption('daily_'.AiContentToolkit::LOG_TYPE_SENDCHECKIT.'_limit_' . $planid) !!}</mc:flag>
        </div>
    </li>

    <!-- Mailterster -->
    <li class="selfclear hide">
        <div class="unit size1of2">
            <strong>{{ trans('ai-content-toolkit::messages.spamscore_credit') }}</strong>
        </div>
        <div class="lastUnit size1of2">
            <mc:flag>{!! $aiContentToolkit->printQuotaOption('daily_'.AiContentToolkit::LOG_TYPE_MAILTESTER.'_limit_' . $planid) !!}</mc:flag>
        </div>
    </li>
</template>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        if(document.querySelector('ul.dotted-list.topborder.section')) {
            document.querySelector('ul.dotted-list.topborder.section').innerHTML += document.querySelector("#aicontent-plan-quota").innerHTML;
        }
    });
</script>