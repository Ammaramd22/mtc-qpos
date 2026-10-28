@php
	$use_avco = session('business.accounting_method') === 'avco';
	$show_pe = function_exists('nutmeg_enabled') && nutmeg_enabled();
	$stock_product_expenses = $stock_product_expenses ?? [];
@endphp

<div class="table-responsive pvs-table-scroll">
	<table class="pvs-table table table-bordered">
		<thead>
			<tr>
				<th>SKU</th>
				<th>@lang('business.product')</th>
				<th>@lang('business.location')</th>
				<th class="num">
					@lang('lang_v1.unit_cost')
					@if($use_avco)
						<small>(@lang('lang_v1.by_average_cost'))</small>
					@endif
				</th>
				<th class="num">@lang('sale.unit_price')</th>
				<th class="num">@lang('report.current_stock')</th>
				<th class="num">
					@lang('lang_v1.total_stock_price')
					@if($use_avco)
						<small>(@lang('lang_v1.by_average_cost'))</small>
					@endif
				</th>
				<th class="num">@lang('report.total_unit_sold')</th>
				<th class="num">@lang('lang_v1.total_unit_transfered')</th>
				<th class="num">@lang('lang_v1.total_unit_adjusted')</th>
			</tr>
		</thead>
		<tbody>
			@foreach($product_stock_details as $product)
				@php
					$stock = (float) ($product->stock ?? 0);
					$sell_price = (float) ($product->unit_price ?? 0);
					if ($use_avco) {
						$unit_cost = (float) ($product->dpp_inc_tax ?? 0);
						$stock_value = $stock * $unit_cost;
					} else {
						$stock_value = (float) ($product->stock_price ?? 0);
						$unit_cost = $stock > 0
							? ($stock_value / $stock)
							: (float) ($product->dpp_inc_tax ?? 0);
					}
					$name = $product->product;
					if ($product->type == 'variable') {
						$name .= ' - '.$product->product_variation.'-'.$product->variation_name;
					}
				@endphp
				<tr>
					<td>{{ $product->sku }}</td>
					<td>{{ $name }}</td>
					<td>{{ $product->location_name }}</td>
					<td class="num"><span class="display_currency" data-currency_symbol="true">{{ $unit_cost }}</span></td>
					<td class="num"><span class="display_currency" data-currency_symbol="true">{{ $sell_price }}</span></td>
					<td class="num">
						<span data-is_quantity="true" class="display_currency" data-currency_symbol="false">{{ $stock }}</span>
						{{ $product->unit }}
					</td>
					<td class="num"><span class="display_currency" data-currency_symbol="true">{{ $stock_value }}</span></td>
					<td class="num">
						<span data-is_quantity="true" class="display_currency" data-currency_symbol="false">{{ $product->total_sold ?? 0 }}</span>
						{{ $product->unit }}
					</td>
					<td class="num">
						<span data-is_quantity="true" class="display_currency" data-currency_symbol="false">{{ $product->total_transfered ?? 0 }}</span>
						{{ $product->unit }}
					</td>
					<td class="num">
						<span data-is_quantity="true" class="display_currency" data-currency_symbol="false">{{ $product->total_adjusted ?? 0 }}</span>
						{{ $product->unit }}
					</td>
				</tr>
			@endforeach
		</tbody>
	</table>
</div>

@if($show_pe)
	@php
		$has_any_pe = false;
		foreach ($product_stock_details as $product) {
			$pe_key = ($product->variation_id ?? '').'_'.($product->location_id ?? '');
			$pe = $stock_product_expenses[$pe_key] ?? null;
			if (!empty($pe) && ($pe['remaining_expense_total'] ?? 0) > 0) {
				$has_any_pe = true;
				break;
			}
		}
	@endphp
	@if($has_any_pe)
		<div class="pvs-expenses">
			<h5>@lang('lang_v1.product_expenses') <small>(@lang('lang_v1.in_remaining_stock'))</small></h5>
			@foreach($product_stock_details as $product)
				@php
					$pe_key = ($product->variation_id ?? '').'_'.($product->location_id ?? '');
					$pe = $stock_product_expenses[$pe_key] ?? null;
					if (empty($pe) || ($pe['remaining_expense_total'] ?? 0) <= 0) {
						continue;
					}
					$name = $product->product;
					if ($product->type == 'variable') {
						$name .= ' - '.$product->product_variation.'-'.$product->variation_name;
					}
				@endphp
				<div class="pvs-exp-block">
					<div class="pvs-exp-head">
						<strong>{{ $name }}</strong>
						<span class="text-muted">· {{ $product->location_name }}</span>
					</div>
					<div class="pvs-exp-grid">
						<div class="pvs-exp-item">
							<div class="lbl">@lang('lang_v1.expense_total')</div>
							<div class="val"><span class="display_currency" data-currency_symbol="true">{{ $pe['remaining_expense_total'] }}</span></div>
						</div>
						<div class="pvs-exp-item">
							<div class="lbl">@lang('lang_v1.per_unit')</div>
							<div class="val"><span class="display_currency" data-currency_symbol="true">{{ $pe['remaining_expense_per_unit'] }}</span></div>
						</div>
						@foreach(($pe['breakdown'] ?? []) as $exp)
							<div class="pvs-exp-item">
								<div class="lbl">{{ $exp['name'] }}</div>
								<div class="val"><span class="display_currency" data-currency_symbol="true">{{ $exp['amount'] }}</span></div>
							</div>
						@endforeach
					</div>
				</div>
			@endforeach
		</div>
	@endif
@endif
