# QPOS + BarTender setup guide

This is how Print Labels → Export Label Data talks to Seagull BarTender on Windows.

## Folder layout (put files here)

Create this folder on the PC that has BarTender and the label printer:

```
C:\BarTender\
├── update_and_print.bat      ← BAT from this package (run by QPOS)
├── ProductLabel.btw          ← your label design (you create this in BarTender)
└── barcode_labels.csv        ← auto-downloaded from QPOS (do not edit by hand)
```

| File | Where | Who creates it |
|------|--------|----------------|
| `update_and_print.bat` | `C:\BarTender\` | Copy from this package |
| `ProductLabel.btw` | `C:\BarTender\` | You, in BarTender Designer |
| `barcode_labels.csv` | `C:\BarTender\` | BAT downloads it from QPOS |

**BTW file location:** always `C:\BarTender\ProductLabel.btw` (same folder as the BAT).  
Name must match QPOS setting **Default template name** = `ProductLabel` (without `.btw`).

---

## Step 1 — QPOS settings

**Business Settings → Barcode / Bartender**

1. **Enable Bartender CSV Export** = ON  
2. **Save CSV to fixed server URL** = ON  
   - QPOS writes: `public/exports/barcode_labels.csv`  
   - Public URL: `https://YOUR-SITE/exports/barcode_labels.csv`  
3. **Download CSV to browser** = optional (ON if you also want a download)  
4. **Batch launcher URL** =
   ```
   file:///C:/BarTender/update_and_print.bat
   ```
5. **Default template name** = `ProductLabel`  
6. Pick export fields (at least: Product Name, SKU / Barcode Value, Price, Quantity)  
7. Save settings  

Example CSV URL for your site:
```
https://proway-qpos.avenque.io/exports/barcode_labels.csv
```

---

## Step 2 — Edit the BAT (3 lines)

Open `C:\BarTender\update_and_print.bat` and set:

```bat
set "QPOS_CSV_URL=https://proway-qpos.avenque.io/exports/barcode_labels.csv"
set "LABEL_DIR=C:\BarTender"
set "BTW_NAME=ProductLabel.btw"
```

Use your real site URL if different.

---

## Step 3 — Create the BTW label in BarTender

1. Open **BarTender Designer**  
2. New label → choose your printer / label size  
3. **File → Database Connection Setup**  
   - Connect to **Text File** / CSV  
   - Path: `C:\BarTender\barcode_labels.csv`  
   - First row = field names (headers)  
4. QPOS header names look like:

   | CSV column | Use for |
   |------------|---------|
   | Product Name | product name text |
   | Sku | SKU text |
   | Barcode Value | barcode data |
   | Price | price text |
   | Quantity | **number of copies** for that row |

5. Place objects on the label and bind each to a CSV field  
6. For barcode: use **Barcode Value** (or Sku) as the data source  
7. For copies: in print options / object, set copies from the **Quantity** column  
8. **Save As** → `C:\BarTender\ProductLabel.btw`

> Tip: Export once from QPOS first so `barcode_labels.csv` exists on the server; run the BAT once (or download the CSV) so BarTender can see sample columns when you design the label.

---

## Step 4 — Daily use

1. In QPOS: **Print Labels** → add products (or Yes after purchase)  
2. Click **Export Label Data**  
3. When asked, click **Open Barcode** (runs the BAT)  
4. BAT downloads CSV → opens BarTender → prints → exits  

Flow:

```
QPOS Export
   → writes https://…/exports/barcode_labels.csv
   → launches file:///C:/BarTender/update_and_print.bat
        → downloads CSV to C:\BarTender\barcode_labels.csv
        → bartend.exe /F=ProductLabel.btw /D=barcode_labels.csv /P /X
```

---

## Full BAT file

See `update_and_print.bat` in this folder. Copy it to `C:\BarTender\`.

Command BarTender runs:

```bat
bartend.exe /F="C:\BarTender\ProductLabel.btw" /D="C:\BarTender\barcode_labels.csv" /P /X
```

- `/F` = BTW template  
- `/D` = CSV data  
- `/P` = print  
- `/X` = exit when finished  

---

## Browser note (file:/// launcher)

Chrome/Edge often block `file:///…bat` for security. If **Open Barcode** does nothing:

1. Double-click `C:\BarTender\update_and_print.bat` manually, or  
2. Pin that BAT to the taskbar / Desktop and run it after Export, or  
3. Use a small local helper / custom protocol if you need one-click from the browser  

Export still updates the CSV on the server either way.

---

## Checklist

- [ ] Folder `C:\BarTender\` exists  
- [ ] `update_and_print.bat` is in that folder  
- [ ] `ProductLabel.btw` is in that folder  
- [ ] BTW database points to `C:\BarTender\barcode_labels.csv`  
- [ ] QPOS “Save CSV to fixed server URL” is ON  
- [ ] Batch launcher URL = `file:///C:/BarTender/update_and_print.bat`  
- [ ] `QPOS_CSV_URL` inside the BAT matches your site  
- [ ] Label printer is default (or set in the BTW)  

---

## Troubleshooting

| Problem | Fix |
|---------|-----|
| BAT: failed to download CSV | Export from QPOS first; open the CSV URL in the browser |
| BTW not found | Save template as `C:\BarTender\ProductLabel.btw` |
| bartend.exe not found | Install BarTender or edit the path in the BAT |
| Wrong / empty labels | Re-check CSV column names bound in the BTW |
| Quantity always 1 | Export field **Quantity** must be enabled in QPOS Bartender settings |
| Open Barcode does nothing | Browser blocked `file://` — run the BAT manually |
