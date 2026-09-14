# Coffee Shop Database Design

## 1. Document status and version

| Field | Value |
| --- | --- |
| Task | CAFE-DB-001B — Complete Frozen Database Design |
| Version | 1.2 |
| Status | DATABASE DESIGN: FROZEN |
| Frozen date | 2026-08-16 |
| Target | MySQL, Laravel migrations |
| Business tables | Exactly 18 |

This document is the canonical logical database design for CAFE-DB-002 through CAFE-DB-008. It does not alter migrations or a live database. Framework tables such as migrations, cache, cache_locks, jobs, job_batches, failed_jobs, sessions, and password_reset_tokens are excluded from the business-table count.

## 2. Authority and sources

Conflicts are resolved in this order:

1. DacTa_QuanLyQuanCafe_v1.2_DatabaseFrozen.docx (approved SRS v1.2 Database Frozen).
2. Approved change requests or technical decisions issued after the SRS.
3. Official team decisions.
4. requirements.md.
5. architecture.md.
6. The previous database-design.md.
7. plan.md.
8. Existing migrations.
9. The current physical schema.

No approved post-SRS change request, separate decision log, CAFE-DB-001 report, CAFE-DB-001A report, or CAFE-DB-AUDIT-001 report exists in the repository. CAFE-DB-002 and CAFE-DB-003 reports and all existing migrations were reviewed read-only. A migration or physical schema mismatch does not override the SRS.

## 3. Database conventions

- Tables use plural snake_case; columns use snake_case.
- Every business table has id BIGINT UNSIGNED AUTO_INCREMENT as its single primary key.
- Every FK is BIGINT UNSIGNED and matches the referenced id.
- created_at and updated_at are Laravel TIMESTAMP columns, nullable with default NULL.
- Approved master tables use deleted_at TIMESTAMP NULL for soft deletion.
- Every column below states nullability and default explicitly; “none” means no database default.
- Monetary values use DECIMAL, never FLOAT/DOUBLE. Menu prices use DECIMAL(12,2); order/payment totals use DECIMAL(15,2).
- Ingredient quantities use DECIMAL(12,3).
- ENUM sets are closed sets. Transitions and cross-row concurrency rules remain application rules.
- Every FK explicitly uses ON DELETE RESTRICT and ON UPDATE NO ACTION. IDs are immutable; soft delete preserves master rows and historical references.
- No delete cascade is approved for business data.
- All 18 tables have created_at and updated_at. Only the ten approved master tables have deleted_at.

## 4. Approved business tables and canonical order

| # | Table | Domain |
| ---: | --- | --- |
| 1 | users | Internal users |
| 2 | customers | Customer information |
| 3 | areas | Physical areas |
| 4 | categories | Menu categories |
| 5 | toppings | Shared toppings |
| 6 | ingredients | Inventory master |
| 7 | promotions | Promotion master |
| 8 | cafe_tables | Physical tables |
| 9 | products | Menu products |
| 10 | product_variants | Sellable variants |
| 11 | reservations | Reservation history |
| 12 | product_recipes | Recipe configuration |
| 13 | orders | Sales orders |
| 14 | order_items | Sold items |
| 15 | payments | Payment attempts/history |
| 16 | order_promotions | Applied promotion history |
| 17 | inventory_transactions | Inventory movement history |
| 18 | order_item_toppings | Sold topping history |

The set is closed for version 1. Dashboard/reporting reads transactional data and has no dedicated business table. There is no nineteenth business table.

## 5. Relationship summary

The design contains 18 nodes and 23 FKs:

- areas 1:N cafe_tables.
- categories 1:N products; products 1:N product_variants.
- customers, cafe_tables, and users each have 1:N optional relationships to reservations.
- product_variants N:M ingredients through product_recipes.
- cafe_tables, customers, users, and reservations relate 1:N to orders; all except users are optional on orders.
- orders, products, and product_variants relate 1:N to order_items.
- orders 1:N payments; users 1:N payments.
- orders 1:0..1 order_promotions; promotions 1:N order_promotions.
- ingredients and users each relate 1:N to inventory_transactions.
- order_items N:M toppings through order_item_toppings.

## 6. Complete data dictionary

Legend: NN = NOT NULL; NULL = nullable; AI = auto increment; UQ = unique; FK = foreign key. Every table also includes created_at TIMESTAMP NULL default NULL and updated_at TIMESTAMP NULL default NULL.

### 6.1 users

| Column | Type | Null/default/constraints | Meaning |
| --- | --- | --- | --- |
| id | BIGINT UNSIGNED | NN, AI, PK | User identifier |
| name | VARCHAR(255) | NN, no default | Full name |
| email | VARCHAR(255) | NN, no default, UQ | Login email |
| password | VARCHAR(255) | NN, no default | Password hash; plaintext prohibited |
| phone | VARCHAR(20) | NULL, default NULL | Phone |
| avatar | VARCHAR(255) | NULL, default NULL | Avatar path |
| role | ENUM(admin, staff) | NN, no default | Internal role |
| status | ENUM(active, inactive) | NN, default active | Account state |
| last_login_at | DATETIME | NULL, default NULL | Last successful login |
| deleted_at | TIMESTAMP | NULL, default NULL | Soft delete |

### 6.2 customers

| Column | Type | Null/default/constraints | Meaning |
| --- | --- | --- | --- |
| id | BIGINT UNSIGNED | NN, AI, PK | Customer identifier |
| name | VARCHAR(255) | NULL, default NULL | Customer name |
| phone | VARCHAR(20) | NULL, default NULL, UQ | Optional unique phone |
| email | VARCHAR(255) | NULL, default NULL | Optional email; not unique in v1 |
| birthday | DATE | NULL, default NULL | Birthday |
| status | ENUM(active, inactive) | NN, default active | Record state |
| deleted_at | TIMESTAMP | NULL, default NULL | Soft delete |

