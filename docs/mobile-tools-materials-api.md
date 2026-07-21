# Mobile Tools & Materials API

Base URL:

```text
https://housefix360.com/crms/api
```

Headers for protected APIs:

```http
Accept: application/json
Content-Type: application/json
Authorization: Bearer {login_token}
```

Login:

```http
POST /login
POST /auth/login
```

Payload:

```json
{
  "email": "user@example.com",
  "password": "password"
}
```

## Endpoint List

### Auth

```http
POST https://housefix360.com/crms/api/login
POST https://housefix360.com/crms/api/auth/login
```

### Dropdown / Options

```http
GET https://housefix360.com/crms/api/tools-materials/options
GET https://housefix360.com/crms/api/inventory/options
```

### Tools / Materials Item Master

```http
GET    https://housefix360.com/crms/api/tools-materials
POST   https://housefix360.com/crms/api/tools-materials
GET    https://housefix360.com/crms/api/tools-materials/{id}
PUT    https://housefix360.com/crms/api/tools-materials/{id}
POST   https://housefix360.com/crms/api/tools-materials/{id}/update
DELETE https://housefix360.com/crms/api/tools-materials/{id}
```

### Tools / Materials Item Master Aliases

```http
GET    https://housefix360.com/crms/api/inventory/items
POST   https://housefix360.com/crms/api/inventory/items
GET    https://housefix360.com/crms/api/inventory/items/{id}
PUT    https://housefix360.com/crms/api/inventory/items/{id}
POST   https://housefix360.com/crms/api/inventory/items/{id}/update
DELETE https://housefix360.com/crms/api/inventory/items/{id}
```

### Stock Transaction / Assignment

```http
GET    https://housefix360.com/crms/api/tools-material-assignments
POST   https://housefix360.com/crms/api/tools-material-assignments
GET    https://housefix360.com/crms/api/tools-material-assignments/{id}
PUT    https://housefix360.com/crms/api/tools-material-assignments/{id}
POST   https://housefix360.com/crms/api/tools-material-assignments/{id}/update
DELETE https://housefix360.com/crms/api/tools-material-assignments/{id}
```

### Stock Transaction / Assignment Aliases

```http
GET    https://housefix360.com/crms/api/inventory/transactions
POST   https://housefix360.com/crms/api/inventory/transactions
GET    https://housefix360.com/crms/api/inventory/transactions/{id}
PUT    https://housefix360.com/crms/api/inventory/transactions/{id}
POST   https://housefix360.com/crms/api/inventory/transactions/{id}/update
DELETE https://housefix360.com/crms/api/inventory/transactions/{id}
```

## API Wise Request

### 1. Login

```http
POST https://housefix360.com/crms/api/login
```

Request:

```json
{
  "email": "user@example.com",
  "password": "password"
}
```

### 2. Tools / Materials Options

```http
GET https://housefix360.com/crms/api/tools-materials/options
```

Request body: not required.

Use this response for dropdowns:

```text
item_type
unit
transaction_type
status
projects
vendors
tools_materials
```

### 3. Tools / Materials List

```http
GET https://housefix360.com/crms/api/tools-materials
```

Request query:

```json
{
  "q": "cement",
  "item_type": "material",
  "low_stock": false,
  "date_from": "2026-07-01",
  "date_to": "2026-07-21",
  "per_page": 15
}
```

All fields optional.

### 4. Create Material

```http
POST https://housefix360.com/crms/api/tools-materials
```

Request:

```json
{
  "item_type": "material",
  "sku": "CEM-001",
  "name": "Cement",
  "unit": "Bag",
  "description": "OPC Cement",
  "date": "2026-07-21",
  "opening_quantity": 100,
  "opening_rate": 420,
  "reorder_level": 20,
  "active_status": true
}
```

Required for material:

```text
item_type
name
unit
date
opening_quantity
opening_rate
```

### 5. Create Tool

