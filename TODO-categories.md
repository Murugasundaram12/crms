
கீழே இந்த project-ல இருக்கும் Expenses / Labour Expenses / Vendor Expenses / History / Reports modules-க்கு reusable understanding content கொடுத்திருக்கேன். இதை வேற project-க்கு logic migrate பண்ணும்போது reference-ஆ use பண்ணலாம்.
Core Tables / Models
expenses table தான் main expense storage.
Main fields:
id, amount, category_id, main_category_id, project_id, user_id, current_date, paid_amt, unpaid_amt, extra_amt, payment_mode, description, image, editedBy, reason, labour_id, vendor_id, is_advance, deleted_at.
Related tables:
expenses_unpaid_date - unpaid amount paid history
advance_history - labour/vendor advance adjustment history
wallet - client/project income and wallet credit/debit
transferdetails - member/vendor transfer history
labour_details - labour master with salary/advance amount
vendor_details - vendor master with advance amount
project_details - project total/advance/profit
category, main_category, payment, users, clientdetails, stage
Expenses Module
Pages:
Expense History, Unpaid History, Deleted History, Reports, Add Expenses
Add Expenses fields:
main_category_id, category_id, project_id, amount, payment_mode, image, description, paid_amt, unpaid_amt, current_date, time.
Important functions:
ExpensesController@index - expense history list
create - add expense form data load
store - new expense save
edit, update - edit expense
unpaid_create, unpaid_store - unpaid amount pay/update
expensedelete - soft delete with reason
delete_record - deleted history
reports_history - reports page
expense_export, expense_pdf - export/pdf
new_category, category - dynamic category add/load
insufficientamt - wallet balance check
Flow:
Expense add பண்ணும்போது amount மற்றும் paid_amt compare பண்ணும்.
paid_amt > amount என்றால் difference extra_amt ஆக save ஆகும்.
amount > paid_amt என்றால் difference unpaid_amt ஆக save ஆகும்.
Expense save ஆனதும் logged-in user wallet-ல இருந்து paid_amt minus ஆகும்.
Unpaid payment later pay பண்ணும்போது expenses_unpaid_date-ல் history create ஆகும்; expense paid_amt increase, unpaid_amt decrease ஆகும்.
Delete பண்ணும்போது reason save பண்ணி soft delete செய்கிறது; user wallet-க்கு paid amount return ஆகும்.
Labour Expenses Module
Pages:
Expense History, Weekly History, Advance Amount, Deleted History, Add Labour Expenses
Add Labour Expense fields:
main_category_id, category_id, project_id, labour_id, amount, payment_mode, image, description, paid_amt, unpaid_amt, current_date, time.
Important functions:
LabourExpensesController@index - weekly summary JSON/view
create, store - add labour expense
labour_salary - labour salary/advance ajax fetch
labour_expenses_history - labour expense history
labour_expense_project - weekly/project wise labour summary
labour_expenses_details - day wise details
labour_expenses_store - selected unpaid project amounts pay
labour_advance - labour advance list
advance_form, labour_advance_store - advance amount adjustment
labour_project_amount - project-wise advance/unpaid amount ajax
edit, update, labourdelete
delete_record, export/pdf functions
Flow:
Labour expense save ஆனதும் normal expense போல expenses table-ல save ஆகும், ஆனால் labour_id filled இருக்கும்.
paid_amt user wallet-ல இருந்து minus ஆகும்.
paid_amt > amount difference extra_amt; இந்த extra amount labour master labour_details.advance_amt-ல add ஆகும்.
Weekly history current_date அடிப்படையில் week range create பண்ணி labour-wise unpaid_amt மற்றும் extra_amt summary காட்டும்.
Advance adjustment செய்யும்போது labour advance amount-ல் இருந்து amount reduce ஆகும்; selected project expense unpaid amount adjust ஆகும்; advance_history-ல் record save ஆகும்.
Delete பண்ணும்போது labour advance rollback, user wallet rollback, expense soft delete.
Vendor Expenses Module
Pages:
Expense History, Unpaid History, Advance Amount, Deleted History, Add Vendor Expenses
Add Vendor Expense fields:
main_category_id, category_id, project_id, vendor_id, amount, payment_mode, image, description, paid_amt, unpaid_amt, current_date, time.
Important functions:
VendorExpensesController@index - vendor expense history
create, store - add vendor expense
vendor_salary - vendor details/ajax fetch
unpaid_expenses, unpaid_edit, unpaid_store - vendor unpaid flow
advance_expenses, advance_form, advance_store - vendor advance adjustment
vendor_project_amount - project-wise advance/unpaid fetch
vendordelete, delete_record
vendor_history - individual vendor transfer/payment history
withdraw, withdraw_save - vendor advance withdraw to member wallet
vendor_insufficant - vendor advance balance check
export/pdf functions
Flow:
Vendor expense also expenses table-ல save ஆகும், but vendor_id filled இருக்கும்.
Vendor expense pay பண்ணும்போது vendor advance_amt-ல இருந்து paid_amt reduce ஆகும்.
Vendor unpaid amount later pay பண்ணும்போது vendor advance balance check பண்ணி unpaid reduce, paid increase செய்கிறது.
Vendor advance adjustment project-wise unpaid amount-க்கு adjust ஆகும்.
Vendor withdraw flow-ல் vendor advance amount reduce ஆகி selected member/user wallet increase ஆகும்.
Delete பண்ணும்போது vendor advance amount-க்கு paid amount return ஆகும்; expense soft delete.
History Module
Transfer History:
TransferController@index, create, store, insufficientamt, userDetail
Fields:
user_type, member_id, amount, payment_mode, current_date, time, description.
Flow:
user_type = 0 என்றால் member-to-member transfer. Logged-in user wallet minus, selected member wallet plus.
user_type = 1 என்றால் vendor transfer. Logged-in user wallet minus, vendor advance_amt plus.
transferdetails table-ல் history save ஆகும்.
Wallet History:
WalletController@index, create, store
Fields:
client_id, project_id, amount, payment_mode, transfer_type, description, current_date, time, stage_id.
Flow:
transfer_type = 0 credited. Project advance_amt increase, project profit decrease, user wallet increase.
transfer_type = 1 debited. Project advance_amt decrease, project profit increase, user wallet decrease.
Wallet records client/project/payment/stage உடன் list ஆகும்.
Vendor History:
TransferController@vendor_history and VendorExpensesController@vendor_history
Flow:
Vendorக்கு transfer ஆன amounts transferdetails table-ல் is_vendor = 1 filter வைத்து காட்டும். Date/search/vendor/member filters இருக்கிறது.
Reports Module
Client Summary:
ReportsController@client_summary
Data:
Project + wallet + stage + client + payment join.
Shows client/project income summary.
Filters:
date_range, category_id as client, project_id, search.
Payment Summary:
ReportsController@payment_summary
Data:
Project-wise expense total and project advance amount.
project_details + expenses join, project group summary.
Payment Income:
ReportsController@payment_income
Data:
Selected project wallet/income records.
Filters client, date, search.
Payment Expenses:
ReportsController@payment_expenses
Data:
Selected project expense records.
Filters category, user, date, search.
Export/PDF:
Client summary, payment income, payment expense எல்லாத்துக்கும் Excel/PDF functions இருக்கிறது.
Common Filters
Most history pages support:
date_range, main_category_id, category_id, project_id, user_id, search, paginate.
Search commonly checks:
category name, main category name, project name, payment mode, user/member name, amount, paid amount, unpaid amount, advance/extra amount, description, delete reason.
Migration Notes
இந்த logic வேற project-க்கு எடுத்துச் செல்லும்போது minimum required modules:
expenses, expenses_unpaid_date, advance_history, wallet, transferdetails, users.wallet, project_details.advance_amt/profit, labour_details.advance_amt, vendor_details.advance_amt.
Main business rule:
amount, paid_amt, unpaid_amt, extra_amt, wallet, advance_amt இந்த 6 values தான் whole flow-ஐ drive பண்ணுது.
Normal expense user wallet-ஐ affect பண்ணும். Labour expense user wallet + labour advance affect பண்ணும். Vendor expense vendor advance affect பண்ணும். Wallet income project/user wallet affect பண்ணும். Transfer member/vendor balances affect பண்ணும்.
