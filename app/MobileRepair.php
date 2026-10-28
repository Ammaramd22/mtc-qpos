<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MobileRepair extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'warranty_covered' => 'boolean',
        'received_at' => 'datetime',
        'completed_at' => 'datetime',
        'phone_check' => 'array',
    ];

    /**
     * Intake checklist items tested before accepting a phone for repair.
     */
    public static function phoneCheckItems(): array
    {
        return [
            'power_on' => __('lang_v1.phone_check_power_on'),
            'touch_screen' => __('lang_v1.phone_check_touch_screen'),
            'display' => __('lang_v1.phone_check_display'),
            'front_camera' => __('lang_v1.phone_check_front_camera'),
            'rear_camera' => __('lang_v1.phone_check_rear_camera'),
            'speakers' => __('lang_v1.phone_check_speakers'),
            'microphone' => __('lang_v1.phone_check_microphone'),
            'charging' => __('lang_v1.phone_check_charging'),
            'battery' => __('lang_v1.phone_check_battery'),
            'wifi' => __('lang_v1.phone_check_wifi'),
            'bluetooth' => __('lang_v1.phone_check_bluetooth'),
            'buttons' => __('lang_v1.phone_check_buttons'),
            'biometric' => __('lang_v1.phone_check_biometric'),
            'sim_network' => __('lang_v1.phone_check_sim_network'),
            'body_condition' => __('lang_v1.phone_check_body_condition'),
            'water_damage' => __('lang_v1.phone_check_water_damage'),
        ];
    }

    public static function phoneCheckStatuses(): array
    {
        return [
            'ok' => __('lang_v1.phone_check_ok'),
            'faulty' => __('lang_v1.phone_check_faulty'),
            'na' => __('lang_v1.phone_check_na'),
        ];
    }

    public function imei()
    {
        return $this->belongsTo(MobileImei::class, 'mobile_imei_id');
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
