<?php

/**
 * Label size/color choice and saved layout checks. No database connection.
 * Run: php tests/label_editor_logic_test.php
 */

require __DIR__.'/../vendor/autoload.php';

use App\Utils\BrowserLabelLayout;
use App\Utils\ProductSizeColor;

$failed = 0;

function check($label, $expected, $actual)
{
    global $failed;
    $same = $expected === $actual;
    if (is_array($expected) && is_array($actual)) {
        $same = json_encode($expected) === json_encode($actual);
    }
    if (! $same) {
        $failed++;
        fwrite(STDERR, "FAIL {$label}\n expected ".json_encode($expected)."\n actual ".json_encode($actual)."\n");

        return;
    }
    echo "OK {$label}\n";
}

$sizes = ['Small', 'Medium', 'Large'];
check('joined list is not printed', 'Small', ProductSizeColor::chooseOne($sizes, 'Small, Medium, Large'));
check('chosen size', 'Large', ProductSizeColor::chooseOne($sizes, 'Large'));
check('chosen size ignores case', 'Large', ProductSizeColor::chooseOne($sizes, 'large'));
check('first size when none chosen', 'Small', ProductSizeColor::chooseOne($sizes, ''));
check('no sizes', '', ProductSizeColor::chooseOne([], 'Large'));
check('catalog wins over text', ['Red', 'White'], ProductSizeColor::optionList(['Red', 'White'], 'Red, White, Black'));
check('text fallback', ['Red', 'White'], ProductSizeColor::optionList([], 'Red, White'));
check('single color fallback', ['Black'], ProductSizeColor::optionList([], 'Black'));
check('neither', [], ProductSizeColor::optionList([], ''));

$layout = BrowserLabelLayout::normalize([
    'label_w' => '50',
    'label_h' => '25',
    'element_layout' => json_encode([
        'price' => ['x' => 150, 'y' => -4, 'size' => 99, 'weight' => '100', 'align' => 'center'],
        'unknown' => ['x' => 10, 'y' => 10],
        'name' => ['align' => 'left'],
    ]),
    'price_text' => 'Rs 1,300.00',
    'label_size' => 'Large',
], 'Marjan Thobe Center');

$elements = json_decode($layout['element_layout'], true);
check('price stays inside the sticker', 100, $elements['price']['x']);
check('negative position becomes zero', 0, $elements['price']['y']);
check('font size is limited', 36, $elements['price']['size']);
check('bad weight is dropped', false, array_key_exists('weight', $elements['price']));
check('align is kept', 'center', $elements['price']['align']);
check('unknown element is dropped', false, array_key_exists('unknown', $elements));
check('name align kept', 'left', $elements['name']['align']);
check('label price is not a layout field', false, array_key_exists('price_text', $layout));
check('chosen size is not a layout field', false, array_key_exists('label_size', $layout));
check('width kept', '50', $layout['label_w']);

$old = BrowserLabelLayout::normalize(['label_w' => '38', 'label_h' => '25'], 'Shop');
check('old profile has no placed elements', '{}', $old['element_layout']);
check('old profile keeps flow defaults', '1', $old['show_name']);
check('old profile keeps price visible', '1', $old['show_price']);

if ($failed > 0) {
    fwrite(STDERR, "{$failed} failed\n");
    exit(1);
}

echo "ALL PASSED\n";
