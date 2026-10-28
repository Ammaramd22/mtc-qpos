@php
    $all_notifications = auth()->user()->notifications;
    $unread_notifications = $all_notifications->where('read_at', null);
    $total_unread = count($unread_notifications);

    // Cheque reminders
    $business_id = auth()->user()->business_id;
    $today = now()->toDateString();
    $in3days = now()->addDays(3)->toDateString();
    $cheque_overdue = \App\TransactionPayment::where('business_id', $business_id)
        ->where('method', 'cheque')->where('cheque_status', 'pending')
        ->where('cheque_date', '<', $today)->count();
    $cheque_due_soon = \App\TransactionPayment::where('business_id', $business_id)
        ->where('method', 'cheque')->where('cheque_status', 'pending')
        ->whereBetween('cheque_date', [$today, $in3days])->count();
    $cheque_alert_count = $cheque_overdue + $cheque_due_soon;
    $total_badge = $total_unread + $cheque_alert_count;
@endphp
<!-- Notifications: style can be found in dropdown.less -->
<li class="dropdown notifications-menu tw-list-none">
    <a type="button"
        class="dropdown-toggle load_notifications tw-inline-flex tw-items-center tw-ring-1 tw-ring-white/10 tw-justify-center tw-text-sm tw-font-medium tw-text-white hover:tw-text-white tw-transition-all tw-duration-200 theme-btn-bg tw-p-1.5 tw-rounded-lg"
        data-toggle="dropdown" id="show_unread_notifications" data-loaded="false">
        <span class="tw-sr-only">
            Notifications
        </span>
        <svg aria-hidden="true" class="tw-size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="1.5"
            stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
            <path d="M10 5a2 2 0 1 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6" />
            <path d="M9 17v1a3 3 0 0 0 6 0v-1" />
        </svg>
        <span class="label label-warning notifications_count">@if (!empty($total_badge)){{$total_badge}}@endif</span>
    </a>
    <ul class="dropdown-menu !tw-p-2 !tw-w-80 tw-absolute !tw-right-0 !tw-z-10 !tw-mt-2 !tw-origin-top-right !tw-bg-white !tw-rounded-lg !tw-shadow-lg !tw-ring-1 !tw-ring-gray-200 !focus:tw-outline-none" style="left: auto !important ; height:90vh; overflow-y: scroll;">
        <!-- Cheque reminders pinned at top -->
        @if($cheque_overdue > 0)
        <li class="cheque-reminder-li" style="border-bottom:1px solid #fee2e2; background:#fff5f5; border-radius:8px; margin-bottom:4px;">
            <a href="{{ route('cheque.index') }}" style="display:flex;align-items:flex-start;gap:10px;padding:10px 12px;text-decoration:none;">
                <span style="font-size:20px;margin-top:2px;">🔴</span>
                <span>
                    <strong style="color:#dc2626;font-size:13px;">{{ $cheque_overdue }} Overdue Cheque{{ $cheque_overdue > 1 ? 's' : '' }}</strong><br>
                    <span style="font-size:11px;color:#7f1d1d;">Cheque date has passed — action needed</span>
                </span>
            </a>
        </li>
        @endif
        @if($cheque_due_soon > 0)
        <li class="cheque-reminder-li" style="border-bottom:1px solid #fef3c7; background:#fffbeb; border-radius:8px; margin-bottom:4px;">
            <a href="{{ route('cheque.index') }}" style="display:flex;align-items:flex-start;gap:10px;padding:10px 12px;text-decoration:none;">
                <span style="font-size:20px;margin-top:2px;">🟡</span>
                <span>
                    <strong style="color:#b45309;font-size:13px;">{{ $cheque_due_soon }} Cheque{{ $cheque_due_soon > 1 ? 's' : '' }} Due Within 3 Days</strong><br>
                    <span style="font-size:11px;color:#78350f;">Present to the bank soon</span>
                </span>
            </a>
        </li>
        @endif
        <li>
            <!-- inner menu: contains the actual data -->
            <ul class="menu" id="notifications_list">
            </ul>
        </li>

        @if (count($all_notifications) > 10)
            <li class="footer load_more_li">
                <a href="#" class="load_more_notifications">@lang('lang_v1.load_more')</a>
            </li>
        @endif
    </ul>
</li>

<input type="hidden" id="notification_page" value="1">
<input type="hidden" id="cheque_alert_count" value="{{ $cheque_alert_count }}">
<script>
// Re-apply cheque count to badge after app.js AJAX clears it
(function() {
    var chequeCount = parseInt(document.getElementById('cheque_alert_count').value) || 0;
    if (chequeCount === 0) return;
    // Observe badge text — restore cheque count when app.js clears it to ''
    var badge = document.querySelector('span.notifications_count');
    if (!badge) return;
    var observer = new MutationObserver(function() {
        var current = badge.textContent.trim();
        if (current === '') {
            badge.textContent = chequeCount;
        }
    });
    observer.observe(badge, { childList: true, characterData: true, subtree: true });
})();
</script>