Version 1 has no points or total_spent column; loyalty and derived aggregates are not approved.

### 6.3 areas

| Column | Type | Null/default/constraints | Meaning |
| --- | --- | --- | --- |
| id | BIGINT UNSIGNED | NN, AI, PK | Area identifier |
| name | VARCHAR(100) | NN, no default | Area name |
| description | TEXT | NULL, default NULL | Description |
| status | ENUM(active, inactive) | NN, default active | State |
| deleted_at | TIMESTAMP | NULL, default NULL | Soft delete |

### 6.4 categories

| Column | Type | Null/default/constraints | Meaning |
| --- | --- | --- | --- |
| id | BIGINT UNSIGNED | NN, AI, PK | Category identifier |
| name | VARCHAR(255) | NN, no default | Name |
| description | TEXT | NULL, default NULL | Description |
| image | VARCHAR(255) | NULL, default NULL | Image path |
| display_order | INT | NN, default 0 | Display order |
| status | ENUM(active, inactive) | NN, default active | State |
| deleted_at | TIMESTAMP | NULL, default NULL | Soft delete |

### 6.5 toppings

| Column | Type | Null/default/constraints | Meaning |
| --- | --- | --- | --- |
| id | BIGINT UNSIGNED | NN, AI, PK | Topping identifier |
| name | VARCHAR(255) | NN, no default | Name |
| price | DECIMAL(12,2) | NN, default 0 | Current selling price |
| status | ENUM(available, unavailable) | NN, default available | Availability |
| deleted_at | TIMESTAMP | NULL, default NULL | Soft delete |

### 6.6 ingredients

| Column | Type | Null/default/constraints | Meaning |
| --- | --- | --- | --- |
| id | BIGINT UNSIGNED | NN, AI, PK | Ingredient identifier |
| name | VARCHAR(255) | NN, no default | Name |
| unit | VARCHAR(50) | NN, no default | Unit of measure |
| current_stock | DECIMAL(12,3) | NN, default 0 | Current quantity |
| minimum_stock | DECIMAL(12,3) | NN, default 0 | Low-stock threshold |
| cost_price | DECIMAL(12,2) | NN, default 0 | Current unit cost |
| status | ENUM(active, inactive) | NN, default active | State |
| deleted_at | TIMESTAMP | NULL, default NULL | Soft delete |

### 6.7 promotions

| Column | Type | Null/default/constraints | Meaning |
| --- | --- | --- | --- |
| id | BIGINT UNSIGNED | NN, AI, PK | Promotion identifier |
| code | VARCHAR(50) | NN, no default, UQ | Promotion code |
| name | VARCHAR(255) | NN, no default | Name |
| description | TEXT | NULL, default NULL | Description |
| discount_type | ENUM(percentage, fixed) | NN, no default | Discount method |
| discount_value | DECIMAL(12,2) | NN, no default | Percentage or fixed value |
| max_discount | DECIMAL(12,2) | NULL, default NULL | Percentage cap |
| minimum_order | DECIMAL(12,2) | NN, default 0 | Minimum subtotal |
| start_at | DATETIME | NN, no default | Effective start |
| end_at | DATETIME | NN, no default | Effective end |
| usage_limit | INT | NULL, default NULL | Optional usage cap |
| used_count | INT | NN, default 0 | Successful uses |
| status | ENUM(active, inactive) | NN, default active | State |
| deleted_at | TIMESTAMP | NULL, default NULL | Soft delete |

### 6.8 cafe_tables

| Column | Type | Null/default/constraints | Meaning |
| --- | --- | --- | --- |
| id | BIGINT UNSIGNED | NN, AI, PK | Table identifier |
| area_id | BIGINT UNSIGNED | NN, FK, no default | Parent area |
| table_code | VARCHAR(20) | NN, no default, UQ | Table code |
| table_name | VARCHAR(100) | NULL, default NULL | Display name |
| capacity | INT | NN, default 4 | Seat capacity |
| status | ENUM(available, occupied, maintenance) | NN, default available | Current operational state |
| qr_code | VARCHAR(255) | NULL, default NULL | QR value/path |
| deleted_at | TIMESTAMP | NULL, default NULL | Soft delete |

There is no reserved state. Future reservations are derived from reservations by time range.

### 6.9 products

| Column | Type | Null/default/constraints | Meaning |
| --- | --- | --- | --- |
| id | BIGINT UNSIGNED | NN, AI, PK | Product identifier |
| category_id | BIGINT UNSIGNED | NN, FK, no default | Parent category |
| name | VARCHAR(255) | NN, no default | Product name |
| slug | VARCHAR(255) | NN, no default, UQ | Public slug |
| description | TEXT | NULL, default NULL | Description |
| image | VARCHAR(255) | NULL, default NULL | Image path |
| status | ENUM(available, unavailable) | NN, default available | Availability |
| is_featured | BOOLEAN | NN, default false | Featured marker |
| deleted_at | TIMESTAMP | NULL, default NULL | Soft delete |

Products do not contain price; price belongs to product_variants.

### 6.10 product_variants

| Column | Type | Null/default/constraints | Meaning |
| --- | --- | --- | --- |
| id | BIGINT UNSIGNED | NN, AI, PK | Variant identifier |
| product_id | BIGINT UNSIGNED | NN, FK, no default | Parent product |
| name | VARCHAR(50) | NN, no default, UQ with product_id | Variant/size name |
| sku | VARCHAR(50) | NULL, default NULL, UQ | Optional SKU |
| price | DECIMAL(12,2) | NN, no default | Current selling price |
| status | ENUM(available, unavailable) | NN, default available | Availability |
| deleted_at | TIMESTAMP | NULL, default NULL | Soft delete |