```http
POST https://housefix360.com/crms/api/tools-materials
```

Request:

```json
{
  "item_type": "tool",
  "sku": "DRILL-001",
  "name": "Drilling Machine",
  "description": "Bosch drilling machine",
  "date": "2026-07-21",
  "opening_quantity": 1,
  "opening_rate": 3500,
  "reorder_level": 0,
  "active_status": true
}
```

Note: `tool` item-ku `unit` backend-la automatic-a `Nos` save aagum.

### 6. Show Single Tool / Material

```http
GET https://housefix360.com/crms/api/tools-materials/{id}
```

Request body: not required.

Example:

```http
GET https://housefix360.com/crms/api/tools-materials/1
```

### 7. Update Tool / Material

```http
PUT https://housefix360.com/crms/api/tools-materials/{id}
```

Mobile PUT support illana:

```http
POST https://housefix360.com/crms/api/tools-materials/{id}/update
```

Request:

```json
{
  "item_type": "material",
  "sku": "CEM-001",
  "name": "Cement OPC",
  "unit": "Bag",
  "description": "Updated cement",
  "date": "2026-07-21",
  "opening_quantity": 120,
  "opening_rate": 430,
  "reorder_level": 25,
  "active_status": true
}
```

### 8. Delete Tool / Material

```http
DELETE https://housefix360.com/crms/api/tools-materials/{id}
```

Request body: not required.

Note: item-ku stock transaction irundha delete panna mudiyathu. API `409` return pannum.

### 9. Stock Transaction List

```http
GET https://housefix360.com/crms/api/tools-material-assignments
```

Request query:

```json
{
  "q": "TM-260721",
  "tool_material_id": 1,
  "project_id": 2,
  "vendor_id": 3,
  "transaction_type": "issue_to_site",
  "status": "transferred",
  "date_from": "2026-07-01",
  "date_to": "2026-07-21",
  "per_page": 15
}
```

All fields optional.

### 10. Purchase Stock

```http
POST https://housefix360.com/crms/api/tools-material-assignments
```

Request:

```json
{
  "tool_material_id": 1,
  "transaction_type": "purchase",
  "status": "completed",
  "vendor_id": 3,
  "destination_type": "office",
  "quantity": 50,
  "rate": 100,
  "receiver_name": "Ravi",
  "vehicle_no": "TN01AB1234",
  "purpose": "Office stock purchase",
  "notes": "Initial purchase",
  "transferred_at": "2026-07-21 10:30:00"
}
```

Direct site purchase request:

```json
{
  "tool_material_id": 1,
  "transaction_type": "purchase",
  "status": "completed",
  "vendor_id": 3,
  "destination_type": "site",
  "to_project_id": 2,
  "quantity": 50,
  "rate": 100,
  "transferred_at": "2026-07-21 10:30:00"
}
```

### 11. Issue Stock To Site

```http
POST https://housefix360.com/crms/api/tools-material-assignments
```

Request:

```json
{
  "tool_material_id": 1,
  "transaction_type": "issue_to_site",
  "status": "transferred",
  "to_project_id": 2,
  "quantity": 10,
  "rate": 100,
  "receiver_name": "Site Engineer",
  "vehicle_no": "TN01AB1234",
  "purpose": "Site usage",
  "transferred_at": "2026-07-21 11:00:00"
}
```

### 12. Return Stock To Office

```http
POST https://housefix360.com/crms/api/tools-material-assignments
```

Request:

```json
{
  "tool_material_id": 1,
  "transaction_type": "return_to_office",
  "status": "returned",
  "from_project_id": 2,
  "quantity": 5,
  "rate": 100,
  "receiver_name": "Store Keeper",
  "purpose": "Unused stock return",
  "transferred_at": "2026-07-21 12:00:00"
}
```

### 13. Site To Site Transfer

```http
POST https://housefix360.com/crms/api/tools-material-assignments
```

