# Tyre shop setup (QPOS Offline)

Use this after the app is running on the customer PC.

## Business settings

- Business name: customer shop name  
- Currency: LKR (or their currency)  
- Timezone: Asia/Colombo (if Sri Lanka)  
- Enable modules: **POS, Purchases, Contacts, Products, Expenses, Reports**  
- Disable unused: restaurant tables, booking, etc. (optional)

## Categories

| Category | Use |
|----------|-----|
| Tyres | Stock items |
| Tubes / Flaps | Optional stock |
| Services | No stock / not for selling as inventory |

## Products — tyres

- Create as **Single** or **Variable** product.
- Good SKU examples:
  - `MICH-185/65R15`
  - `BRI-195/65R15`
- Fields that help search: brand in name, size in name/SKU.
- Enable **stock management**, alert qty (e.g. 4).

## Products — services (no stock)

Add these as products with **stock management off** (or manage as service):

- Tyre Fitting  
- Wheel Balancing  
- Puncture Repair  
- Valve / Alignment (if offered)

## Selling flow (counter)

1. Open **POS**  
2. Scan/search tyre → add qty  
3. Add **Fitting** (and balancing if needed)  
4. Choose customer (or Walk-in)  
5. Pay cash/card or credit  
6. Print receipt  

## Optional

- Customer credit for garage accounts  
- Price groups: Retail / Wholesale  
- Backup daily with `backup-qpos.bat`

## Training checklist

- [ ] Add one tyre and one fitting  
- [ ] Make a sale and print  
- [ ] Purchase stock in  
- [ ] Check low stock  
- [ ] Run backup once  