No display_order column is approved in SRS v1.2.

### 6.11 reservations

| Column | Type | Null/default/constraints | Meaning |
| --- | --- | --- | --- |
| id | BIGINT UNSIGNED | NN, AI, PK | Reservation identifier |
| customer_id | BIGINT UNSIGNED | NULL, FK, default NULL | Optional known customer |
| table_id | BIGINT UNSIGNED | NULL, FK, default NULL | Assigned table; NULL while pending online |
| customer_name | VARCHAR(255) | NN, no default | Contact snapshot/name |
| customer_phone | VARCHAR(20) | NN, no default | Contact phone |
| guest_count | INT | NN, no default | Party size |
| reservation_start_at | DATETIME | NN, no default | Start time |
| reservation_end_at | DATETIME | NN, no default | End time |
| note | TEXT | NULL, default NULL | Notes |
| status | ENUM(pending, confirmed, arrived, completed, cancelled, no_show) | NN, default pending | Lifecycle |
| created_by | BIGINT UNSIGNED | NULL, FK, default NULL | Internal creator; NULL for public request |

Reservations do not use soft delete. Cancellation is status-based.

### 6.12 product_recipes

| Column | Type | Null/default/constraints | Meaning |
| --- | --- | --- | --- |
| id | BIGINT UNSIGNED | NN, AI, PK | Recipe row identifier |
| variant_id | BIGINT UNSIGNED | NN, FK, no default, UQ with ingredient_id | Variant |
| ingredient_id | BIGINT UNSIGNED | NN, FK, no default, UQ with variant_id | Ingredient |
| quantity | DECIMAL(12,3) | NN, no default | Required ingredient quantity |

Product recipes are configuration rows, have no deleted_at, and may be physically removed only while preserving already-recorded inventory history.

### 6.13 orders

| Column | Type | Null/default/constraints | Meaning |
| --- | --- | --- | --- |
| id | BIGINT UNSIGNED | NN, AI, PK | Order identifier |
| order_code | VARCHAR(50) | NN, no default, UQ | Display code |
| table_id | BIGINT UNSIGNED | NULL, FK, default NULL | Dine-in table |
| customer_id | BIGINT UNSIGNED | NULL, FK, default NULL | Optional customer |
| user_id | BIGINT UNSIGNED | NN, FK, no default | Staff member creating order |
| reservation_id | BIGINT UNSIGNED | NULL, FK, default NULL | Optional reservation |
| order_type | ENUM(dine_in, takeaway) | NN, default dine_in | Service type |
| status | ENUM(pending, completed, cancelled) | NN, default pending | Order lifecycle |
| subtotal | DECIMAL(15,2) | NN, default 0 | Items total |
| discount_amount | DECIMAL(15,2) | NN, default 0 | Applied discount |
| tax_amount | DECIMAL(15,2) | NN, default 0 | Tax/surcharge; v1 remains 0 without approved configuration |
| total_amount | DECIMAL(15,2) | NN, default 0 | subtotal - discount + tax |
| note | TEXT | NULL, default NULL | Notes |
| ordered_at | DATETIME | NN, no default | Order time |
| completed_at | DATETIME | NULL, default NULL | Completion time |

The SRS-approved audit FK is user_id, not created_by. Orders do not use soft delete.

### 6.14 order_items

| Column | Type | Null/default/constraints | Meaning |
| --- | --- | --- | --- |
| id | BIGINT UNSIGNED | NN, AI, PK | Item identifier |
| order_id | BIGINT UNSIGNED | NN, FK, no default | Parent order |
| product_id | BIGINT UNSIGNED | NN, FK, no default | Product reference |
| variant_id | BIGINT UNSIGNED | NN, FK, no default | Selected variant |
| product_name | VARCHAR(255) | NN, no default | Product-name snapshot |
| variant_name | VARCHAR(50) | NULL, default NULL | Variant-name snapshot |
| quantity | INT | NN, no default | Item quantity |
| unit_price | DECIMAL(12,2) | NN, no default | Unit-price snapshot |
| topping_amount | DECIMAL(12,2) | NN, default 0 | Total topping amount |
| subtotal | DECIMAL(15,2) | NN, no default | Line subtotal |
| note | VARCHAR(500) | NULL, default NULL | Preparation note |
| status | ENUM(pending, preparing, ready, served, cancelled) | NN, default pending | Preparation state |

The SRS uses product_id and variant_id, not product_variant_id. Order items do not use soft delete.

### 6.15 payments

| Column | Type | Null/default/constraints | Meaning |
| --- | --- | --- | --- |
| id | BIGINT UNSIGNED | NN, AI, PK | Payment attempt identifier |
| order_id | BIGINT UNSIGNED | NN, FK, no default | Parent order |
| payment_code | VARCHAR(50) | NN, no default, UQ | Internal payment code |
| payment_method | ENUM(cash, bank_transfer) | NN, no default | Method |
| amount | DECIMAL(15,2) | NN, no default | Attempt amount |
| transaction_code | VARCHAR(100) | NULL, default NULL | Bank/reference code |
| status | ENUM(pending, paid, failed, refunded) | NN, default pending | Attempt state |
| paid_at | DATETIME | NULL, default NULL | Successful payment time |
| created_by | BIGINT UNSIGNED | NN, FK, no default | Recording user |

Payments do not use soft delete. Multiple attempts may exist; at most one may be paid per order by an application rule.

