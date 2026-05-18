# TODO - Expense/Labour/Vendor/Transfer modules

## Expense/Labour/Vendor

- [ ] Create new dedicated migrations + tables for:
    - [ ] general expenses transactions (main_category_id, category_id, project_id, paid_amount, payment_mode, expense_date, expense_time, description, image_path, etc.)
    - [ ] labour expense transactions (labour_id, salary, etc.)
    - [ ] vendor expense transactions (vendor_id, salary, etc.)
- [ ] Create dedicated models for each new table with proper relationships.
- [ ] Create dedicated controllers with full CRUD + optimized eager-loading.
- [ ] Implement validation + file upload handling (store image to public disk, delete on replace/remove).
- [ ] Implement date/time parsing & formatting (dd/mm/yyyy for UI; store as Y-m-d and time string as HH:MM:SS AM/PM compatible format).
- [ ] Create Blade pages (index/create/edit) with responsive offcanvas/modal UI:
    - [ ] dropdowns for DB-driven data
    - [ ] show/hide fields for Transfer module (radio)
    - [ ] show validation errors and success messages
- [ ] Add dedicated routes for each module.
- [ ] Add view components/partials for reusable dropdowns if feasible.

## Transfer module

- [ ] Add missing Blade views for transfers:
    - [ ] index
    - [ ] create
    - [ ] edit
- [ ] Implement validation improvements in TransferDetailsController (ensure employee/vendor required based on transfer_type).
- [ ] Ensure correct dd/mm/yyyy date parsing and time formatting in UI.
- [ ] Make Transfer UI responsive and mobile friendly.

## Final verification

- [ ] Run migrations.
- [ ] Manual CRUD testing for all modules.
- [ ] Verify images are stored and deleted correctly.
- [ ] Verify duplicate/invalid entry prevention (at least server-side sanity checks).
