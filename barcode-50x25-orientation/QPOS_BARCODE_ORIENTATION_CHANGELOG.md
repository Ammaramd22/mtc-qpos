# Changelog — 50 × 25 mm barcode orientation

Patch only. No Laravel upgrade, no migration, no change to sales, receipts, KOT/BOT, stock, stored barcodes, or saved profiles.

## Why

A 50 × 25 mm label at 0° still printed sideways across two stickers. Switching the print window to Landscape flipped the design the other way and still fed 50 mm, so the gap cut through the barcode. The printer turns a wide page counter-clockwise and feeds the long side.

## What changed

- Wide roll labels are printed with `page-orientation: rotate-right`. The file Chrome sends is turned clockwise on a 25 × 50 mm page, which is the opposite of the latest roll photo, so that same printer turn lands upright with 25 mm along the feed.
- The on-screen preview stays the upright sticker. It is not part of the print.
- A portrait roll size is left alone.
- The note under the preview tells the operator to keep Landscape, margins None, and scale 100.

## Files

- `resources/views/labels/browser_print.blade.php`
- `lang/en/lang_v1.php`
