@php
    $custom_labels = json_decode(session('business.custom_labels'), true);
    $bank_details = !empty($user->bank_details) ? json_decode($user->bank_details, true) : [];

    $vuVal = function ($value) {
        $value = is_string($value) ? trim($value) : $value;
        if ($value === null || $value === '') {
            return ['—', true];
        }
        return [$value, false];
    };
@endphp

<div class="vu-details">
    <div class="vu-details-section">
        <h4 class="vu-details-heading">@lang('lang_v1.more_info')</h4>
        <div class="vu-fields">
            @php
                $dob_fmt = !empty($user->dob)
                    ? \Carbon::createFromTimestamp(strtotime($user->dob))->format(session('business.date_format'))
                    : '';
                [$v, $empty] = $vuVal($dob_fmt);
            @endphp
            <div class="vu-field">
                <span class="vu-field-label">@lang('lang_v1.dob')</span>
                <span class="vu-field-value {{ $empty ? 'is-empty' : '' }}">{{ $v }}</span>
            </div>

            @php [$v, $empty] = $vuVal(!empty($user->gender) ? __('lang_v1.'.$user->gender) : ''); @endphp
            <div class="vu-field">
                <span class="vu-field-label">@lang('lang_v1.gender')</span>
                <span class="vu-field-value {{ $empty ? 'is-empty' : '' }}">{{ $v }}</span>
            </div>

            @php [$v, $empty] = $vuVal(!empty($user->marital_status) ? __('lang_v1.'.$user->marital_status) : ''); @endphp
            <div class="vu-field">
                <span class="vu-field-label">@lang('lang_v1.marital_status')</span>
                <span class="vu-field-value {{ $empty ? 'is-empty' : '' }}">{{ $v }}</span>
            </div>

            @php [$v, $empty] = $vuVal($user->blood_group ?? ''); @endphp
            <div class="vu-field">
                <span class="vu-field-label">@lang('lang_v1.blood_group')</span>
                <span class="vu-field-value {{ $empty ? 'is-empty' : '' }}">{{ $v }}</span>
            </div>

            @php [$v, $empty] = $vuVal($user->contact_number ?? ''); @endphp
            <div class="vu-field">
                <span class="vu-field-label">@lang('lang_v1.mobile_number')</span>
                <span class="vu-field-value {{ $empty ? 'is-empty' : '' }}">{{ $v }}</span>
            </div>

            @php [$v, $empty] = $vuVal($user->alt_number ?? ''); @endphp
            <div class="vu-field">
                <span class="vu-field-label">@lang('business.alternate_number')</span>
                <span class="vu-field-value {{ $empty ? 'is-empty' : '' }}">{{ $v }}</span>
            </div>

            @php [$v, $empty] = $vuVal($user->family_number ?? ''); @endphp
            <div class="vu-field">
                <span class="vu-field-label">@lang('lang_v1.family_contact_number')</span>
                <span class="vu-field-value {{ $empty ? 'is-empty' : '' }}">{{ $v }}</span>
            </div>

            @php [$v, $empty] = $vuVal($user->fb_link ?? ''); @endphp
            <div class="vu-field">
                <span class="vu-field-label">@lang('lang_v1.fb_link')</span>
                <span class="vu-field-value {{ $empty ? 'is-empty' : '' }}">{{ $v }}</span>
            </div>

            @php [$v, $empty] = $vuVal($user->twitter_link ?? ''); @endphp
            <div class="vu-field">
                <span class="vu-field-label">@lang('lang_v1.twitter_link')</span>
                <span class="vu-field-value {{ $empty ? 'is-empty' : '' }}">{{ $v }}</span>
            </div>

            @php [$v, $empty] = $vuVal($user->social_media_1 ?? ''); @endphp
            <div class="vu-field">
                <span class="vu-field-label">@lang('lang_v1.social_media', ['number' => 1])</span>
                <span class="vu-field-value {{ $empty ? 'is-empty' : '' }}">{{ $v }}</span>
            </div>

            @php [$v, $empty] = $vuVal($user->social_media_2 ?? ''); @endphp
            <div class="vu-field">
                <span class="vu-field-label">@lang('lang_v1.social_media', ['number' => 2])</span>
                <span class="vu-field-value {{ $empty ? 'is-empty' : '' }}">{{ $v }}</span>
            </div>

            @php [$v, $empty] = $vuVal($user->id_proof_name ?? ''); @endphp
            <div class="vu-field">
                <span class="vu-field-label">@lang('lang_v1.id_proof_name')</span>
                <span class="vu-field-value {{ $empty ? 'is-empty' : '' }}">{{ $v }}</span>
            </div>

            @php [$v, $empty] = $vuVal($user->id_proof_number ?? ''); @endphp
            <div class="vu-field">
                <span class="vu-field-label">@lang('lang_v1.id_proof_number')</span>
                <span class="vu-field-value {{ $empty ? 'is-empty' : '' }}">{{ $v }}</span>
            </div>

            @php [$v, $empty] = $vuVal($user->custom_field_1 ?? ''); @endphp
            <div class="vu-field">
                <span class="vu-field-label">{{ $custom_labels['user']['custom_field_1'] ?? __('lang_v1.user_custom_field1') }}</span>
                <span class="vu-field-value {{ $empty ? 'is-empty' : '' }}">{{ $v }}</span>
            </div>

            @php [$v, $empty] = $vuVal($user->custom_field_2 ?? ''); @endphp
            <div class="vu-field">
                <span class="vu-field-label">{{ $custom_labels['user']['custom_field_2'] ?? __('lang_v1.user_custom_field2') }}</span>
                <span class="vu-field-value {{ $empty ? 'is-empty' : '' }}">{{ $v }}</span>
            </div>

            @php [$v, $empty] = $vuVal($user->custom_field_3 ?? ''); @endphp
            <div class="vu-field">
                <span class="vu-field-label">{{ $custom_labels['user']['custom_field_3'] ?? __('lang_v1.user_custom_field3') }}</span>
                <span class="vu-field-value {{ $empty ? 'is-empty' : '' }}">{{ $v }}</span>
            </div>

            @php [$v, $empty] = $vuVal($user->custom_field_4 ?? ''); @endphp
            <div class="vu-field">
                <span class="vu-field-label">{{ $custom_labels['user']['custom_field_4'] ?? __('lang_v1.user_custom_field4') }}</span>
                <span class="vu-field-value {{ $empty ? 'is-empty' : '' }}">{{ $v }}</span>
            </div>
        </div>
    </div>

    <div class="vu-details-section">
        <h4 class="vu-details-heading">@lang('business.address')</h4>
        <div class="vu-addresses">
            @php [$v, $empty] = $vuVal($user->permanent_address ?? ''); @endphp
            <div class="vu-address-card">
                <span class="vu-field-label">@lang('lang_v1.permanent_address')</span>
                <span class="vu-field-value {{ $empty ? 'is-empty' : '' }}">{{ $v }}</span>
            </div>
            @php [$v, $empty] = $vuVal($user->current_address ?? ''); @endphp
            <div class="vu-address-card">
                <span class="vu-field-label">@lang('lang_v1.current_address')</span>
                <span class="vu-field-value {{ $empty ? 'is-empty' : '' }}">{{ $v }}</span>
            </div>
        </div>
    </div>

    <div class="vu-details-section">
        <h4 class="vu-details-heading">@lang('lang_v1.bank_details')</h4>
        <div class="vu-fields">
            @php [$v, $empty] = $vuVal($bank_details['account_holder_name'] ?? ''); @endphp
            <div class="vu-field">
                <span class="vu-field-label">@lang('lang_v1.account_holder_name')</span>
                <span class="vu-field-value {{ $empty ? 'is-empty' : '' }}">{{ $v }}</span>
            </div>
            @php [$v, $empty] = $vuVal($bank_details['account_number'] ?? ''); @endphp
            <div class="vu-field">
                <span class="vu-field-label">@lang('lang_v1.account_number')</span>
                <span class="vu-field-value {{ $empty ? 'is-empty' : '' }}">{{ $v }}</span>
            </div>
            @php [$v, $empty] = $vuVal($bank_details['bank_name'] ?? ''); @endphp
            <div class="vu-field">
                <span class="vu-field-label">@lang('lang_v1.bank_name')</span>
                <span class="vu-field-value {{ $empty ? 'is-empty' : '' }}">{{ $v }}</span>
            </div>
            @php [$v, $empty] = $vuVal($bank_details['bank_code'] ?? ''); @endphp
            <div class="vu-field">
                <span class="vu-field-label">@lang('lang_v1.bank_code')</span>
                <span class="vu-field-value {{ $empty ? 'is-empty' : '' }}">{{ $v }}</span>
            </div>
            @php [$v, $empty] = $vuVal($bank_details['branch'] ?? ''); @endphp
            <div class="vu-field">
                <span class="vu-field-label">@lang('lang_v1.branch')</span>
                <span class="vu-field-value {{ $empty ? 'is-empty' : '' }}">{{ $v }}</span>
            </div>
            @php [$v, $empty] = $vuVal($bank_details['tax_payer_id'] ?? ''); @endphp
            <div class="vu-field">
                <span class="vu-field-label">@lang('lang_v1.tax_payer_id')</span>
                <span class="vu-field-value {{ $empty ? 'is-empty' : '' }}">{{ $v }}</span>
            </div>
        </div>
    </div>

    @if(!empty($view_partials))
        @foreach($view_partials as $partial)
            {!! $partial !!}
        @endforeach
    @endif
</div>
