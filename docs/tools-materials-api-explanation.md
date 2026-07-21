# Tools & Materials API Explanation

Tools & Materials API `routes/api.php` la `mobile.api` middleware kulla iruku. So ella endpoint-kum mobile login token venum.

Base URL example: `/api`

## Main Concept

Tools & Materials la 2 layer iruku:

1. **Item Master**
   - Tool/material create panrathu.
   - Example: cement, steel, drill machine.
   - Table/model: `ToolMaterial`
   - API prefix: `/tools-materials` or duplicate alias `/inventory/items`

2. **Stock Transactions / Assignments**
   - Purchase, site-ku issue, site-lendhu office return, site-to-site transfer, vendor return, wastage.
   - Table/model: `ToolMaterialAssignment`
   - API prefix: `/tools-material-assignments` or duplicate alias `/inventory/transactions`

## Permissions

API permission checks:

- List/options/show: `tools-materials-list`
- Create: `tools-materials-create`
- Update: `tools-materials-edit`
- Delete: `tools-materials-delete`

## Item Master Endpoints

```http
GET /api/tools-materials/options
```

Options data return pannum:

- transaction types
- statuses
- source/destination types
- active tools/materials
- projects
- vendors

Same alias:

```http
GET /api/inventory/options
```

```http
GET /api/tools-materials
```

Tools/materials list with pagination and summary.

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

Same alias:

```http
GET /api/inventory/items
```

```http
POST /api/tools-materials
```

Create item.

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

For `tool`, unit auto `Nos` ah set agum.

Image upload supported:

```http
image: file, max 2MB
```

Same alias:

```http
POST /api/inventory/items
```

```http
GET /api/tools-materials/{id}
```

Single item details.

Same alias:

```http
GET /api/inventory/items/{id}
```

```http
PUT /api/tools-materials/{id}
POST /api/tools-materials/{id}/update
```

Update item. Mobile app PUT support illana POST update alias use panna mudiyum.

Same alias:

```http
PUT /api/inventory/items/{id}
POST /api/inventory/items/{id}/update
```

```http
DELETE /api/tools-materials/{id}
```

Delete item.

Important: item-ku stock transaction irundha delete panna mudiyathu. API `409` return pannum.

Same alias:

```http
DELETE /api/inventory/items/{id}
```

## Transaction Types

Allowed transaction types:

```json
{
  "purchase": "Purchase",
  "issue_to_site": "Issue to Site",
  "return_to_office": "Return to Office",
  "site_to_site": "Site to Site",
  "return_to_vendor": "Return to Vendor",
  "damage_wastage": "Damage / Wastage"
}
```

## Statuses

```json
{
  "draft": "Draft",
  "transferred": "Transferred",
  "returned": "Returned",
  "completed": "Completed",
  "cancelled": "Cancelled"
}
```

Stock affect agura statuses:

```text
transferred, returned, completed
```

`draft` and `cancelled` stock affect pannathu.

## Transaction Endpoints

```http
GET /api/tools-material-assignments
```

Transaction list with pagination and summary.

Filters:

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

Same alias:

```http
GET /api/inventory/transactions
```

```http
POST /api/tools-material-assignments
```

Create stock transaction.

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

Same alias:

```http
POST /api/inventory/transactions
```

```http
GET /api/tools-material-assignments/{id}
```

Single transaction details.

```http
PUT /api/tools-material-assignments/{id}
POST /api/tools-material-assignments/{id}/update
```

Update transaction.

```http
DELETE /api/tools-material-assignments/{id}
```

Delete transaction.

## Transaction Rules

Purchase:

```json
{
  "transaction_type": "purchase",
  "vendor_id": 1,
  "destination_type": "office",
  "quantity": 10,
  "rate": 100
}
```

Vendor required. Destination office/site. Site purchase na `to_project_id` required.

Issue to Site:

```json
{
  "transaction_type": "issue_to_site",
  "to_project_id": 2,
  "quantity": 5,
  "rate": 100
}
```

