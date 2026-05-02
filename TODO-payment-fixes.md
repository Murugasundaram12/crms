# Payment Module Fixes - TODO

## Feature 1: Project Name Auto-Fill based on Client

- [x] Create migration for due_date (column already exists in DB)
- [x] Update Payment model ($fillable, $casts)
- [x] Add getProjectByClient API in PaymentController
- [x] Add route for project-by-client API
- [x] Update blade form (readonly project name + hidden project_id)
- [x] Add jQuery AJAX for project auto-fill
- [x] Handle null project safely in jQuery
- [x] Use latest() for project fetch to avoid multiple projects

## Feature 2: Due Date & Payment Date storing fix

- [x] Fix validatePaymentData - add due_date validation
- [x] Fix validatePaymentData - add project_id validation
- [x] Fix store() method - map paid_at to payment_date
- [x] Fix update() method - map paid_at to payment_date
- [x] Change add form input name from payment_date to paid_at
- [x] payment_date cast as datetime in model
- [x] due_date migration placed after payment_date column