### 6.16 order_promotions

| Column | Type | Null/default/constraints | Meaning |
| --- | --- | --- | --- |
| id | BIGINT UNSIGNED | NN, AI, PK | Applied-promotion identifier |
| order_id | BIGINT UNSIGNED | NN, FK, no default, UQ | One promotion maximum per order |
| promotion_id | BIGINT UNSIGNED | NN, FK, no default | Promotion reference |
| promotion_code | VARCHAR(50) | NN, no default | Code snapshot |
| discount_amount | DECIMAL(15,2) | NN, no default | Actual discount snapshot |

SRS v1.2 does not approve promotion_name, discount_type, or discount_value snapshot columns. The immutable code and actual amount are sufficient for the approved history.

### 6.17 inventory_transactions

| Column | Type | Null/default/constraints | Meaning |
| --- | --- | --- | --- |
| id | BIGINT UNSIGNED | NN, AI, PK | Movement identifier |
| ingredient_id | BIGINT UNSIGNED | NN, FK, no default | Ingredient |
| type | ENUM(import, consume, adjustment, waste) | NN, no default | Movement type |
| quantity | DECIMAL(12,3) | NN, no default | Positive movement quantity |
| before_quantity | DECIMAL(12,3) | NN, no default | Stock before |
| after_quantity | DECIMAL(12,3) | NN, no default | Stock after |
| reference_type | VARCHAR(50) | NULL, default NULL | Reference discriminator |
| reference_id | BIGINT UNSIGNED | NULL, default NULL | Reference identifier |
| note | TEXT | NULL, default NULL | Reason/notes |
| created_by | BIGINT UNSIGNED | NN, FK, no default | User causing/recording movement |

There is no order_item_id FK. Automatic consumption uses reference_type = order_item and reference_id = order_items.id. This polymorphic reference is application-validated and creates no dependency edge.

### 6.18 order_item_toppings

| Column | Type | Null/default/constraints | Meaning |
| --- | --- | --- | --- |
| id | BIGINT UNSIGNED | NN, AI, PK | Sold-topping identifier |
| order_item_id | BIGINT UNSIGNED | NN, FK, no default, UQ with topping_id | Parent item |
| topping_id | BIGINT UNSIGNED | NN, FK, no default, UQ with order_item_id | Topping reference |
| topping_name | VARCHAR(255) | NN, no default | Name snapshot |
| price | DECIMAL(12,2) | NN, no default | Unit-price snapshot |
| quantity | INT | NN, default 1 | Quantity |

SRS v1.2 does not approve a subtotal column here; the order_items.topping_amount aggregate is calculated from price × quantity.

## 7. Primary key matrix

| Table | PK | Type | Auto increment | Result/source |
| --- | --- | --- | :---: | --- |
| users | id | BIGINT UNSIGNED | Yes | Frozen; SRS DB-02 |
| customers | id | BIGINT UNSIGNED | Yes | Frozen; SRS DB-02 |
| areas | id | BIGINT UNSIGNED | Yes | Frozen; SRS DB-02 |
| categories | id | BIGINT UNSIGNED | Yes | Frozen; SRS DB-02 |
| toppings | id | BIGINT UNSIGNED | Yes | Frozen; SRS DB-02 |
| ingredients | id | BIGINT UNSIGNED | Yes | Frozen; SRS DB-02 |
| promotions | id | BIGINT UNSIGNED | Yes | Frozen; SRS DB-02 |
| cafe_tables | id | BIGINT UNSIGNED | Yes | Frozen; SRS DB-02 |
| products | id | BIGINT UNSIGNED | Yes | Frozen; SRS DB-02 |
| product_variants | id | BIGINT UNSIGNED | Yes | Frozen; SRS DB-02 |
| reservations | id | BIGINT UNSIGNED | Yes | Frozen; SRS DB-02 |
| product_recipes | id | BIGINT UNSIGNED | Yes | Frozen; SRS DB-02 |
| orders | id | BIGINT UNSIGNED | Yes | Frozen; SRS DB-02 |
| order_items | id | BIGINT UNSIGNED | Yes | Frozen; SRS DB-02 |
| payments | id | BIGINT UNSIGNED | Yes | Frozen; SRS DB-02 |
| order_promotions | id | BIGINT UNSIGNED | Yes | Frozen; SRS DB-02 |
| inventory_transactions | id | BIGINT UNSIGNED | Yes | Frozen; SRS DB-02 |
| order_item_toppings | id | BIGINT UNSIGNED | Yes | Frozen; SRS DB-02 |

There are 18 single-column primary keys and no composite primary key.

## 8. Foreign key matrix

All FKs have an index either directly or as the leading column of a unique/composite index. Delete is RESTRICT to preserve master/configuration/history links; update is NO ACTION because identifiers are immutable.

