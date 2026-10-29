# 270° duplicate sticker — changelog

Patch only. No Laravel upgrade, no database change, no change to sales, receipts, KOT/BOT, stock, or stored barcodes.

## Root cause

270° lays the artwork out in a box **25 mm wide and 38 mm tall** (the sticker’s width used as the layout height), then rotates that box with `translateY(25mm) rotate(-90deg)`. The rotation is visual. Print layout uses the box height. A 38 mm box on a 25 mm sticker continues onto the next label, so one requested label crosses the gap and looks like two stickers. The live preview stayed one sticker because the screen clips that box.

90° uses the same tall box (`height` = sticker width). It is printed the same way so it cannot spill either. 0° and 180° already use a box the size of the sticker and are unchanged.

## Correction

For 90° and 270°, the print job replaces each sticker with one bitmap of that sticker, at the sticker’s width and height. The bitmap is the same design as the preview. Nothing in the print layout is taller than the sticker, and the preview element is still excluded from the print. The number of bitmaps equals the number of requested labels.

## File

- `resources/views/labels/browser_print.blade.php`
