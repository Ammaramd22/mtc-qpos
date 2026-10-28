@extends('layouts.app')

@section('title', __( 'lang_v1.view_user' ))

@section('content')
<section class="content vu-page">
    <div class="vu-toolbar">
        <h1 class="vu-title">@lang('lang_v1.view_user')</h1>
        <div class="vu-user-switch">
            {!! Form::select('user_id', $users, $user->id , ['class' => 'form-control select2', 'id' => 'user_id']); !!}
        </div>
    </div>

    <div class="vu-grid">
        <aside class="vu-profile-card">
            @php
                if (isset($user->media->display_url)) {
                    $img_src = $user->media->display_url;
                } else {
                    $img_src = 'https://ui-avatars.com/api/?name='.urlencode($user->first_name.' '.$user->last_name).'&background=f5c518&color=111111&size=160';
                }
            @endphp

            <div class="vu-avatar-wrap">
                <img class="vu-avatar" src="{{ $img_src }}" alt="{{ $user->user_full_name }}">
            </div>

            <h2 class="vu-name">{{ $user->user_full_name }}</h2>
            <p class="vu-role" title="@lang('user.role')">{{ $user->role_name }}</p>

            <dl class="vu-meta">
                <div class="vu-meta-row">
                    <dt>@lang('business.username')</dt>
                    <dd>{{ $user->username }}</dd>
                </div>
                <div class="vu-meta-row">
                    <dt>@lang('business.email')</dt>
                    <dd class="vu-meta-email">{{ $user->email }}</dd>
                </div>
                <div class="vu-meta-row">
                    <dt>{{ __('lang_v1.status_for_user') }}</dt>
                    <dd>
                        @if($user->status == 'active')
                            <span class="vu-badge vu-badge-ok">@lang('business.is_active')</span>
                        @else
                            <span class="vu-badge vu-badge-off">@lang('lang_v1.inactive')</span>
                        @endif
                    </dd>
                </div>
            </dl>

            @can('user.update')
                <a href="{{ action([\App\Http\Controllers\ManageUserController::class, 'edit'], [$user->id]) }}" class="vu-edit-btn">
                    <i class="fas fa-pen"></i>
                    @lang('messages.edit')
                </a>
            @endcan
        </aside>

        <div class="vu-main">
            <div class="nav-tabs-custom vu-tabs">
                <ul class="nav nav-tabs nav-justified">
                    <li class="active">
                        <a href="#user_info_tab" data-toggle="tab" aria-expanded="true">
                            <i class="fas fa-user" aria-hidden="true"></i> @lang('lang_v1.user_info')
                        </a>
                    </li>
                    <li>
                        <a href="#documents_and_notes_tab" data-toggle="tab" aria-expanded="true">
                            <i class="fas fa-paperclip" aria-hidden="true"></i> @lang('lang_v1.documents_and_notes')
                        </a>
                    </li>
                    <li>
                        <a href="#activities_tab" data-toggle="tab" aria-expanded="true">
                            <i class="fas fa-pen-square" aria-hidden="true"></i> @lang('lang_v1.activities')
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane active" id="user_info_tab">
                        <div class="vu-summary">
                            <div class="vu-summary-item">
                                <span class="vu-summary-label">@lang('lang_v1.cmmsn_percent')</span>
                                <span class="vu-summary-value">{{ $user->cmmsn_percent }}%</span>
                            </div>
                            <div class="vu-summary-item">
                                <span class="vu-summary-label">@lang('lang_v1.allowed_contacts')</span>
                                <span class="vu-summary-value">
                                    @php
                                        $selected_contacts = __('lang_v1.all');
                                        if (count($user->contactAccess)) {
                                            $selected_contacts = $user->contactAccess->pluck('name')->implode(', ');
                                        }
                                    @endphp
                                    {{ $selected_contacts }}
                                </span>
                            </div>
                        </div>
                        @include('user.show_details')
                    </div>
                    <div class="tab-pane" id="documents_and_notes_tab">
                        <input type="hidden" name="notable_id" id="notable_id" value="{{ $user->id }}">
                        <input type="hidden" name="notable_type" id="notable_type" value="App\User">
                        <div class="document_note_body"></div>
                    </div>
                    <div class="tab-pane" id="activities_tab">
                        @include('activity_log.activities')
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('css')
<style>
    .vu-page {
        padding-bottom: 32px;
    }
    .vu-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }
    .vu-title {
        margin: 0 !important;
        font-size: 1.65rem !important;
        font-weight: 800 !important;
        color: #f5f5f5 !important;
        letter-spacing: -0.02em;
    }
    .vu-user-switch {
        min-width: 220px;
        max-width: 320px;
        flex: 1;
    }
    .vu-grid {
        display: grid;
        grid-template-columns: 300px minmax(0, 1fr);
        gap: 18px;
        align-items: start;
    }
    @media (max-width: 991px) {
        .vu-grid { grid-template-columns: 1fr; }
    }

    .vu-profile-card {
        background: linear-gradient(160deg, #141414, #111);
        border: 1px solid #2a2a2a;
        border-radius: 16px;
        padding: 24px 20px 20px;
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.35);
        text-align: center;
    }
    .vu-avatar-wrap {
        width: 96px;
        height: 96px;
        margin: 0 auto 14px;
        border-radius: 50%;
        padding: 3px;
        background: linear-gradient(135deg, #f5c518, #a16207);
    }
    .vu-avatar {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
        background: #1a1a1a;
        display: block;
    }
    .vu-name {
        margin: 0 0 4px !important;
        font-size: 1.15rem !important;
        font-weight: 800 !important;
        color: #f5f5f5 !important;
        line-height: 1.3;
        word-break: break-word;
    }
    .vu-role {
        margin: 0 0 18px !important;
        color: #a3a3a3 !important;
        font-size: 0.9rem;
        font-weight: 600;
    }

    .vu-meta {
        margin: 0 0 16px;
        border: 1px solid #2a2a2a;
        border-radius: 12px;
        overflow: hidden;
        background: #0a0a0a;
        text-align: left;
    }
    .vu-meta-row {
        display: grid;
        grid-template-columns: 1fr;
        gap: 2px;
        padding: 12px 14px;
        border-bottom: 1px solid #2a2a2a;
    }
    .vu-meta-row:last-child { border-bottom: 0; }
    .vu-meta-row dt {
        margin: 0;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: #a3a3a3 !important;
    }
    .vu-meta-row dd {
        margin: 0;
        font-size: 0.92rem;
        font-weight: 700;
        color: #f5c518 !important;
        word-break: break-word;
    }
    .vu-meta-email {
        font-size: 0.84rem !important;
        color: #e5e5e5 !important;
    }
    .vu-badge {
        display: inline-flex;
        align-items: center;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 800;
        letter-spacing: 0.02em;
    }
    .vu-badge-ok {
        background: rgba(34, 197, 94, 0.16);
        color: #4ade80 !important;
        border: 1px solid rgba(34, 197, 94, 0.35);
    }
    .vu-badge-off {
        background: rgba(239, 68, 68, 0.16);
        color: #f87171 !important;
        border: 1px solid rgba(239, 68, 68, 0.35);
    }
    .vu-edit-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        padding: 10px 14px;
        border-radius: 10px;
        background: linear-gradient(135deg, #f5c518, #e6b800);
        color: #111 !important;
        font-weight: 800;
        font-size: 0.9rem;
        text-decoration: none !important;
        border: 1px solid rgba(245, 197, 24, 0.45);
        transition: transform .15s, box-shadow .15s;
    }
    .vu-edit-btn:hover {
        color: #111 !important;
        transform: translateY(-1px);
        box-shadow: 0 10px 18px rgba(245, 197, 24, 0.28);
    }

    .vu-main .vu-tabs {
        border-radius: 16px !important;
        overflow: hidden;
        margin-bottom: 0 !important;
    }
    .vu-tabs > .nav-tabs > li > a {
        white-space: nowrap;
    }
    .vu-summary {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 18px;
    }
    @media (max-width: 640px) {
        .vu-summary { grid-template-columns: 1fr; }
    }
    .vu-summary-item {
        background: #0a0a0a;
        border: 1px solid #2a2a2a;
        border-radius: 12px;
        padding: 12px 14px;
    }
    .vu-summary-label {
        display: block;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: #a3a3a3 !important;
        margin-bottom: 4px;
    }
    .vu-summary-value {
        display: block;
        font-size: 0.95rem;
        font-weight: 700;
        color: #f5f5f5 !important;
        word-break: break-word;
    }

    /* Details grid */
    .vu-details { margin-top: 4px; }
    .vu-details-section {
        margin-bottom: 20px;
    }
    .vu-details-section:last-child { margin-bottom: 0; }
    .vu-details-heading {
        margin: 0 0 12px !important;
        font-size: 0.95rem !important;
        font-weight: 800 !important;
        color: #f5c518 !important;
        letter-spacing: 0.02em;
    }
    .vu-fields {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px 14px;
    }
    @media (max-width: 900px) {
        .vu-fields { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 560px) {
        .vu-fields { grid-template-columns: 1fr; }
    }
    .vu-field {
        background: #0a0a0a;
        border: 1px solid #2a2a2a;
        border-radius: 10px;
        padding: 10px 12px;
        min-width: 0;
    }
    .vu-field-label {
        display: block;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #a3a3a3 !important;
        margin-bottom: 3px;
    }
    .vu-field-value {
        display: block;
        font-size: 0.9rem;
        font-weight: 600;
        color: #f5f5f5 !important;
        word-break: break-word;
        line-height: 1.35;
        min-height: 1.2em;
    }
    .vu-field-value.is-empty {
        color: #525252 !important;
        font-weight: 500;
    }
    .vu-addresses {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }
    @media (max-width: 640px) {
        .vu-addresses { grid-template-columns: 1fr; }
    }
    .vu-address-card {
        background: #0a0a0a;
        border: 1px solid #2a2a2a;
        border-radius: 12px;
        padding: 12px 14px;
    }
</style>
@endsection

@section('javascript')
    @include('documents_and_notes.document_and_note_js')

    <script type="text/javascript">
        $(document).ready(function () {
            $('#user_id').change(function () {
                if ($(this).val()) {
                    window.location = "{{ url('/users') }}/" + $(this).val();
                }
            });
        });
    </script>
@endsection
