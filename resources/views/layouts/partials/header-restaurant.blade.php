@inject('request', 'Illuminate\Http\Request')
<div class="row no-print">
  <div class="col-md-12">
    <button type="button" class="qpos-theme-toggle btn btn-default btn-flat btn-sm m-5 pull-right" title="Switch to light theme" aria-label="Switch to light theme">
        <i class="fi fi-rr-sun qpos-theme-toggle-icon"></i>
    </button>
    <a href="{{ action([\App\Http\Controllers\HomeController::class, 'index'])}}" title="{{ __('lang_v1.go_back') }}" data-toggle="tooltip" data-placement="bottom" class="btn btn-info btn-flat m-6 hidden-xs btn-sm m-5 pull-right">
        <strong><i class="fa fa-backward fa-lg"></i></strong>
    </a>
  </div>
</div>