| # | Source | Target | Nullable | On delete | On update | Index | Reason/source |
| ---: | --- | --- | :---: | --- | --- | --- | --- |
| 1 | cafe_tables.area_id | areas.id | No | RESTRICT | NO ACTION | FK index | Table belongs to area; SRS table 23 |
| 2 | products.category_id | categories.id | No | RESTRICT | NO ACTION | FK index | Product belongs to category; table 26 |
| 3 | product_variants.product_id | products.id | No | RESTRICT | NO ACTION | UQ(product_id,name) prefix | Variant belongs to product; table 27 |
| 4 | reservations.customer_id | customers.id | Yes | RESTRICT | NO ACTION | FK index | Optional known customer; DB-03/BR20 |
| 5 | reservations.table_id | cafe_tables.id | Yes | RESTRICT | NO ACTION | reservation lookup index | Assigned only on confirmation; DB-03/BR20 |
| 6 | reservations.created_by | users.id | Yes | RESTRICT | NO ACTION | FK index | NULL for public request; DB-03/BR20 |
| 7 | product_recipes.variant_id | product_variants.id | No | RESTRICT | NO ACTION | UQ(variant_id,ingredient_id) prefix | Recipe variant; table 36 |
| 8 | product_recipes.ingredient_id | ingredients.id | No | RESTRICT | NO ACTION | FK index | Recipe ingredient; table 36 |
| 9 | orders.table_id | cafe_tables.id | Yes | RESTRICT | NO ACTION | order table/status/time index | NULL for takeaway; table 29 |
| 10 | orders.customer_id | customers.id | Yes | RESTRICT | NO ACTION | FK index | Walk-in may be anonymous; table 29 |
| 11 | orders.user_id | users.id | No | RESTRICT | NO ACTION | FK index | Internal creator; table 29 |
| 12 | orders.reservation_id | reservations.id | Yes | RESTRICT | NO ACTION | FK index | Optional origin; table 29 |
| 13 | order_items.order_id | orders.id | No | RESTRICT | NO ACTION | FK index | Item belongs to order; table 30 |
| 14 | order_items.product_id | products.id | No | RESTRICT | NO ACTION | FK index | Master reference plus snapshot; table 30 |
| 15 | order_items.variant_id | product_variants.id | No | RESTRICT | NO ACTION | FK index | Selected variant; table 30 |
| 16 | payments.order_id | orders.id | No | RESTRICT | NO ACTION | payment order/status index | Payment history; table 32 |
| 17 | payments.created_by | users.id | No | RESTRICT | NO ACTION | FK index | Recording user; table 32 |
| 18 | order_promotions.order_id | orders.id | No | RESTRICT | NO ACTION | UQ(order_id) | Maximum one promotion; DB-12/BR15 |
| 19 | order_promotions.promotion_id | promotions.id | No | RESTRICT | NO ACTION | FK index | Applied promotion reference; table 34 |
| 20 | inventory_transactions.ingredient_id | ingredients.id | No | RESTRICT | NO ACTION | ingredient/time index | Movement ingredient; table 37 |
| 21 | inventory_transactions.created_by | users.id | No | RESTRICT | NO ACTION | FK index | Required audit actor; DB-11/BR35 |
| 22 | order_item_toppings.order_item_id | order_items.id | No | RESTRICT | NO ACTION | UQ(order_item_id,topping_id) prefix | Sold topping belongs to item; table 31 |
| 23 | order_item_toppings.topping_id | toppings.id | No | RESTRICT | NO ACTION | FK index | Topping reference plus snapshot; table 31 |

Nullable FK decisions are final: reservations.customer_id, reservations.table_id, reservations.created_by, orders.table_id, orders.customer_id, and orders.reservation_id are nullable; all other business FKs are NOT NULL. Nullable does not imply SET NULL on deletion because the SRS requires preservation of historical references and master records use soft delete.
## 9. DECIMAL precision matrix

| Table.column | Meaning | Precision | Nullable | Default | Validation/source |
| --- | --- | --- | :---: | --- | --- |
| toppings.price | Current topping price | 12,2 | No | 0 | >= 0; BR25 |
| ingredients.current_stock | Current stock | 12,3 | No | 0 | >= 0; BR22/25 |
| ingredients.minimum_stock | Threshold | 12,3 | No | 0 | >= 0; BR25 |
| ingredients.cost_price | Unit cost | 12,2 | No | 0 | >= 0; BR25 |
| promotions.discount_value | Discount value | 12,2 | No | none | >= 0; table 33 |
| promotions.max_discount | Percentage cap | 12,2 | Yes | NULL | >= 0 when present; BR14 |
| promotions.minimum_order | Eligibility subtotal | 12,2 | No | 0 | >= 0; BR12 |
| product_variants.price | Selling price | 12,2 | No | none | >= 0; BR25 |
| product_recipes.quantity | Recipe quantity | 12,3 | No | none | > 0; BR25 |
| orders.subtotal | Item total | 15,2 | No | 0 | >= 0 |
| orders.discount_amount | Discount total | 15,2 | No | 0 | 0..subtotal; BR14 |
| orders.tax_amount | Tax/surcharge | 15,2 | No | 0 | >= 0; BR29 |
| orders.total_amount | Payable total | 15,2 | No | 0 | subtotal-discount+tax |
| order_items.unit_price | Price snapshot | 12,2 | No | none | >= 0; BR09/25 |
| order_items.topping_amount | Topping total | 12,2 | No | 0 | >= 0; BR10 |
| order_items.subtotal | Line total | 15,2 | No | none | >= 0 |
| payments.amount | Attempt amount | 15,2 | No | none | > 0; paid amount equals order total; BR16/17 |
| order_promotions.discount_amount | Actual discount | 15,2 | No | none | >= 0 and <= order subtotal; BR14 |
| inventory_transactions.quantity | Movement magnitude | 12,3 | No | none | > 0; table 37 |
| inventory_transactions.before_quantity | Stock before | 12,3 | No | none | >= 0; BR25 |
| inventory_transactions.after_quantity | Stock after | 12,3 | No | none | >= 0; BR22/25 |
| order_item_toppings.price | Price snapshot | 12,2 | No | none | >= 0; BR10/25 |

customers.total_spent and order_item_toppings.subtotal are intentionally absent because they are not approved SRS columns.

## 10. Status and enum matrix

