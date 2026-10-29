# QPOS 270° duplicate-sticker fix — cPanel File Manager

This is a **minimal patch**. It replaces one view. It is not a full application. There is **no database change** and **no Terminal or SSH step**.

## 1. Find the live site

In cPanel, open **File Manager**. The QPOS folder contains `artisan` and `resources`. On this account that is usually:

`/home/mtcqpos/public_html`

## 2. Back up the file you will replace

Download and keep a copy of:

`resources/views/labels/browser_print.blade.php`

## 3. Upload the ZIP away from the live site

1. Go to the home directory, not inside `public_html`.
2. Upload `QPOS-270-Rotation-Duplicate-Label-Fix-cPanel.zip`.
3. Select it and click **Extract**.
4. Confirm the extracted folder contains `resources/views/labels/browser_print.blade.php`.

Do not extract the ZIP directly over `public_html`.

## 4. Replace one file

Copy the extracted file onto:

`public_html/resources/views/labels/browser_print.blade.php`

Do not replace `.env`, `vendor`, `storage`, or any other file.

## 5. Refresh

Hard-refresh the label page (`Ctrl+F5`). If the old script is still running, delete the compiled `.php` files in `storage/framework/views` and leave `.gitignore`. Do not upload a public script that runs Artisan.

## 6. Test 270°

1. Open **Print Labels → Browser print**.
2. Set **Content rotation** to **270° — other way**. The live preview is still one sticker, with the design turned the other way.
3. Use one product and one copy.
4. Click **Print**. Chrome or Edge: margins **None**, scale **100%**, headers and footers **off**. The dialog should show **one** page for one copy.
5. Print one sticker. It should be a single sticker, not two with the gap cutting through the barcode.
6. Print two copies and confirm two stickers, not four.
7. Set rotation back to **0° — upright** and print one sticker to confirm the shop name is at the top.

## 7. Roll back

Put the backed-up `browser_print.blade.php` back in the same path and hard-refresh.
