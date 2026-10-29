# 50 × 25 mm barcode label fix — cPanel File Manager

This is a patch. It replaces two files. It does not change the database, products, sales, receipts, or stock. No Terminal and no cache script.

The live site is the folder that contains `artisan`, usually `public_html`.

## 1. Back up the current files

In File Manager, copy these two files somewhere safe, for example a folder named `label-backup`:

- `public_html/resources/views/labels/browser_print.blade.php`
- `public_html/lang/en/lang_v1.php`

## 2. Upload and extract

1. Upload `QPOS-50x25mm-Barcode-Orientation-Fix-cPanel.zip` to the home directory, not into `public_html`.
2. Extract it. You should see:

- `resources/views/labels/browser_print.blade.php`
- `lang/en/lang_v1.php`
- `CPANEL_50x25mm_BARCODE_FIX_INSTALLATION.md`

## 3. Replace only these files

Copy:

| From the extracted zip | Onto the live site |
| --- | --- |
| `resources/views/labels/browser_print.blade.php` | `public_html/resources/views/labels/browser_print.blade.php` |
| `lang/en/lang_v1.php` | `public_html/lang/en/lang_v1.php` |

Do not replace any other file.

## 4. Refresh the label page

Open `/labels/browser-print` and press `Ctrl+F5`.

The live preview must show the whole sticker: shop name, item, barcode, number, and price inside the white rectangle. For 50 mm × 25 mm the rectangle is wide and short, about twice as wide as it is tall.

A yellow line under the preview tells you the print-window settings.

## 5. Print one 50 × 25 mm sticker

Set Width **50**, Height **25**, Content rotation **0° — upright**. The on-screen preview stays upright, with the shop name across the top.

This printer turns a wide page on its side and feeds the long side, which is why one design covered two stickers. The file is now turned the other way before it reaches the printer. In Chrome’s print window the page can look tall and sideways. Print it that way.

1. Orientation: **Landscape** (the same setting as the last print).
2. Margins: **None**.
3. Scale: **100**.
4. Headers and footers: off.

One copy should be one sticker, with “Marjan Thobe Center” across the top and the price on that same sticker.

## 6. Print several labels

Print two copies. You should get two stickers, each with the same upright layout. The gap between stickers must fall between labels, not through the barcode.

## 7. Other rotations

Leave the paper size and Landscape orientation as above. Change only Content rotation:

- **0°** — shop name at the top.
- **90°** — the design turns clockwise inside the same sticker.
- **180°** — the design is upside down inside the same sticker.
- **270°** — the design turns the other way inside the same sticker.

Each choice is still one page per sticker. Save layout after you return to **0°**. Leave the printer driver’s rotation where it was for the last print.

## 8. Roll back

Put the two backed-up files back in the same paths and hard-refresh the page.