| Table.column | Approved values | Default | Transition/enforcement |
| --- | --- | --- | --- |
| users.role | admin, staff | none | ENUM; authorization in application |
| users.status | active, inactive | active | ENUM; application lifecycle |
| customers.status | active, inactive | active | ENUM |
| areas.status | active, inactive | active | ENUM |
| categories.status | active, inactive | active | ENUM |
| toppings.status | available, unavailable | available | ENUM |
| ingredients.status | active, inactive | active | ENUM |
| promotions.discount_type | percentage, fixed | none | ENUM; calculation in service |
| promotions.status | active, inactive | active | ENUM |
| cafe_tables.status | available, occupied, maintenance | available | ENUM; reserved prohibited |
| products.status | available, unavailable | available | ENUM |
| product_variants.status | available, unavailable | available | ENUM |
| reservations.status | pending, confirmed, arrived, completed, cancelled, no_show | pending | ENUM; transitions/application locking |
| orders.order_type | dine_in, takeaway | dine_in | ENUM |
| orders.status | pending, completed, cancelled | pending | ENUM; no preparation states |
| order_items.status | pending, preparing, ready, served, cancelled | pending | ENUM; transition service |
| payments.payment_method | cash, bank_transfer | none | ENUM |
| payments.status | pending, paid, failed, refunded | pending | ENUM; one-paid rule in application |
| inventory_transactions.type | import, consume, adjustment, waste | none | ENUM |

## 11. Soft delete matrix

| Table | Soft delete | deleted_at | Reason/source |
| --- | :---: | --- | --- |
| users | Yes | TIMESTAMP NULL | Master referenced by history; DB-15 |
| customers | Yes | TIMESTAMP NULL | Customer master; DB-15 |
| areas | Yes | TIMESTAMP NULL | Physical master; DB-15 |
| categories | Yes | TIMESTAMP NULL | Menu master; DB-15 |
| toppings | Yes | TIMESTAMP NULL | Menu master; snapshots retain sales; DB-15 |
| ingredients | Yes | TIMESTAMP NULL | Inventory master; DB-15 |
| promotions | Yes | TIMESTAMP NULL | Promotion master; DB-15 |
| cafe_tables | Yes | TIMESTAMP NULL | Physical master; DB-15 |
| products | Yes | TIMESTAMP NULL | Menu master; DB-15 |
| product_variants | Yes | TIMESTAMP NULL | Sellable master; DB-15 |
| reservations | No | absent | Historical lifecycle; BR27 |
| product_recipes | No | absent | Current configuration; history is in movements |
| orders | No | absent | Historical transaction; BR27 |
| order_items | No | absent | Historical detail; BR27 |
| payments | No | absent | Payment history; BR27 |
| order_promotions | No | absent | Applied snapshot; BR27 |
| inventory_transactions | No | absent | Immutable movement log; BR27 |
| order_item_toppings | No | absent | Historical snapshot; BR27 |

## 12. Unique constraint matrix

| Constraint | Columns | Source/reason |
| --- | --- | --- |
| users_email_unique | users(email) | Login identity; DB-17 |
| customers_phone_unique | customers(phone) | SRS table 21; multiple NULL allowed |
| cafe_tables_table_code_unique | cafe_tables(table_code) | Business code; DB-17 |
| products_slug_unique | products(slug) | Public identity; DB-17 |
| promotions_code_unique | promotions(code) | Promotion identity; DB-17 |
| product_variants_sku_unique | product_variants(sku) | Optional SKU; multiple NULL allowed; DB-17 |
| product_variants_product_name_unique | product_variants(product_id, name) | Variant name within product; DB-17/BR30 |
| product_recipes_variant_ingredient_unique | product_recipes(variant_id, ingredient_id) | One recipe row per ingredient; DB-17/BR30 |
| orders_order_code_unique | orders(order_code) | SRS table 29 business code |
| payments_payment_code_unique | payments(payment_code) | DB-17 |
| order_promotions_order_unique | order_promotions(order_id) | One promotion per order; DB-12/17 |
| order_item_toppings_item_topping_unique | order_item_toppings(order_item_id, topping_id) | One aggregated topping row per item; DB-17/BR30 |

No unique constraint is approved for customer email, names, reservation-to-order, payment order_id, or transaction_code.

## 13. Index matrix

Unique indexes above are not duplicated. Required non-unique indexes are:

| Table | Columns | Purpose |
| --- | --- | --- |
| cafe_tables | area_id | FK/area listing |
| products | category_id | FK/category listing |
| reservations | (table_id, status, reservation_start_at, reservation_end_at) | Overlap/availability query; DB-18 |
| reservations | customer_id | Customer history/FK |
| reservations | created_by | Creator audit/FK |
| reservations | (status, reservation_start_at) | Pending/time operations |
| product_recipes | ingredient_id | Reverse recipe/FK |
| orders | (table_id, status, ordered_at) | Active table order/time; DB-18 |
| orders | (status, ordered_at) | Reporting/operations; DB-18 |
| orders | customer_id | Customer order history/FK |
| orders | user_id | Staff order history/FK |
| orders | reservation_id | Reservation conversion/FK |
| order_items | order_id | Order details/FK |
| order_items | product_id | Product sales/FK |
| order_items | variant_id | Variant sales/FK |
| order_items | (status, created_at) | Preparation queue |
| payments | (order_id, status) | Payment condition/history; DB-18 |
| payments | created_by | Creator audit/FK |
| payments | transaction_code | External-reference lookup |
| order_promotions | promotion_id | Promotion usage/FK |
| inventory_transactions | (ingredient_id, created_at) | Stock ledger; DB-18 |
| inventory_transactions | created_by | Creator audit/FK |
| inventory_transactions | (reference_type, reference_id) | Polymorphic trace lookup |
| order_item_toppings | topping_id | Topping sales/FK |

