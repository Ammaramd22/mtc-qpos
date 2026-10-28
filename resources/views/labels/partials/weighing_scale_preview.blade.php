<title>{{__('barcode.print_labels')}} - Weighing Scale</title>
<button class="btn btn-success no-print" onclick="window.print()">Print</button>
<div id="preview_body">
@php
    $loop_count = 0;
    $business = \App\Business::find(session('user.business_id'));
    $business_info = json_decode($business->business_info ?? '{}', true);
@endphp

@foreach($product_details as $details)
    @while($details['qty'] > 0)
        @php
            $loop_count += 1;
            $weight_kg = $details['weight_kg'] ?? 0.100; // Default 100g if not set
            $unit_price = $details['details']->default_sell_price ?? 0;
            $total_price = $unit_price * $weight_kg;
            
            // Generate weighing scale barcode: 0000 + PLU (5 digits) + Weight in grams (4 digits)
            $plu_code = str_pad(substr($details['details']->sub_sku, -5), 5, '0', STR_PAD_LEFT);
            $weight_grams = str_pad(round($weight_kg * 1000), 4, '0', STR_PAD_LEFT);
            $weighing_barcode = '0000' . $plu_code . $weight_grams;
            // Add check digit for EAN-13
            $weighing_barcode = calculateEanCheckDigit($weighing_barcode);
            
            $date = date('Y.m.d');
        @endphp

        <!-- Single Weighing Scale Label -->
        <div class="weighing-scale-label">
            <!-- Product Name -->
            <div class="product-name">
                {{$details['details']->product_actual_name}}
            </div>
            
            <!-- Barcode -->
            <div class="barcode-section">
                <img src="data:image/png;base64,{{DNS1D::getBarcodePNG($weighing_barcode, 'EAN13', 2, 50, array(0, 0, 0), true)}}" alt="{{$weighing_barcode}}">
                <div class="barcode-number">{{$weighing_barcode}}</div>
            </div>
            
            <!-- Date -->
            <div class="date-section">
                <span class="label">Date:</span>
                <span class="value">{{$date}}</span>
            </div>
            
            <!-- Price Grid -->
            <table class="price-grid">
                <tr>
                    <th>
                        <small>Unit Price(Rs./kg)</small>
                        <div class="amount">{{@num_format($unit_price)}}</div>
                    </th>
                    <th>
                        <small>Weight (kg)</small>
                        <div class="amount">{{number_format($weight_kg, 3)}}</div>
                    </th>
                    <th>
                        <small>Total Price(Rs.)</small>
                        <div class="amount">{{@num_format($total_price)}}</div>
                    </th>
                </tr>
            </table>
            
            <!-- Store Info -->
            <div class="store-section">
                <div class="store-name">{{$business_name}}</div>
                <div class="store-details">
                    <div class="left">
                        {{$business_info['address'] ?? ''}}
                    </div>
                    <div class="right">
                        <div>Batch No.:</div>
                        <div>Tel: {{$business_info['phone'] ?? ''}}</div>
                        <div>{{$business_info['tax_number_1'] ?? ''}}</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Page break after each label for continuous roll -->
        @if(!$loop->last || $details['qty'] > 1)
            <div style="page-break-after: always;"></div>
        @endif

        @php
            $details['qty'] = $details['qty'] - 1;
        @endphp
    @endwhile
@endforeach

</div>

@php
function calculateEanCheckDigit($barcode) {
    $barcode = str_pad($barcode, 12, '0', STR_PAD_LEFT);
    $sum = 0;
    for ($i = 0; $i < 12; $i++) {
        $digit = (int)$barcode[$i];
        $sum += ($i % 2 === 0) ? $digit : $digit * 3;
    }
    $check_digit = (10 - ($sum % 10)) % 10;
    return $barcode . $check_digit;
}
@endphp

<style type="text/css">
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        font-family: 'Arial', sans-serif;
        font-size: 10px;
        line-height: 1.2;
    }
    
    .weighing-scale-label {
        width: 60mm;
        padding: 3mm;
        border: 1px solid #000;
        margin: 0 auto;
        background: #fff;
    }
    
    .product-name {
        text-align: center;
        font-size: 14px;
        font-weight: bold;
        text-transform: capitalize;
        margin-bottom: 2mm;
        min-height: 18px;
    }
    
    .barcode-section {
        text-align: center;
        margin: 2mm 0;
    }
    
    .barcode-section img {
        max-width: 100%;
        height: 12mm;
    }
    
    .barcode-number {
        font-size: 9px;
        letter-spacing: 1px;
        margin-top: 1mm;
    }
    
    .date-section {
        margin: 2mm 0;
        font-size: 9px;
    }
    
    .date-section .label {
        border: 1px solid #000;
        padding: 0 2mm;
        display: inline-block;
    }
    
    .date-section .value {
        border: 1px solid #000;
        border-left: none;
        padding: 0 2mm;
        display: inline-block;
        min-width: 20mm;
    }
    
    .price-grid {
        width: 100%;
        border-collapse: collapse;
        margin: 2mm 0;
    }
    
    .price-grid th {
        border: 1px solid #000;
        padding: 1mm;
        text-align: center;
        width: 33.33%;
    }
    
    .price-grid th small {
        font-size: 7px;
        display: block;
        line-height: 1;
    }
    
    .price-grid th .amount {
        font-size: 14px;
        font-weight: bold;
        margin-top: 1mm;
    }
    
    .store-section {
        margin-top: 2mm;
        border: 1px solid #000;
        padding: 2mm;
    }
    
    .store-name {
        font-size: 11px;
        font-weight: bold;
        text-transform: uppercase;
        margin-bottom: 1mm;
    }
    
    .store-details {
        display: flex;
        justify-content: space-between;
        font-size: 7px;
    }
    
    .store-details .left {
        max-width: 60%;
    }
    
    .store-details .right {
        text-align: right;
    }
    
    .store-details .right div {
        margin-bottom: 0.5mm;
    }
    
    @media print {
        body {
            margin: 0;
            padding: 0;
        }
        
        .no-print {
            display: none !important;
        }
        
        .weighing-scale-label {
            page-break-inside: avoid;
            border: none;
        }
        
        @page {
            size: 60mm auto;
            margin: 0;
        }
    }
</style>

<script type="text/javascript">
    // Auto-print when page loads
    window.onload = function() {
        setTimeout(function() {
            window.print();
        }, 500);
    };
</script>
