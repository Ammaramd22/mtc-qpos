# cPanel installation guide

Install this update with File Manager and phpMyAdmin. Do not upload `node_modules`. Do not replace the live `.env` file. Do not replace `storage` uploads.

## 1. Backup

1. In cPanel **File Manager**, compress the current QPOS folder and download that archive.
2. In **phpMyAdmin**, export the QPOS database.

## 2. Upload the files

1. Upload `QPOS-Size-Color-Management-cPanel.zip`.
2. Extract it.
3. Copy the extracted files into the live QPOS folder so the paths match.

Examples:

- `app/Http/Controllers/SizeController.php` replaces the same path.
- `resources/views/product/partials/size_color_fields.blade.php` replaces the same path.
- `public/js/product-size-color.js` is a new file.
- `routes/web.php` replaces the same path.

Leave these untouched:

- `.env`
- `storage/app`
- `storage/logs`
- uploaded product images
- `vendor`
- `node_modules`

## 3. Update the database

Follow `DATABASE_UPDATE_GUIDE.md`. Paste `database/cpanel_size_color_management.sql` into phpMyAdmin and click **Go**.

## 4. Clear cached views

In File Manager, delete the files inside `storage/framework/views`. Do not delete the `views` folder itself. Leave `.gitignore` if it is there.

QPOS rebuilds those files on the next page load. There is no Terminal command to run.

## 5. Confirm in the browser

1. Log in as a user who can view and create products.
2. Open **Products → Sizes**. Add `S`, then try to add `S` again. The second save must show “This size name already exists”.
3. Open **Products → Add Product**. Choose more than one size. Type `Black`, press Enter, type `White`, press Enter. Save the product.
4. Open **Products → List Products**. The Size and Color columns show `S` (or the sizes you picked) and `Black, White`.
5. Edit the product, remove one colour, save, and confirm the list changed.
6. Open **Print Labels**, add the product, and tick **Size** and **Color**. The preview and the printed label show that product’s sizes and colours. Try 50mm × 25mm.
7. Open POS. The product shows its size and colour. Add it to the cart. Quantity and price behave as before.

## If Sizes is empty or the page errors

The database script has not been applied, or the new PHP files were not copied. Repeat steps 2 and 3, then clear `storage/framework/views` again.
