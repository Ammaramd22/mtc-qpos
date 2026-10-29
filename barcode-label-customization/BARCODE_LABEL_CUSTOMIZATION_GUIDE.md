# Barcode label customization

Browser print is the label path. BarTender export stays off. Settings are stored on the business record (`common_settings`), so each shop keeps its own layout and up to 12 named profiles.

Open the screen from **Print Labels → Browser print**. The dashed box is one sticker. **Width** is across the roll. **Height** is the sticker face along the feed. The gap on the liner is not part of the height.

## Size and media

| Control | What it does |
| --- | --- |
| Width / Height (mm) | One sticker. Default **38 × 25**. Allowed about 15–120 mm wide and 10–120 mm tall. |
| Stock | **Roll**: one sticker is one printed page. **Sheet**: a grid of columns and rows. |
| Print rotation | **0°** for this roll (page height = sticker length). **90°** or **270°** only when the printer turns the page and the text comes out sideways. Sheet layouts stay at 0°. |
| Columns / Rows | Used for sheets. A roll is always 1 × 1. |
| Column gap / Row gap | Space between stickers on a sheet, in millimetres. Not added to a roll page. |
| Shift left/right and up/down | Calibration, −15 to +15 mm. Moves the design inside the page. |
| Padding (top, right, bottom, left) | Inner space so text does not touch the sticker edge. It is not added to the page size. |

Presets fill the form. Click **Save layout** (or Apply) so the barcode is rebuilt at the new size.

- **38×25 mm roll** — shop name, product, barcode, SKU, price, cost code.
- **25×38 mm roll** — the same content when the sticker is taller than it is wide.
- **Compact** — smaller type, cost code hidden.
- **Price label** — larger price, shop name and cost code hidden, barcode toward the top.
- **Barcode only** — barcode and SKU.
- **Two columns** — a sheet, two stickers across, 2 mm gap.

## What appears on the label

Each item can be shown or hidden:

- Shop name (the text in **Print business name**, not a second copy of the product)
- Brand and category (off until you turn them on; they use the product’s existing brand and category)
- Product name (long names wrap to two lines, then ellipsis)
- Barcode graphic
- SKU (the stored barcode value, printed as text)
- Selling price, with the business currency symbol and separators
- Cost code

Price can be tax inclusive or exclusive. The number is the product’s selling price. The label does not invent a price or change the stored SKU.

Text controls:

- Font: Arial, Helvetica, Tahoma, Verdana, or Courier New
- Alignment: left, center, or right for the heading block
- Size in pixels for shop name, product, SKU, price, and cost code
- Weight for the product name and the price: regular, bold, extra bold, or black

## Barcode

| Control | Safe range |
| --- | --- |
| Format | Auto (product type), C128, C39, EAN13, EAN8, UPCA, UPCE |
| Position | Top, middle, or bottom of the sticker |
| Width | 40–100% of the content area |
| Height | 16–80 px, scaled to stay inside the sticker (`object-fit: contain`) |
| Bar thickness | 1–4 |
| Quiet zone | 0–3 mm of side padding so bars do not touch the text |

Invalid EAN or UPC lengths are printed as Code 128 so the label still has a scannable symbol. The stored SKU is not rewritten.

## Profiles

1. Set the layout you want.
2. Type a profile name (up to 40 characters).
3. Click **Save as profile**.

Choose a saved profile in the profile list to load it into the form, then save or apply so the preview rebuilds. **Delete profile** removes that name for this business only. **Restore defaults** returns 38 × 25 mm, rotation 0°, and the standard fields, then saves.

## Calibration

Print one sticker before a batch.

1. Rotation **0°**, width and height equal to the physical sticker, margins **None**, scale **100%**.
2. If the design is on the sticker but slightly high, low, left, or right, change the shift fields by half a millimetre and print one sticker again.
3. If a gap cuts through the words, the page is longer than the sticker. Set rotation to 0° and reduce Height to the sticker face only.
4. If the words are sideways, try 90°, then 270° if needed.

The on-screen preview updates sizes, fonts, alignment, offsets, and show/hide immediately. Barcode thickness, format, and a new profile still need **Apply** or **Save layout** so the image is generated again.

Business Settings → the barcode/label section edits the same saved layout. Named profiles are managed on the print screen.