product_variants.product_id, product_recipes.variant_id, order_promotions.order_id, and order_item_toppings.order_item_id are already leading columns of approved unique indexes.

## 14. Check constraints and application validation

| Rule | Database enforcement | Application enforcement |
| --- | --- | --- |
| capacity > 0 | CHECK on cafe_tables | FormRequest |
| guest_count > 0; end > start | CHECK on reservations | FormRequest; overlap/capacity under transaction |
| All prices/costs and order amounts >= 0 | CHECK per row | FormRequest/service calculations |
| product_recipes.quantity > 0 | CHECK | FormRequest |
| order_items.quantity > 0 | CHECK | FormRequest |
| order_item_toppings.quantity > 0 | CHECK | FormRequest |
| inventory quantity > 0; before/after >= 0 | CHECK | Inventory service/transaction |
| promotion start_at < end_at, counts nonnegative | CHECK | FormRequest/service |
| discount_amount <= subtotal | CHECK where same order row; cross-table value in service | Promotion service |
| total_amount = subtotal - discount + tax | CHECK on orders | Order service recalculates |
| confirmed reservation has table and no overlap | Not portable as row CHECK | Reservation service + locking |
| one active dine-in order per table | Not portable as simple unique index | Order service + locking |
| at most one paid payment/order | MySQL has no portable partial unique index | Payment service + transaction/locking |
| variant belongs to product on order item | Two FKs do not prove pair | Order service validates master relationship |
| valid status transitions | ENUM only limits values | Domain service validates transitions |

These row-level CHECK constraints are part of the frozen target schema. Existing DB-002/DB-003 migrations that omit applicable checks require a later correction task; this task does not edit them.

## 15. Snapshot policy

- order_items stores product_name, variant_name, unit_price, quantity, topping_amount, and subtotal at sale time. Historical display uses these snapshots, not current menu data.
- order_item_toppings stores topping_name, price, and quantity. Its amount is price × quantity and contributes to order_items.topping_amount.
- order_promotions stores promotion_code and the actual discount_amount applied. SRS v1.2 does not add unapproved promotion-name/type/value columns.
- Master FKs remain for traceability, but snapshots are the authoritative historical display values.

## 16. Historical-data retention policy

- Reservations, orders, order_items, payments, order_promotions, inventory_transactions, and order_item_toppings are not hard deleted in normal operation.
- Cancellation uses reservation/order/order-item status. Completed orders are immutable for v1 sales operations.
- Payment attempts remain auditable; refunds are represented by payment status/process, not row deletion.
- Every stock change has a matching inventory transaction. Existing ledger rows are immutable historical facts.
- Recipe edits never recalculate past inventory transactions.
- Soft-deleted masters remain referenced. FK RESTRICT prevents a physical delete from erasing or orphaning history.
- Promotion used_count increments exactly once only after a successful paid payment.

## 17. Database rules versus application rules

Database rules: 18 PKs; 23 FKs; explicit nullability; RESTRICT/NO ACTION actions; types and lengths; ENUM value sets; defaults; 12 unique constraints; indexes; DECIMAL precision; approved row-level CHECK constraints; timestamps and soft-delete columns.

Application rules and future implementation ownership:

| Rule | Why application-level | Intended layer / later task |
| --- | --- | --- |
| Reservation table assignment, capacity, and time overlap | Cross-row/time-range concurrency | ReservationService; Reservation backend task |
| Valid reservation/order/item/payment transitions | Depends on current state and actor | Domain services; relevant backend tasks |
| One active dine-in order per table | Conditional uniqueness/concurrency | OrderService transaction |
| Product/variant availability and pair consistency | Multiple master records | OrderService |
| Snapshot capture and amount calculation | Values are copied at operation time | OrderService/PromotionService |
| Promotion eligibility, cap, usage limit, one-time increment | Time, counters, order values, locking | PromotionService + PaymentService |
| At most one paid payment and no partial/split payment | Conditional uniqueness; MySQL portability | PaymentService transaction/locking |
| Payment requires all non-cancelled items served | Cross-row aggregate | PaymentService |
| Stock deduction at pending to preparing | Recipe aggregation and locking | InventoryService transaction |
| No negative stock; consume audit actor/reference | Cross-row atomic workflow | InventoryService |
| reference_type/reference_id validity | Polymorphic reference has no physical FK | InventoryService |

No business logic is implemented by CAFE-DB-001B.

## 18. Dependency graph

Parent-to-child graph:

    users -> reservations, orders, payments, inventory_transactions
    customers -> reservations, orders
    areas -> cafe_tables
    categories -> products
    toppings -> order_item_toppings
    ingredients -> product_recipes, inventory_transactions
    promotions -> order_promotions
    cafe_tables -> reservations, orders
    products -> product_variants, order_items
    product_variants -> product_recipes, order_items
    reservations -> orders
    orders -> order_items, payments, order_promotions
    order_items -> order_item_toppings

All 18 nodes and all 23 FK edges are present. There is no cycle.

Topological levels:

- Level 0: users, customers, areas, categories, toppings, ingredients, promotions.
- Level 1: cafe_tables, products.
- Level 2: product_variants, reservations.
- Level 3: product_recipes, orders.
- Level 4: order_items, payments, order_promotions, inventory_transactions.
- Level 5: order_item_toppings.

## 19. Canonical migration order

The only project canonical order is the numbered list in section 4:

users → customers → areas → categories → toppings → ingredients → promotions → cafe_tables → products → product_variants → reservations → product_recipes → orders → order_items → payments → order_promotions → inventory_transactions → order_item_toppings.