Request:

```json
{
  "tool_material_id": 1,
  "transaction_type": "site_to_site",
  "status": "transferred",
  "from_project_id": 2,
  "to_project_id": 4,
  "quantity": 3,
  "rate": 100,
  "receiver_name": "Site Engineer",
  "vehicle_no": "TN01AB1234",
  "purpose": "Transfer to another site",
  "transferred_at": "2026-07-21 13:00:00"
}
```

### 14. Return Stock To Vendor

```http
POST https://housefix360.com/crms/api/tools-material-assignments
```

Request from office:

```json
{
  "tool_material_id": 1,
  "transaction_type": "return_to_vendor",
  "status": "returned",
  "vendor_id": 3,
  "source_type": "office",
  "quantity": 2,
  "rate": 100,
  "purpose": "Damaged return",
  "transferred_at": "2026-07-21 14:00:00"
}
```

Request from site:

```json
{
  "tool_material_id": 1,
  "transaction_type": "return_to_vendor",
  "status": "returned",
  "vendor_id": 3,
  "source_type": "site",
  "from_project_id": 2,
  "quantity": 2,
  "rate": 100,
  "purpose": "Site stock vendor return",
  "transferred_at": "2026-07-21 14:00:00"
}
```

### 15. Damage / Wastage

```http
POST https://housefix360.com/crms/api/tools-material-assignments
```

Request from office:

```json
{
  "tool_material_id": 1,
  "transaction_type": "damage_wastage",
  "status": "completed",
  "source_type": "office",
  "quantity": 1,
  "rate": 100,
  "purpose": "Damaged item",
  "transferred_at": "2026-07-21 15:00:00"
}
```

Request from site:

```json
{
  "tool_material_id": 1,
  "transaction_type": "damage_wastage",
  "status": "completed",
  "source_type": "site",
  "from_project_id": 2,
  "quantity": 1,
  "rate": 100,
  "purpose": "Site wastage",
  "transferred_at": "2026-07-21 15:00:00"
}
```

### 16. Show Single Stock Transaction

```http
GET https://housefix360.com/crms/api/tools-material-assignments/{id}
```

Request body: not required.

Example:

```http
GET https://housefix360.com/crms/api/tools-material-assignments/1
```

### 17. Update Stock Transaction

```http
PUT https://housefix360.com/crms/api/tools-material-assignments/{id}
```

Mobile PUT support illana:

```http
POST https://housefix360.com/crms/api/tools-material-assignments/{id}/update
```

Request:

```json
{
  "tool_material_id": 1,
  "transaction_type": "issue_to_site",
  "status": "transferred",
  "to_project_id": 2,
  "quantity": 12,
  "rate": 100,
  "receiver_name": "Site Engineer",
  "vehicle_no": "TN01AB1234",
  "purpose": "Updated site issue",
  "transferred_at": "2026-07-21 11:30:00"
}
```

### 18. Delete Stock Transaction

```http
DELETE https://housefix360.com/crms/api/tools-material-assignments/{id}
```

Request body: not required.

## Options

Use this API to load dropdown values for item type, unit, transaction type, status, projects, vendors, and active tools/materials.

```http
GET /tools-materials/options
```

Alias:

```http
GET /inventory/options
```

## Tools / Materials Items

### List

```http
GET /tools-materials
```

Alias:

```http
GET /inventory/items
```

Query filters:

```json
{
  "q": "cement",
  "item_type": "material",
  "low_stock": true,
  "date_from": "2026-07-01",
  "date_to": "2026-07-21",
  "per_page": 15
}
```

### Create

```http
POST /tools-materials
```

Alias:

```http
POST /inventory/items
```

Payload:

```json
{
  "item_type": "material",
  "sku": "CEM-001",
  "name": "Cement",
  "unit": "Bag",
  "description": "OPC Cement",
  "date": "2026-07-21",
  "opening_quantity": 100,
  "opening_rate": 420,
  "reorder_level": 20,
  "active_status": true
}
```

