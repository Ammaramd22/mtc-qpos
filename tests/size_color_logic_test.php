<?php

/**
 * Logic checks for size and colour parsing. No database connection.
 * Run: php tests/size_color_logic_test.php
 */

require __DIR__.'/../vendor/autoload.php';

use App\Utils\ProductSizeColor;

$failed = 0;

function check($label, $expected, $actual)
{
    global $failed;
    if ($expected !== $actual) {
        $failed++;
        fwrite(STDERR, "FAIL {$label}\n expected ".var_export($expected, true)."\n actual ".var_export($actual, true)."\n");

        return;
    }
    echo "OK {$label}\n";
}

check('split sizes', ['S', 'M', 'L', 'XL'], ProductSizeColor::splitList('S, M, L, XL'));
check('split colours', ['Black', 'White', 'Red', 'Navy Blue'], ProductSizeColor::splitList('Black, White, Red, Navy Blue'));
check('dedupe colours', ['Black', 'white'], ProductSizeColor::splitList('Black, white, Black'));
check('empty', [], ProductSizeColor::splitList('  '));
check('single free size', ['Free Size'], ProductSizeColor::splitList('Free Size'));
check('join', 'S, M, L', ProductSizeColor::joinList([' S ', 'M', 'L']));
check('join empty', null, ProductSizeColor::joinList(['', '  ']));
check('duplicate colour', 'red', ProductSizeColor::duplicateColor(['Red', ' red ']));
check('unique colours', null, ProductSizeColor::duplicateColor(['Black', 'White', 'Navy Blue']));
check('blank name', null, ProductSizeColor::cleanName('   '));
check('collapse spaces', 'Navy Blue', ProductSizeColor::cleanName("  Navy   Blue  "));

if ($failed > 0) {
    fwrite(STDERR, "{$failed} failed\n");
    exit(1);
}

echo "ALL PASSED\n";