Every parent precedes every child. Task grouping may differ, but filenames/timestamps must preserve this order. Existing DB-003 files put promotions after cafe_tables and products, so their current timestamps do not match the canonical order even though the partial graph remains executable.

## 20. Resolved decision log

| Issue | Previous/candidate values | Frozen decision | Evidence |
| --- | --- | --- | --- |
| Business-table count | Partial/grouped inventories | Exactly 18, section 4 order | SRS DB-01/19 |
| Customer loyalty fields | Prompt mentioned points/total_spent | Both absent | SRS table 21, section 7.3, NFR 9.4 |
| Reservation customer_id | nullable vs required | Nullable | SRS table 24; customer optional |
| Reservation table_id | nullable vs required | Nullable; required before confirmed | DB-03/BR20 |
| Reservation created_by | nullable vs required | Nullable for public request | DB-03/BR20 |
| Reservation time/guest fields | reservation_time/number_of_guests candidates | reservation_start_at, reservation_end_at, guest_count | SRS table 24/DB-04 |
| Reservation deletion | candidate soft delete | No soft delete; historical status | DB-15/BR27 |
| Table reserved status | included vs removed | Removed; derive reservations by time | DB-05/BR03 |
| Variant fields | optional SKU/display order | sku VARCHAR(50) nullable unique; no display_order | SRS table 27 |
| Variant price/status/delete | unresolved | DECIMAL(12,2); available/unavailable; soft delete | Table 27/DB-15 |
| Order creator FK | created_by vs user_id | user_id NOT NULL | SRS table 29 |
| Order service types | dine-in only/TBD vs takeaway | dine_in and takeaway | SRS table 29, UC-06 |
| Order status | preparation states candidate | pending/completed/cancelled only | DB-06/BR31 |
| Order item variant FK | product_variant_id vs variant_id | product_id and variant_id | SRS table 30 |
| Order item snapshots | incomplete candidates | Exact table 30 fields including topping_amount | DB-14/table 30 |
| Topping snapshot amount | subtotal candidate | price and quantity; no row subtotal | SRS table 31 |
| Payment cardinality | 1:1 vs 1:N | 1:N attempts, maximum one paid by app | DB-13/BR16 |
| Promotion cardinality | multiple vs one | Maximum one/order; UQ order_id | DB-12/17/BR15 |
| Promotion snapshots | name/type/value candidates | promotion_code and discount_amount only | SRS table 34/DB-14 |
| Inventory order link | order_item_id FK candidate | reference_type/reference_id, no FK | SRS table 37/UC-12 |
| Inventory creator | nullable/system actor vs required | created_by NOT NULL user actor | DB-11/BR35 |
| Inventory types | candidates | import, consume, adjustment, waste | Table 37/BR24 |
| Stock deduction | add item vs preparation | pending to preparing transaction | DB-09/BR22 |
| Soft delete | candidates/TBD | Ten masters yes; remaining eight no | DB-15/BR26/27 |
| FK actions | CASCADE/SET NULL/defaults | ON DELETE RESTRICT, ON UPDATE NO ACTION for 23 FKs | DB-16; immutable IDs |
| Money/quantity precision | unspecified | Exact section 9 matrix | SRS tables 28–37/conventions |
| Canonical order | promotions 9; recipes after orders | promotions 7; recipes 12 before orders 13 | DB-19 |
| CHECK strategy | deferred in earlier reports | Approved row-local checks in section 14 | SRS BR08/14/21/22/25 and MySQL target |

No equal-authority conflict remains. No schema-affecting decision is pending.

## 21. Existing migration mismatch register

Read-only inspection on 2026-08-16 found:

1. DB-003 timestamps create cafe_tables and products before promotions; Frozen order requires promotions first.
2. Existing cafe_tables.area_id and products.category_id rely on implicit FK actions instead of explicitly declaring RESTRICT/NO ACTION.
3. Existing DB-002/DB-003 migrations omit the applicable frozen row-level CHECK constraints.
4. The nine business tables currently represented by migrations otherwise match the SRS column sets, ENUMs, defaults, unique constraints, precision, timestamps, and soft-delete policy.
5. Migrations for tables 10–18 do not yet exist; this is expected because CAFE-DB-004 and later tasks remain unimplemented.

These findings require a separate CAFE-DB-CORRECTION-001 — Align Existing Migrations with Frozen Database Design, followed by re-audit. Nothing in this task changes migrations or database state.

## 22. Frozen-schema declaration

DATABASE DESIGN: FROZEN

| Gate | Result |
| --- | --- |
| Approved/actual documented business tables | 18 / 18 |
| Extra unapproved tables | 0 |
| Missing definitions | 0 |
| Undefined PK/FK/FK nullable/FK actions | 0 |
| Undefined DECIMAL/status/soft-delete/unique/index decisions | 0 |
| Snapshot/history conflicts | 0 |
| Dependency cycles | 0 |
| Canonical-order conflicts within this design | 0 |
| Cross-document schema conflicts after alignment | 0 |
| Schema-affecting TBD/candidate decisions | 0 |

The declaration freezes the target design, not the current migration implementation. Existing migration mismatches remain correction/re-audit work and do not authorize CAFE-DB-004 yet.

## 23. Task boundary and readiness

- CAFE-DB-001B documentation can pass when cross-document static validation and diff review pass.
- Re-audit may begin after engineer review of this frozen design.
- CAFE-DB-004 must remain blocked until CAFE-DB-001, CAFE-DB-002, and CAFE-DB-003 re-audits pass and CAFE-DB-CORRECTION-001 either passes or is confirmed unnecessary.
- This document does not create/modify migrations, run database commands, create/modify models, create seeders/factories, install dependencies, implement business logic, commit, push, or start the next task.