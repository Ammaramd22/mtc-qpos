<?php

namespace App\Utils;

class SriLankanBankUtil
{
    /**
     * List of all Sri Lankan licensed banks
     */
    public static function getBanks(): array
    {
        return [
            // Licensed Commercial Banks
            'Bank of Ceylon' => 'Bank of Ceylon (BOC)',
            'People\'s Bank' => 'People\'s Bank',
            'Commercial Bank of Ceylon' => 'Commercial Bank of Ceylon (Combank)',
            'Hatton National Bank' => 'Hatton National Bank (HNB)',
            'Sampath Bank' => 'Sampath Bank',
            'Seylan Bank' => 'Seylan Bank',
            'Nations Trust Bank' => 'Nations Trust Bank (NTB)',
            'DFCC Bank' => 'DFCC Bank',
            'National Development Bank' => 'National Development Bank (NDB)',
            'Union Bank of Colombo' => 'Union Bank of Colombo',
            'Pan Asia Banking Corporation' => 'Pan Asia Banking Corporation (PABC)',
            'Amana Bank' => 'Amana Bank',
            'Cargills Bank' => 'Cargills Bank',
            // Foreign / specialized commercial
            'Standard Chartered Bank' => 'Standard Chartered Bank',
            'HSBC' => 'HSBC',
            'Citibank N.A.' => 'Citibank N.A.',
            'Deutsche Bank AG' => 'Deutsche Bank AG',
            'Habib Bank Limited' => 'Habib Bank Limited (HBL)',
            'Indian Bank' => 'Indian Bank',
            'Indian Overseas Bank' => 'Indian Overseas Bank',
            'State Bank of India' => 'State Bank of India (SBI)',
            'Public Bank Berhad' => 'Public Bank Berhad',
            'MCB Bank' => 'MCB Bank',
            // State / specialized
            'National Savings Bank' => 'National Savings Bank (NSB)',
            'Housing Development Finance Corporation Bank' => 'HDFC Bank of Sri Lanka',
            'State Mortgage & Investment Bank' => 'State Mortgage & Investment Bank (SMIB)',
            'Sanasa Development Bank' => 'Sanasa Development Bank (SDB)',
            'Regional Development Bank' => 'Regional Development Bank (RDB)',
            // Finance companies commonly used for cheques
            'LB Finance' => 'LB Finance PLC',
            'Central Finance' => 'Central Finance Company PLC',
            'Commercial Credit & Finance' => 'Commercial Credit & Finance PLC',
            'People\'s Leasing & Finance' => 'People\'s Leasing & Finance PLC',
            'Senkadagala Finance' => 'Senkadagala Finance PLC',
            'Singer Finance' => 'Singer Finance (Lanka) PLC',
            'Vallibel Finance' => 'Vallibel Finance PLC',
            'Softlogic Finance' => 'Softlogic Finance PLC',
            'Orient Finance' => 'Orient Finance PLC',
            'Other' => 'Other / Not listed',
        ];
    }

    /**
     * Check if Sri Lankan banks are enabled for this business.
     * Defaults to ON when the setting is missing (Sri Lanka POS default).
     */
    public static function enabled(): bool
    {
        $business = \App\Business::find(request()->session()->get('user.business_id'));
        if (!$business) {
            return true;
        }
        $posSettings = is_array($business->pos_settings)
            ? $business->pos_settings
            : (json_decode($business->pos_settings ?? '{}', true) ?: []);

        if (!array_key_exists('use_sri_lankan_banks', $posSettings)) {
            return true;
        }

        return !empty($posSettings['use_sri_lankan_banks']) && (int) $posSettings['use_sri_lankan_banks'] === 1;
    }

    /**
     * Get bank options for select dropdown (keyed by bank name)
     */
    public static function options(): array
    {
        $banks = self::getBanks();
        return ['' => '-- Select Bank --'] + $banks;
    }

