@if(!empty($customer_vehicles_enabled))
<div class="col-sm-3 mt-56">
    <strong><i class="fa fa-car"></i> @lang('lang_v1.customer_vehicles')</strong>
    @if($customer_vehicles->isEmpty())
        <p class="text-muted">@lang('lang_v1.no_vehicles_for_customer')</p>
    @else
        <ul class="list-unstyled tw-mt-1">
            @foreach($customer_vehicles as $vehicle)
                <li>
                    <strong>{{ $vehicle->registration_number }}</strong>
                    @if(($vehicle->jobs_count ?? 0) > 0)
                        <small class="text-muted">({{ $vehicle->jobs_count }} @lang('lang_v1.jobs'))</small>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
    @can('customer.update')
        <a href="#"
           data-href="{{ action([\App\Http\Controllers\ContactController::class, 'manageVehicles'], [$contact->id]) }}"
           class="btn-modal tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-primary"
           data-container=".view_modal">
            <i class="fa fa-car"></i> @lang('lang_v1.manage_vehicles')
        </a>
    @endcan
</div>
@endif