Office stock irukanum. Stock insufficient na validation error.

Return to Office:

```json
{
  "transaction_type": "return_to_office",
  "from_project_id": 2,
  "quantity": 5,
  "rate": 100
}
```

Site stock irukanum.

Site to Site:

```json
{
  "transaction_type": "site_to_site",
  "from_project_id": 2,
  "to_project_id": 3,
  "quantity": 5,
  "rate": 100
}
```

From site and to site different ah irukanum.

Return to Vendor:

```json
{
  "transaction_type": "return_to_vendor",
  "source_type": "office",
  "vendor_id": 1,
  "quantity": 5,
  "rate": 100
}
```

Vendor required. Source site na `from_project_id` required. Vendor advance balance also adjust agum.

Damage / Wastage:

```json
{
  "transaction_type": "damage_wastage",
  "source_type": "office",
  "quantity": 2,
  "rate": 100
}
```

Source site na `from_project_id` required.

## Response Structure

## Common Error Responses

Unauthorized token missing/invalid:

```json
{
  "message": "Unauthenticated."
}
```

Permission illa na:

```json
{
  "message": "This action is unauthorized."
}
```

Validation error:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "quantity": [
      "Insufficient stock. Available quantity is 4.00 Bag."
    ]
  }
}
```

Delete blocked because stock transactions exist:

```json
{
  "message": "This tool / material has stock transactions. Delete the transactions before deleting this item."
}
```

HTTP status: `409`

## Options Response

Endpoint:

```http
GET /api/tools-materials/options
GET /api/inventory/options
```

Response:

```json
{
  "transaction_types": {
    "purchase": "Purchase",
    "issue_to_site": "Issue to Site",
    "return_to_office": "Return to Office",
    "site_to_site": "Site to Site",
    "return_to_vendor": "Return to Vendor",
    "damage_wastage": "Damage / Wastage"
  },
  "statuses": {
    "draft": "Draft",
    "transferred": "Transferred",
    "returned": "Returned",
    "completed": "Completed",
    "cancelled": "Cancelled"
  },
  "source_types": {
    "office": "Office",
    "site": "Site",
    "vendor": "Vendor"
  },
  "destination_types": {
    "office": "Office",
    "site": "Site",
    "vendor": "Vendor",
    "wastage": "Wastage"
  },
  "tools_materials": [],
  "projects": [
    {
      "id": 1,
      "name": "Demo Project",
      "project_code": "PRJ-001"
    }
  ],
  "vendors": [
    {
      "id": 1,
      "name": "ABC Traders"
    }
  ]
}
```

## Item List Response

Endpoint:

```http
GET /api/tools-materials
GET /api/inventory/items
```

Response:

```json
{
  "summary": {
    "items": 2,
    "tools": 1,
    "materials": 1,
    "stock_value": 42000,
    "low_stock": 0
  },
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "item_type": "material",
      "sku": "CEM-001",
      "name": "Cement",
      "unit": "Bag",
      "image_path": null,
      "image_url": null,
      "description": "OPC Cement",
      "opening_quantity": 100,
      "opening_rate": 420,
      "opening_amount": 42000,
      "reorder_level": 20,
      "office_stock_quantity": 80,
      "site_stock_quantity": 20,
      "stock_quantity": 100,
      "stock_amount": 42000,
      "is_low_stock": false,
      "active_status": true,
      "balances": [
        {
          "label": "Office",
          "quantity": 80,
          "amount": 33600
        },
        {
          "label": "Demo Project",
          "quantity": 20,
          "amount": 8400
        }
      ]
    }
  ],
  "first_page_url": "http://localhost/api/tools-materials?page=1",
  "from": 1,
  "last_page": 1,
  "last_page_url": "http://localhost/api/tools-materials?page=1",
  "links": [],
  "next_page_url": null,
  "path": "http://localhost/api/tools-materials",
  "per_page": 15,
  "prev_page_url": null,
  "to": 1,
  "total": 1
}
```

## Item Create Response

Endpoint:

```http
POST /api/tools-materials
POST /api/inventory/items
```

HTTP status: `201`

Response:

```json
{
  "message": "Tool / material created successfully.",
  "tool_material": {
    "id": 1,
    "item_type": "material",
    "sku": "CEM-001",
    "name": "Cement",
    "unit": "Bag",
    "image_url": null,
    "opening_quantity": 100,
    "opening_rate": 420,
    "opening_amount": 42000,
    "reorder_level": 20,
    "office_stock_quantity": 80,
    "site_stock_quantity": 20,
    "stock_quantity": 100,
    "stock_amount": 42000,
    "is_low_stock": false,
    "active_status": true,
    "balances": []
  }
}
```

## Item Show Response

Endpoint:

```http
GET /api/tools-materials/{id}
GET /api/inventory/items/{id}
```

Response:

```json
{
  "tool_material": {
    "id": 1,
    "item_type": "material",
    "sku": "CEM-001",
    "name": "Cement",
    "unit": "Bag",
    "image_path": null,
    "image_url": null,
    "description": "OPC Cement",
    "opening_quantity": 100,
    "opening_rate": 420,
    "opening_amount": 42000,
    "reorder_level": 20,
    "office_stock_quantity": 80,
    "site_stock_quantity": 20,
    "stock_quantity": 100,
    "stock_amount": 42000,
    "is_low_stock": false,
    "active_status": true,
    "balances": []
  }
}
```

## Item Update Response

Endpoint:

```http
PUT /api/tools-materials/{id}
POST /api/tools-materials/{id}/update
PUT /api/inventory/items/{id}
POST /api/inventory/items/{id}/update
```

Response:

```json
{
  "message": "Tool / material updated successfully.",
  "tool_material": {
    "id": 1,
    "item_type": "material",
    "sku": "CEM-001",
    "name": "Cement Updated",
    "unit": "Bag",
    "image_path": null,
    "image_url": null,
    "description": "Updated description",
    "opening_quantity": 100,
    "opening_rate": 430,
    "opening_amount": 43000,
    "reorder_level": 25,
    "office_stock_quantity": 100,
    "site_stock_quantity": 0,
    "stock_quantity": 100,
    "stock_amount": 43000,
    "is_low_stock": false,
    "active_status": true,
    "balances": []
  }
}
```

## Item Delete Response

Endpoint:

```http
DELETE /api/tools-materials/{id}
DELETE /api/inventory/items/{id}
```

Response:

```json
{
  "message": "Tool / material deleted successfully."
}
```

## Transaction List Response

Endpoint:

```http
GET /api/tools-material-assignments
GET /api/inventory/transactions
```

Response:

```json
{
  "summary": {
    "transactions": 1,
    "completed": 1,
    "quantity": 50,
    "amount": 5000,
    "vendor_returns": 0
  },
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "reference_no": "TM-260721-0001",
      "status": "completed",
      "status_label": "Returned",
      "transaction_type": "purchase",
      "transaction_label": "Purchase",
      "tool_material": {},
      "from_project": null,
      "to_project": null,
      "vendor": {
        "id": 1,
        "name": "ABC Traders"
      },
      "handled_by": {
        "id": 1,
        "name": "Admin"
      },
      "source_type": "vendor",
      "destination_type": "office",
      "quantity": 50,
      "unit": "Bag",
      "rate": 100,
      "amount": 5000,
      "receiver_name": "Ravi",
      "vehicle_no": "TN01AB1234",
      "purpose": "Site stock",
      "notes": "Initial purchase",
      "transferred_at": "2026-07-21T05:00:00.000000Z",
      "created_at": "2026-07-21T05:00:00.000000Z",
      "updated_at": "2026-07-21T05:00:00.000000Z"
    }
  ],
  "first_page_url": "http://localhost/api/tools-material-assignments?page=1",
  "from": 1,
  "last_page": 1,
  "last_page_url": "http://localhost/api/tools-material-assignments?page=1",
  "links": [],
  "next_page_url": null,
  "path": "http://localhost/api/tools-material-assignments",
  "per_page": 15,
  "prev_page_url": null,
  "to": 1,
  "total": 1
}
```

## Transaction Create Response

Endpoint:

```http
POST /api/tools-material-assignments
POST /api/inventory/transactions
```

HTTP status: `201`

Response:

```json
{
  "message": "Inventory transaction saved successfully.",
  "transaction": {
    "id": 1,
    "reference_no": "TM-260721-0001",
    "status": "completed",
    "transaction_type": "purchase",
    "tool_material": {},
    "from_project": null,
    "to_project": null,
    "vendor": {},
    "handled_by": {},
    "source_type": "vendor",
    "destination_type": "office",
    "quantity": 50,
    "unit": "Bag",
    "rate": 100,
    "amount": 5000,
    "transferred_at": "2026-07-21T..."
  }
}
```

## Transaction Show Response

Endpoint:

```http
GET /api/tools-material-assignments/{id}
GET /api/inventory/transactions/{id}
```

Response:

```json
{
  "transaction": {
    "id": 1,
    "reference_no": "TM-260721-0001",
    "status": "completed",
    "status_label": "Returned",
    "transaction_type": "purchase",
    "transaction_label": "Purchase",
    "tool_material": {},
    "from_project": null,
    "to_project": null,
    "vendor": {},
    "handled_by": {},
    "source_type": "vendor",
    "destination_type": "office",
    "quantity": 50,
    "unit": "Bag",
    "rate": 100,
    "amount": 5000,
    "receiver_name": "Ravi",
    "vehicle_no": "TN01AB1234",
    "purpose": "Site stock",
    "notes": "Initial purchase",
    "transferred_at": "2026-07-21T05:00:00.000000Z",
    "created_at": "2026-07-21T05:00:00.000000Z",
    "updated_at": "2026-07-21T05:00:00.000000Z"
  }
}
```

## Transaction Update Response

Endpoint:

```http
PUT /api/tools-material-assignments/{id}
POST /api/tools-material-assignments/{id}/update
PUT /api/inventory/transactions/{id}
POST /api/inventory/transactions/{id}/update
```

Response:

```json
{
  "message": "Inventory transaction updated successfully.",
  "transaction": {
    "id": 1,
    "reference_no": "TM-260721-0001",
    "status": "completed",
    "status_label": "Returned",
    "transaction_type": "issue_to_site",
    "transaction_label": "Issue to Site",
    "tool_material": {},
    "from_project": null,
    "to_project": {
      "id": 2,
      "name": "Demo Project"
    },
    "vendor": null,
    "handled_by": {},
    "source_type": "office",
    "destination_type": "site",
    "quantity": 25,
    "unit": "Bag",
    "rate": 100,
    "amount": 2500,
    "receiver_name": "Ravi",
    "vehicle_no": "TN01AB1234",
    "purpose": "Issue to site",
    "notes": "Updated transaction",
    "transferred_at": "2026-07-21T05:00:00.000000Z",
    "created_at": "2026-07-21T05:00:00.000000Z",
    "updated_at": "2026-07-21T05:10:00.000000Z"
  }
}
```

## Transaction Delete Response

Endpoint:

```http
DELETE /api/tools-material-assignments/{id}
DELETE /api/inventory/transactions/{id}
```

Response:

```json
{
  "message": "Inventory transaction deleted successfully."
}
```

## Short Summary

Mobile app-ku rendu naming support panniruku:

- New/simple naming: `/api/tools-materials`, `/api/tools-material-assignments`
- Generic inventory alias: `/api/inventory/items`, `/api/inventory/transactions`

Both same controller/function call pannum. Web la irukura same stock concept API la implement panniruku: item master + stock ledger transactions + project/vendor/location based validation.
