<?php

return [
    'name' => 'QRoll by AVENQUE',
    'module_version' => '1.0.0',
    'pid' => 0,
    'author' => 'AVENQUE',

    /*
    | Internal length base unit for all stock math.
    | All opening/remaining/sold lengths are normalised to millimetres.
    */
    'base_length_unit' => 'mm',

    /*
    | Default display precision for length (decimal places).
    | Money precision still follows business.currency_precision.
    */
    'measurement_precision' => 4,

    'width_units' => ['inch', 'foot', 'cm', 'metre'],

    'length_units' => ['foot', 'inch', 'foot_inch', 'metre', 'cm', 'yard'],

    'billing_methods' => [
        'per_running_foot',
        'per_running_metre',
        'per_running_yard',
        'per_square_foot',
        'per_square_metre',
        'full_roll',
        'fixed_piece',
        'manual',
    ],

    'roll_statuses' => [
        'available',
        'partially_used',
        'reserved',
        'fully_sold',
        'damaged',
        'quarantined',
        'returned',
        'transferred',
        'archived',
    ],

    'movement_types' => [
        'opening_stock',
        'purchase_receive',
        'sale',
        'sale_return',
        'purchase_return',
        'transfer_out',
        'transfer_in',
        'adjustment_add',
        'adjustment_remove',
        'damage',
        'reservation',
        'reservation_release',
        'remnant_created',
        'remnant_sold',
        'data_correction',
    ],
];
