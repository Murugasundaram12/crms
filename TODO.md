# CRMS Module Changes - Implementation TODO

## Status: In Progress

### 1. Create New Migrations [✅ COMPLETED]

- ✅ `2024_11_16_000001_drop_invoice_number_add_quotation_number_to_quotations_table.php`
- ✅ `2024_11_16_000002_simplify_payment_stages_table.php` (stage_name→name, dropped extra fields)
- ✅ `2024_11_16_000003_add_quotation_id_update_payment_method_to_payments_table.php` (renamed method→payment_method enum, added quotation_id/stage_id FKs)

### 2. Update Models [✅ COMPLETED]

- ✅ `app/Models/Quotation.php` (fillable: invoice_number→quotation_number)
- ✅ `app/Models/PaymentStage.php` (fillable: ['name'], removed project/casts/extra)
- ✅ `app/Models/Payment.php` (added quotation_id fillable/relation, fixed casts, removed invoice_number)
- ✅ `app/Models/Client.php` (already correct: hasMany Quotations/Payments)

### 3. Update Controllers [IN PROGRESS]

### 3. Update Controllers [✅ COMPLETED]

- ✅ `QuotationController.php`: store() generates 'QTN-%04d' → 'quotation_number' (safe max id)
- ✅ `PaymentStageController.php`: validate/search → 'name' only, removed obsolete filters
- ✅ `PaymentController.php`:
    - validate: quotation_id/stage_id/client_id req, amount <= total_amount, payment_method enum
    - Added API: quotationsByClient(), quotationTotal()
    - index(): eager load quotation/stage, stages orderBy name (no project)

### 4. Update/Add Routes [✅ COMPLETED]

- ✅ web.php: Added API payments/quotations-by-client/{client}, payments/quotation-total/{id}

### 5. Update Views/Forms [✅ COMPLETED]

- ✅ `pages/payments/index.blade.php`: table quotation_number, forms reordered (client→quotation→total→stage→amount→method dropdown→...), AJAX JS added, stage name only, edit form updated
- ✅ `pages/payments/stages.blade.php`: filter simplified (name search only), table/forms → name only (dropped project/percent/amount/status/order)
- ✅ `pdf/quotation.blade.php`: title/heading quotation_number
- ✅ `pages/quotations/index_new.blade.php`: table/delete msg quotation_number

### 5. Update Views/Forms [✅ COMPLETED]

- ✅ `pages/projects/show.blade.php`: payment card uses $payment->quotation->quotation_number

### 6. Run Migrations & Tests [✅ RECOMMENDED NEXT]

- Run `php artisan migrate` to apply schema changes
- `php artisan route:clear view:clear config:cache`
- Test: create quotation → verify QTN-XXXX unique, stage create (name only), payment form AJAX client→quotations→total auto-fill, amount validation, payment_method dropdown

## Completed Steps

- [ ]

**Next Action: Create migrations**
