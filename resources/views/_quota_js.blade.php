<?php

use Rubbylab\AiContentToolkit\AiContentToolkit;

try {

    $logs = (array)$aiContentToolkit->getOption('logs', []);
    $planid = $user->customer->getCurrentActiveSubscription()->plan->uid;

    $dailyAIQuota = (int)$aiContentToolkit->getOption('daily_' . AiContentToolkit::LOG_TYPE_OPENAI . '_limit_' . $planid, '-1');
    $dailySendcheckitQuota = (int)$aiContentToolkit->getOption('daily_' . AiContentToolkit::LOG_TYPE_SENDCHECKIT . '_limit_' . $planid, '-1');
    $dailyMailtesterQuota = (int)$aiContentToolkit->getOption('daily_' . AiContentToolkit::LOG_TYPE_MAILTESTER . '_limit_' . $planid, '-1');

    $today = date('y-m-d');
    $currentAIUsage  = $logs[$today][AiContentToolkit::LOG_TYPE_OPENAI][$user->id] ?? 0;
    $currentAIUsagePercent = round($dailyAIQuota <= 0 ? 0 : ($currentAIUsage / $dailyAIQuota) * 100,2);

    $currentSendcheckitUsage  = $logs[$today][AiContentToolkit::LOG_TYPE_SENDCHECKIT][$user->id] ?? 0;
    $currentSendcheckitUsagePercent = round($dailySendcheckitQuota <= 0 ? 0 : ($currentSendcheckitUsage / $dailySendcheckitQuota) * 100,2);

    $currentMailtesterUsage  = $logs[$today][AiContentToolkit::LOG_TYPE_MAILTESTER][$user->id] ?? 0;
    $currentMailtesterUsagePercent = round($dailyMailtesterQuota <= 0 ? 0 : ($currentMailtesterUsage / $dailyMailtesterQuota) * 100,2);

    $gridClass = $viewName == "dashboard" ? "col-12 col-md-6" : "col-md-12";
    $progressbarType = $viewName == "dashboard" ? "progress-bar-striped" : "";
    $progressbarSize = $viewName == "dashboard" ? "sm" : "xxs";
    $progressbarStyle = $viewName == "dashboard"? 'style="height: 12px;"':'';
    $contentClass = $viewName == "dashboard" ? "content-group-sm mb-3" : " content-group-sm mt-4";
    $textClass = $viewName == "dashboard" ? "fw-600 me-auto" : "h5 text-semibold mb-1 me-auto";
} catch (\Throwable $th) {
    return;
}

?>
<template id="aicontent-quota">

    <!-- AI -->
    <div class="{{ $gridClass }}">
        <div class="{{ $contentClass }}">
            <div class="d-flex mb-2">
                <label class="{{ $textClass }}">{{ trans('ai-content-toolkit::messages.ai_credit') }}</label>
                <div class="pull-right text-semibold">
                    <span
                        class="text-muted">{{ number_with_delimiter($currentAIUsage) }}/{{ ($dailyAIQuota == -1) ? '∞' : number_with_delimiter($dailyAIQuota) }}</span>
                    &nbsp;&nbsp;&nbsp;<span>{{ $currentAIUsagePercent }}%</span>
                </div>
            </div>

            <div class="progress progress-{{ $progressbarSize }}" {{ $progressbarStyle }}>
                <div class="progress-bar {{ $progressbarType }} bg-{{ ($currentAIUsagePercent >= 80) ? 'danger' : 'primary' }}"
                    style="width: {{ $currentAIUsagePercent  }}%">
                </div>
            </div>
        </div>
    </div>

    <!-- Sendcheckit -->
    <div class="{{ $gridClass }}">
        <div class="{{ $contentClass }}">
            <div class="d-flex mb-2">
                <label class="{{ $textClass }}">{{ trans('ai-content-toolkit::messages.subjectline_credit') }}</label>
                <div class="pull-right text-semibold">
                    <span
                        class="text-muted">{{ number_with_delimiter($currentSendcheckitUsage) }}/{{ ($dailySendcheckitQuota == -1) ? '∞' : number_with_delimiter($dailySendcheckitQuota) }}</span>
                    &nbsp;&nbsp;&nbsp;<span>{{ $currentSendcheckitUsagePercent }}%</span>
                </div>
            </div>

            <div class="progress progress-{{ $progressbarSize }}" {{ $progressbarStyle }}>
                <div class="progress-bar {{ $progressbarType }} bg-{{ ($currentSendcheckitUsagePercent >= 80) ? 'danger' : 'primary' }}"
                    style="width: {{ $currentSendcheckitUsagePercent  }}%">
                </div>
            </div>
        </div>
    </div>

    <!-- Mailterster -->
    <div class="{{ $gridClass }}">
        <div class="{{ $contentClass }}">
            <div class="d-flex mb-2">
                <label class="{{ $textClass }}">{{ trans('ai-content-toolkit::messages.spamscore_credit') }}</label>
                <div class="pull-right text-semibold">
                    <span
                        class="text-muted">{{ number_with_delimiter($currentMailtesterUsage) }}/{{ ($dailyMailtesterQuota == -1) ? '∞' : number_with_delimiter($dailyMailtesterQuota) }}</span>
                    &nbsp;&nbsp;&nbsp;<span>{{ $currentMailtesterUsagePercent }}%</span>
                </div>
            </div>

            <div class="progress progress-{{ $progressbarSize }}" {{ $progressbarStyle }}>
                <div class="progress-bar {{ $progressbarType }} bg-{{ ($currentMailtesterUsagePercent >= 80) ? 'danger' : 'primary' }}"
                    style="width: {{ $currentMailtesterUsagePercent  }}%">
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        if(document.querySelector('.quota_box')) {
            document.querySelector('.quota_box').innerHTML += document.querySelector("#aicontent-quota").innerHTML;
        }
    });
    setTimeout(() => {
        if(document.querySelector('.modal .quota_box')) {
            document.querySelector('.modal .quota_box').innerHTML += document.querySelector(".modal #aicontent-quota").innerHTML;
        }
    }, 100);
</script>