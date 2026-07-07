# Expenses Module Concept

இந்த project-ல் Other Expenses, Labour Expenses, Vendor Expenses மூன்றும் ஒரே `expenses` table-ல் save ஆகும். Type split `labour_id` / `vendor_id` மூலம் நடக்கும்.

## Expense Types

| Type | Rule | List page |
| --- | --- | --- |
| Other Expenses | `labour_id = NULL`, `vendor_id = NULL` | `/expenses-history?tab=1` |
| Labour Expenses | `labour_id` filled, `vendor_id = NULL` | `/labour-expenses-history` |
| Vendor Expenses | `vendor_id` filled, `labour_id = NULL` | `/vendor-expenses` |

## Common Fields

All expense types use these common fields:

- `main_category_id`
- `category_id`
- `project_id`
- `amount`
- `paid_amt`
- `unpaid_amt`
- `extra_amt`
- `payment_mode`
- `description`
- `current_date`
- `image`
- `user_id`

## Amount Calculation

```text
paid_amt   = user entered paid amount
unpaid_amt = max(amount - paid_amt, 0)
extra_amt  = max(paid_amt - amount, 0)
```

Examples:

```text
Amount = 1000, Paid = 700
unpaid_amt = 300
extra_amt = 0

Amount = 1000, Paid = 1200
unpaid_amt = 0
extra_amt = 200
```

Shared calculation code:

- `App\Support\ExpenseAmounts::calculate()`

## Other Expenses Flow

- Add path: `/expenses-create`
- List path: `/expenses-history?tab=1`
- Store route: `expenses.store`
- Controller: `ExpensesController`
- View: `resources/views/expenses/create.blade.php`

Form fields:

- Main Category
- Category
- Project
- Amount
- Payment Mode
- Image
- Description
- Paid Amount
- Date
- Time

Store flow:

1. Validate request.
2. Calculate `paid_amt`, `unpaid_amt`, `extra_amt`.
3. Upload image to `public/images` if present.
4. Check logged-in user's wallet balance.
5. Deduct `paid_amt` from user wallet.
6. Save record in `expenses` with `labour_id = NULL` and `vendor_id = NULL`.

## Labour Expenses Flow

- Add path: `/labour-expenses/create`
- List path: `/labour-expenses-history`
- Store route: `labour-expenses.store`
- Controller: `LabourExpensesController`
- View: `resources/views/labour-expenses/create.blade.php`

Extra field:

- Labour Name

Store flow:

1. `labour_id` is mandatory.
2. Validate request.
3. Check logged-in user's wallet balance.
4. Deduct `paid_amt` from user wallet.
5. Save record in `expenses` with `labour_id` and `vendor_id = NULL`.
6. If `paid_amt > amount`, add `extra_amt` to `labour_details.advance_amt`.

Example:

```text
Labour amount = 1000
Paid = 1200

expenses.extra_amt = 200
labour_details.advance_amt += 200
```

Special labour pages/flows:

- Weekly History
- Advance Amount
- Deleted History
- Labour-wise and project-wise unpaid/advance summary

## Vendor Expenses Flow

- Add path: `/vendor-expenses/create`
- List path: `/vendor-expenses`
- Store route: `vendor-expenses.store`
- Controller: `VendorExpensesController`
- View: `resources/views/vendor-expenses/create.blade.php`

Extra field:

- Vendor Name

Store flow:

1. `vendor_id` is mandatory.
2. Validate request.
3. Check vendor advance balance.
4. Deduct `paid_amt` from `vendor_details.advance_amt`.
5. Save record in `expenses` with `vendor_id` and `labour_id = NULL`.

Important difference:

```text
Other/Labour paid amount -> deduct from user wallet
Vendor paid amount       -> deduct from vendor advance_amt
```

## List Concept

Common filters:

- Date Range
- Main Category
- Category
- Project
- User / Labour / Vendor
- Search
- Pagination

Common totals:

- Total Amount
- Total Paid
- Total Unpaid
- Total Advance / Extra
- Balance = unpaid - advance

## Edit and Delete Concept

Edit/delete balance logic is handled through:

- `App\Services\ExpenseLedgerService`

Edit flow:

- Adjusts user wallet, vendor advance, and labour advance based on paid amount difference.

Delete flow:

1. Soft delete expense.
2. Save delete reason.
3. Reverse paid amount to user wallet or vendor advance.
4. Reverse labour extra advance when applicable.

## Short Summary

Expenses act like a single ledger.

- Other Expenses = normal expense entry.
- Labour Expenses = same entry plus `labour_id`.
- Vendor Expenses = same entry plus `vendor_id`.
- Paid/unpaid/extra calculation is common.
- Wallet and advance balance updates are the core business rules.