    /**
     * Convert a number to words (English) for cheque printing.
     * Supports up to crores/millions.
     */
    public static function numberToWords(float $number): string
    {
        $hyphen      = '-';
        $conjunction = ' And ';
        $separator   = ', ';
        $negative    = 'Negative ';
        $dictionary  = [
            0                   => 'Zero',
            1                   => 'One',
            2                   => 'Two',
            3                   => 'Three',
            4                   => 'Four',
            5                   => 'Five',
            6                   => 'Six',
            7                   => 'Seven',
            8                   => 'Eight',
            9                   => 'Nine',
            10                  => 'Ten',
            11                  => 'Eleven',
            12                  => 'Twelve',
            13                  => 'Thirteen',
            14                  => 'Fourteen',
            15                  => 'Fifteen',
            16                  => 'Sixteen',
            17                  => 'Seventeen',
            18                  => 'Eighteen',
            19                  => 'Nineteen',
            20                  => 'Twenty',
            30                  => 'Thirty',
            40                  => 'Forty',
            50                  => 'Fifty',
            60                  => 'Sixty',
            70                  => 'Seventy',
            80                  => 'Eighty',
            90                  => 'Ninety',
            100                 => 'Hundred',
            1000                => 'Thousand',
            100000              => 'Hundred Thousand',
            1000000             => 'Million',
            100000000           => 'Hundred Million',
            1000000000          => 'Billion',
        ];

        if (!is_numeric($number)) {
            return '';
        }

        if ($number < 0) {
            return $negative . self::numberToWords(abs($number));
        }

        $fraction = null;
        if (strpos((string)$number, '.') !== false) {
            [$number, $fraction] = explode('.', (string) round($number, 2));
            $fraction = str_pad($fraction, 2, '0', STR_PAD_RIGHT);
        }

        $number = (int) $number;

        if ($number === 0) {
            $string = $dictionary[0];
        } else {
            $string = '';
            // Billions
            if ($number >= 1000000000) {
                $billions = floor($number / 1000000000);
                $string .= self::numberToWords($billions) . ' ' . $dictionary[1000000000];
                $number %= 1000000000;
                if ($number > 0) {
                    $string .= $separator;
                }
            }
            // Hundred Millions
            if ($number >= 100000000) {
                $hundredMillions = floor($number / 100000000);
                $string .= self::numberToWords($hundredMillions) . ' ' . $dictionary[100];
                $number %= 100000000;
                if ($number > 0) {
                    $string .= ' ';
                }
            }
            // Millions
            if ($number >= 1000000) {
                $millions = floor($number / 1000000);
                $string .= self::numberToWords($millions) . ' ' . $dictionary[1000000];
                $number %= 1000000;
                if ($number > 0) {
                    $string .= $separator;
                }
            }
            // Hundred Thousands
            if ($number >= 100000) {
                $hundredThousands = floor($number / 100000);
                $string .= self::numberToWords($hundredThousands) . ' ' . $dictionary[100];
                $number %= 100000;
                if ($number > 0) {
                    $string .= ' ';
                }
            }
            // Thousands
            if ($number >= 1000) {
                $thousands = floor($number / 1000);
                $string .= self::numberToWords($thousands) . ' ' . $dictionary[1000];
                $number %= 1000;
                if ($number > 0) {
                    $string .= $separator;
                }
            }
            // Hundreds
            if ($number >= 100) {
                $hundreds = floor($number / 100);
                $string .= self::numberToWords($hundreds) . ' ' . $dictionary[100];
                $number %= 100;
                if ($number > 0) {
                    $string .= $conjunction;
                }
            }
            // Tens and ones
            if ($number > 0) {
                if ($string !== '') {
                    $string .= '';
                }
                if ($number < 20) {
                    $string .= $dictionary[$number];
                } else {
                    $tens = floor($number / 10) * 10;
                    $ones = $number % 10;
                    $string .= $dictionary[$tens];
                    if ($ones > 0) {
                        $string .= $hyphen . $dictionary[$ones];
                    }
                }
            }
        }

        if ($fraction !== null && (int)$fraction > 0) {
            $string .= ' And ' . $dictionary[(int)$fraction[0] === 0 ? (int)$fraction[1] : (int)ltrim($fraction, '0')] . ' Cents';
        } else {
            $string .= ' Only';
        }

        return $string;
    }
}
