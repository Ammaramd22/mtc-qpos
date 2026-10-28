@extends('layouts.app')
@section('title', __('lang_v1.my_profile'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('lang_v1.my_profile')</h1>
    <!-- <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol> -->
</section>

<!-- Main content -->
<section class="content">
{!! Form::open(['url' => action([\App\Http\Controllers\UserController::class, 'updatePassword']), 'method' => 'post', 'id' => 'edit_password_form',
            'class' => 'form-horizontal' ]) !!}
<div class="row">
    <div class="col-sm-12">
        <div class="box box-solid"> <!--business info box start-->
            <div class="box-header">
                <div class="box-header">
                    <h3 class="box-title"> @lang('user.change_password')</h3>
                </div>
            </div>
            <div class="box-body">
                <div class="form-group">
                    {!! Form::label('current_password', __('user.current_password') . ':', ['class' => 'col-sm-3 control-label']) !!}
                    <div class="col-sm-9">
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-lock"></i>
                            </span>
                            {!! Form::password('current_password', ['class' => 'form-control','placeholder' => __('user.current_password'), 'required']); !!}
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    {!! Form::label('new_password', __('user.new_password') . ':', ['class' => 'col-sm-3 control-label']) !!}
                    <div class="col-sm-9">
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-lock"></i>
                            </span>
                            {!! Form::password('new_password', ['class' => 'form-control','placeholder' => __('user.new_password'), 'required']); !!}
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    {!! Form::label('confirm_password', __('user.confirm_new_password') . ':', ['class' => 'col-sm-3 control-label']) !!}
                    <div class="col-sm-9">
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-lock"></i>
                            </span>
                            {!! Form::password('confirm_password', ['class' => 'form-control','placeholder' =>  __('user.confirm_new_password'), 'required']); !!}
                        </div>
                    </div>
                </div>
                <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white pull-right">@lang('messages.update')</button>
            </div>
        </div>
    </div>
</div>
{!! Form::close() !!}
{!! Form::open(['url' => action([\App\Http\Controllers\UserController::class, 'updateLoginPin']), 'method' => 'post', 'id' => 'edit_login_pin_form',
            'class' => 'form-horizontal' ]) !!}
<div class="row">
    <div class="col-sm-12">
        <div class="box box-solid">
            <div class="box-header">
                <h3 class="box-title">@lang('lang_v1.login_pin')</h3>
            </div>
            <div class="box-body">
                <p class="text-muted">@lang('lang_v1.pin_login_hint')</p>
                <div class="form-group">
                    {!! Form::label('pin_current_password', __('user.current_password') . ':', ['class' => 'col-sm-3 control-label']) !!}
                    <div class="col-sm-9">
                        <div class="input-group">
                            <span class="input-group-addon"><i class="fa fa-lock"></i></span>
                            {!! Form::password('current_password', ['class' => 'form-control', 'placeholder' => __('user.current_password'), 'required']); !!}
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    {!! Form::label('pin', __('lang_v1.new_pin') . ':', ['class' => 'col-sm-3 control-label']) !!}
                    <div class="col-sm-9">
                        <div class="input-group">
                            <span class="input-group-addon"><i class="fa fa-key"></i></span>
                            {!! Form::password('pin', ['class' => 'form-control', 'placeholder' => __('lang_v1.pin_must_be_4_to_6_digits'), 'inputmode' => 'numeric', 'maxlength' => 6]); !!}
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    {!! Form::label('pin_confirmation', __('lang_v1.confirm_pin') . ':', ['class' => 'col-sm-3 control-label']) !!}
                    <div class="col-sm-9">
                        <div class="input-group">
                            <span class="input-group-addon"><i class="fa fa-key"></i></span>
                            {!! Form::password('pin_confirmation', ['class' => 'form-control', 'placeholder' => __('lang_v1.confirm_pin'), 'inputmode' => 'numeric', 'maxlength' => 6]); !!}
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-sm-offset-3 col-sm-9">
                        <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">@lang('lang_v1.update_login_pin')</button>
                        @if (!empty($user->getAttributes()['login_pin'] ?? null))
                            <button type="submit" name="remove_pin" value="1" class="tw-dw-btn tw-dw-btn-outline tw-ml-2" formaction="{{ action([\App\Http\Controllers\UserController::class, 'updateLoginPin']) }}">@lang('lang_v1.remove_login_pin')</button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
{!! Form::close() !!}
{!! Form::open(['url' => action([\App\Http\Controllers\UserController::class, 'updateProfile']), 'method' => 'post', 'id' => 'edit_user_profile_form', 'files' => true ]) !!}
<div class="row">
    <div class="col-sm-8">
        <div class="box box-solid"> <!--business info box start-->
            <div class="box-header">
                <div class="box-header">
                    <h3 class="box-title"> @lang('user.edit_profile')</h3>
                </div>
            </div>
            <div class="box-body">
                <div class="form-group col-md-2">
                    {!! Form::label('surname', __('business.prefix') . ':') !!}
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-info"></i>
                        </span>
                        {!! Form::text('surname', $user->surname, ['class' => 'form-control','placeholder' => __('business.prefix_placeholder')]); !!}
                    </div>
                </div>
                <div class="form-group col-md-5">
                    {!! Form::label('first_name', __('business.first_name') . ':') !!}
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-info"></i>
                        </span>
                        {!! Form::text('first_name', $user->first_name, ['class' => 'form-control','placeholder' => __('business.first_name'), 'required']); !!}
                    </div>
                </div>
                <div class="form-group col-md-5">
                    {!! Form::label('last_name', __('business.last_name') . ':') !!}
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-info"></i>
                        </span>
                        {!! Form::text('last_name', $user->last_name, ['class' => 'form-control','placeholder' => __('business.last_name')]); !!}
                    </div>
                </div>
                <div class="form-group col-md-6">
                    {!! Form::label('email', __('business.email') . ':') !!}
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-info"></i>
                        </span>
                        {!! Form::email('email',  $user->email, ['class' => 'form-control','placeholder' => __('business.email') ]); !!}
                    </div>
                </div>
                <div class="form-group col-md-6">
                    {!! Form::label('language', __('business.language') . ':') !!}
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-info"></i>
                        </span>
                        {!! Form::select('language',$languages, $user->language, ['class' => 'form-control select2']); !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        @component('components.widget', ['title' => __('lang_v1.profile_photo')])
            @if(!empty($user->media))
                <div class="col-md-12 text-center">
                    {!! $user->media->thumbnail([150, 150], 'img-circle') !!}
                </div>
            @endif
            <div class="col-md-12">
                <div class="form-group">
                    {!! Form::label('profile_photo', __('lang_v1.upload_image') . ':') !!}
                    {!! Form::file('profile_photo', ['id' => 'profile_photo', 'accept' => 'image/*']); !!}
                    <small><p class="help-block">@lang('purchase.max_file_size', ['size' => (config('constants.document_size_limit') / 1000000)])</p></small>
                </div>
            </div>
        @endcomponent
    </div>
</div>
@include('user.edit_profile_form_part', ['bank_details' => !empty($user->bank_details) ? json_decode($user->bank_details, true) : null])
@php
    $user_bank = !empty($user->bank_details) ? json_decode($user->bank_details, true) : [];
    $user_ic_key = $user_bank['ic_shortcut_key'] ?? '';
@endphp
<div class="row">
    <div class="col-md-12">
        @component('components.widget', ['title' => 'IC Mode Settings'])
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('ic_shortcut_key', 'IC Mode Shortcut Key') !!}
                    <input type="text" name="ic_shortcut_key" id="ic_shortcut_key_profile"
                        class="form-control"
                        value="{{ $user_ic_key }}"
                        placeholder="Leave blank to use business default"
                        readonly>
                    <p class="help-block">Click the field and press any key to set your personal shortcut. Leave blank to use the business default.</p>
                </div>
            </div>
        @endcomponent
    </div>
</div>
<div class="row">
    <div class="col-md-12 text-center">
        <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white tw-dw-btn-lg">@lang('messages.update')</button>
    </div>
</div>
@push('javascript')
<script>
    $('#ic_shortcut_key_profile').on('keydown', function(e) {
        e.preventDefault();
        $(this).val(e.key);
    }).on('focus', function() {
        $(this).val('').attr('placeholder', 'Press any key...');
    }).on('blur', function() {
        $(this).attr('placeholder', 'Leave blank to use business default');
    });
</script>
@endpush
{!! Form::close() !!}

</section>
<!-- /.content -->
@endsection