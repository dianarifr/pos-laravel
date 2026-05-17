<div
    class="flex items-center justify-between gap-2 p-2 dark:bg-slate-800 rounded-lg border border-gray-300 dark:border-slate-700">
    <div id="captcha-img">
        {!! captcha_img('flat') !!}
    </div>
    <button type="button" onclick="reloadCaptcha()"
        class="text-xs text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 flex flex-col items-center gap-1">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        <span>Refresh</span>
    </button>
</div>