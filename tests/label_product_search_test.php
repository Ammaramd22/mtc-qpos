<?php

/**
 * Print Labels search rules. No database connection.
 * Run: php tests/label_product_search_test.php
 */

require __DIR__.'/../vendor/autoload.php';

use App\Http\Controllers\LabelsController;
use App\Utils\LabelProductSearch;
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

check('two character minimum still splits', ['AL'], LabelProductSearch::tokens('  AL  '));
check('full name tokens', ['AL', 'HADEEL', 'LONG'], LabelProductSearch::tokens('AL HADEEL LONG'));
check('size token stays numeric', true, LabelProductSearch::isNumericToken('54'));
check('name token is not numeric', false, LabelProductSearch::isNumericToken('HADEEL'));
check('number does not match a name', false, LabelProductSearch::tokenMayMatchName('54'));
check('word may match a name', true, LabelProductSearch::tokenMayMatchName('HADEEL'));

check('size 54 is assigned', true, LabelProductSearch::listHasExactToken('54, 56, 58, 60', '54'));
check('size 56 is assigned', true, LabelProductSearch::listHasExactToken('54, 56, 58, 60', '56'));
check('154 is not size 54', false, LabelProductSearch::listHasExactToken('154, 540', '54'));
check('540 is not size 54', false, LabelProductSearch::listHasExactToken('54, 56', '540'));
check('size inside a sentence is not a size list', false, LabelProductSearch::listHasExactToken('Model 54 special', '54'));

check('color prefix White', true, LabelProductSearch::listHasPrefixToken('White, Black', 'Whi'));
check('color exact Black', true, LabelProductSearch::listHasPrefixToken('White, Black', 'Black'));
check('color does not match the middle', false, LabelProductSearch::listHasPrefixToken('Offwhite, Black', 'white'));

$catalog = LabelProductSearch::resolveAssigned(['54', '56', '58'], '1, 2, 3', ['48']);
check('catalog sizes win', 'catalog', $catalog['source']);
check('catalog values only', ['54', '56', '58'], $catalog['values']);

$text = LabelProductSearch::resolveAssigned([], 'White, Black', ['Blue']);
check('text colors when no catalog', 'text', $text['source']);
check('text color values', ['White', 'Black'], $text['values']);

$variation = LabelProductSearch::resolveAssigned([], '', ['48', '50', '52']);
check('variation sizes last', 'variation', $variation['source']);
check('variation values', ['48', '50', '52'], $variation['values']);

check('no attributes', 'none', LabelProductSearch::resolveAssigned([], '', [])['source']);

check('matched size from a mixed search', '54', LabelProductSearch::matchedValue(['48', '54', '60'], ['AL', 'HADEEL', '54'], false));
check('number is not a prefix color', null, LabelProductSearch::matchedValue(['540', '56'], ['54'], true));
check('unique color prefix', 'White', LabelProductSearch::matchedValue(['White', 'Black'], ['Whi'], true));
check('ambiguous color prefix is not chosen', null, LabelProductSearch::matchedValue(['Black', 'Blue'], ['Bl'], true));

check('printed choice is the selected size', '50', ProductSizeColor::chooseOne(['48', '50', '52', '54'], '50'));
check('printed choice is the selected color', 'White', ProductSizeColor::chooseOne(['White', 'Black'], 'White'));
check('size template', 'size', LabelsController::labelAttributeKind('Product Size'));
check('color template', 'color', LabelsController::labelAttributeKind('Colour'));
check('dummy template is not a size', '', LabelsController::labelAttributeKind('DUMMY'));

check('suggestion window is a dropdown cap', 40, LabelProductSearch::SUGGESTION_CAP);

if ($failed > 0) {
    fwrite(STDERR, "{$failed} failed\n");
    exit(1);
}

echo "All label search rules passed\n";