Notes:

- `item_type`: `tool` or `material`
- For `material`, `unit`, `opening_quantity`, and `opening_rate` are required.
- For `tool`, unit is automatically saved as `Nos`.
- `image` upload is supported as multipart form-data, max 2 MB.

### Show

```http
GET /tools-materials/{id}
```

Alias:

```http
GET /inventory/items/{id}
```

### Update

```http
PUT /tools-materials/{id}
POST /tools-materials/{id}/update
```

Aliases:

```http
PUT /inventory/items/{id}
POST /inventory/items/{id}/update
```

### Delete

```http
DELETE /tools-materials/{id}
```

Alias:

```http
DELETE /inventory/items/{id}
```

If an item already has stock transactions, delete is blocked with `409`.

## Stock Transactions

### List

```http
GET /tools-material-assignments
```

Alias:

```http
GET /inventory/transactions
```

Query filters:

```json
{
  "q": "TM-260721",
  "tool_material_id": 1,
  "project_id": 2,
  "vendor_id": 3,
  "transaction_type": "issue_to_site",
  "status": "transferred",
  "date_from": "2026-07-01",
  "date_to": "2026-07-21",
  "per_page": 15
}
```

### Create

```http
POST /tools-material-assignments
```

Alias:

```http
POST /inventory/transactions
```

Common payload:

```json
{
  "tool_material_id": 1,
  "transaction_type": "purchase",
  "status": "completed",
  "vendor_id": 3,
  "destination_type": "office",
  "quantity": 50,
  "rate": 100,
  "receiver_name": "Ravi",
  "vehicle_no": "TN01AB1234",
  "purpose": "Site stock",
  "notes": "Initial purchase",
  "transferred_at": "2026-07-21 10:30:00"
}
```

Allowed `transaction_type` values:

```text
purchase
issue_to_site
return_to_office
site_to_site
return_to_vendor
damage_wastage
```

Allowed `status` values:

```text
draft
transferred
returned
completed
cancelled
```

Only stock-effective statuses affect stock balance: `transferred`, `returned`, `completed`.

Transaction required fields by type:

| Type | Required fields |
| --- | --- |
| `purchase` | `vendor_id`, `quantity`, `rate`; if direct site purchase then `destination_type=site` and `to_project_id` |
| `issue_to_site` | `tool_material_id`, `to_project_id`, `quantity`, `rate` |
| `return_to_office` | `tool_material_id`, `from_project_id`, `quantity`, `rate` |
| `site_to_site` | `tool_material_id`, `from_project_id`, `to_project_id`, `quantity`, `rate` |
| `return_to_vendor` | `tool_material_id`, `vendor_id`, `quantity`, `rate`; if from site then `source_type=site` and `from_project_id` |
| `damage_wastage` | `tool_material_id`, `quantity`, `rate`; if from site then `source_type=site` and `from_project_id` |

### Show

```http
GET /tools-material-assignments/{id}
```

Alias:

```http
GET /inventory/transactions/{id}
```

### Update

```http
PUT /tools-material-assignments/{id}
POST /tools-material-assignments/{id}/update
```

Aliases:

```http
PUT /inventory/transactions/{id}
POST /inventory/transactions/{id}/update
```

### Delete

```http
DELETE /tools-material-assignments/{id}
```

Alias:

```http
DELETE /inventory/transactions/{id}
```

## Important Response / Error Notes

- Validation error response: HTTP `422` with `errors` object.
- Unauthorized/no token: HTTP `401`.
- Permission missing: HTTP `403`.
- Item delete blocked because stock transactions exist: HTTP `409`.
- For mobile app, prefer the simple routes: `/tools-materials` and `/tools-material-assignments`.
- `/inventory/items` and `/inventory/transactions` are aliases for the same APIs.
