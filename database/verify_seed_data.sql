-- After importing seed_data.sql, every invalid_count must be 0.
-- V2 requires the online pickup migrations. These checks include presence, not only error counts.
SELECT 'v2_fixture_presence' AS check_name,
    ABS(6 - (SELECT COUNT(*) FROM online_order_requests WHERE request_code IN ('DEMO-V2-ONLINE-PENDING','DEMO-V2-SCHEDULED-PENDING','DEMO-V2-ONLINE-ACCEPTED','DEMO-V2-SCHEDULED-PAID','DEMO-V2-REJECTED','DEMO-V2-CANCELLED')))
    + ABS(2 - (SELECT COUNT(*) FROM orders WHERE order_code IN ('DEMO-V2-ONLINE-ORDER','DEMO-V2-SCHEDULED-ORDER')))
    + ABS(7 - (SELECT COUNT(*) FROM online_order_request_items i JOIN online_order_requests r ON r.id=i.request_id WHERE r.request_code LIKE 'DEMO-V2-%'))
    + ABS(1 - (SELECT COUNT(*) FROM online_order_request_item_toppings t JOIN online_order_request_items i ON i.id=t.request_item_id JOIN online_order_requests r ON r.id=i.request_id WHERE r.request_code LIKE 'DEMO-V2-%'))
    + ABS(1 - (SELECT COUNT(*) FROM payments WHERE payment_code='DEMO-V2-PREPAID')) AS invalid_count;

SELECT 'v2_request_order_link' AS check_name, COUNT(*) AS invalid_count
FROM online_order_requests r LEFT JOIN orders o ON o.online_request_id=r.id
WHERE r.request_code LIKE 'DEMO-V2-%' AND (
    (r.status='accepted' AND (o.id IS NULL OR o.purchase_type<>r.purchase_type OR o.order_type<>'takeaway' OR o.table_id IS NOT NULL OR o.user_id<>r.accepted_by))
    OR (r.status IN ('pending','rejected') AND o.id IS NOT NULL));

SELECT 'v2_order_snapshots_and_totals' AS check_name, COUNT(*) AS invalid_count
FROM orders o WHERE o.order_code LIKE 'DEMO-V2-%' AND (
    o.total_amount<>o.subtotal-o.discount_amount+o.tax_amount
    OR o.subtotal<>COALESCE((SELECT SUM(i.subtotal) FROM order_items i WHERE i.order_id=o.id AND i.status<>'cancelled'),0)
    OR EXISTS(SELECT 1 FROM order_items i JOIN product_variants v ON v.id=i.variant_id WHERE i.order_id=o.id AND (i.product_id<>v.product_id OR i.subtotal<>i.quantity*i.unit_price+i.topping_amount OR i.topping_amount<>COALESCE((SELECT SUM(t.price*t.quantity) FROM order_item_toppings t WHERE t.order_item_id=i.id),0)))
    OR o.subtotal<>COALESCE((SELECT SUM(i.quantity*i.unit_price + COALESCE((SELECT SUM(t.price*t.quantity) FROM online_order_request_item_toppings t WHERE t.request_item_id=i.id),0)) FROM online_order_request_items i WHERE i.request_id=o.online_request_id),0));

SELECT 'v2_payment_state' AS check_name, COUNT(*) AS invalid_count
FROM orders o WHERE o.order_code LIKE 'DEMO-V2-%' AND (
    (SELECT COUNT(*) FROM payments p WHERE p.order_id=o.id AND p.status='paid')>1
    OR EXISTS(SELECT 1 FROM payments p WHERE p.order_id=o.id AND p.status='paid' AND (p.amount<>o.total_amount OR p.paid_at IS NULL))
    OR (o.status='completed' AND (NOT EXISTS(SELECT 1 FROM payments p WHERE p.order_id=o.id AND p.status='paid') OR EXISTS(SELECT 1 FROM order_items i WHERE i.order_id=o.id AND i.status NOT IN ('served','cancelled')))));

-- Run immediately after import: the prepaid scheduled fixture must still await preparation.
SELECT 'v2_initial_prepaid_scenario' AS check_name, IF(
    (SELECT COUNT(*) FROM orders o JOIN payments p ON p.order_id=o.id WHERE o.order_code='DEMO-V2-SCHEDULED-ORDER' AND o.status='pending' AND p.payment_code='DEMO-V2-PREPAID' AND p.status='paid' AND p.payment_method='bank_transfer')=1,0,1) AS invalid_count;
SELECT 'order_totals' AS check_name, COUNT(*) AS invalid_count
FROM orders o WHERE o.order_code LIKE 'DEMO-V1-%'
AND (o.total_amount <> o.subtotal - o.discount_amount + o.tax_amount
OR o.subtotal <> COALESCE((SELECT SUM(i.subtotal) FROM order_items i WHERE i.order_id = o.id AND i.status <> 'cancelled'), 0));

SELECT 'item_snapshots_and_toppings' AS check_name, COUNT(*) AS invalid_count
FROM order_items i JOIN orders o ON o.id = i.order_id JOIN product_variants v ON v.id = i.variant_id
WHERE o.order_code LIKE 'DEMO-V1-%'
AND (v.product_id <> i.product_id OR i.subtotal <> i.quantity * i.unit_price + i.topping_amount
OR i.topping_amount <> COALESCE((SELECT SUM(t.price * t.quantity) FROM order_item_toppings t WHERE t.order_item_id = i.id), 0));

SELECT 'paid_payments' AS check_name, COUNT(*) AS invalid_count
FROM orders o WHERE o.order_code LIKE 'DEMO-V1-%'
AND ((SELECT COUNT(*) FROM payments p WHERE p.order_id = o.id AND p.status = 'paid') > 1
OR EXISTS (SELECT 1 FROM payments p WHERE p.order_id = o.id AND p.status = 'paid' AND (p.amount <> o.total_amount OR p.paid_at IS NULL))
OR (o.status = 'completed' AND NOT EXISTS (SELECT 1 FROM payments p WHERE p.order_id = o.id AND p.status = 'paid')));

SELECT 'stock_ledger_chain' AS check_name, COUNT(*) AS invalid_count FROM (
    SELECT ingredient_id, type, quantity, before_quantity, after_quantity,
           LAG(after_quantity, 1, 0) OVER (PARTITION BY ingredient_id ORDER BY id) AS previous_stock
    FROM inventory_transactions WHERE note LIKE 'DEMO-V1 %'
) m WHERE m.before_quantity <> m.previous_stock OR m.quantity <= 0 OR m.after_quantity < 0
OR (m.type = 'import' AND m.after_quantity <> m.before_quantity + m.quantity)
OR (m.type IN ('consume', 'waste') AND m.after_quantity <> m.before_quantity - m.quantity)
OR (m.type = 'adjustment' AND ABS(m.after_quantity - m.before_quantity) <> m.quantity);

SELECT 'current_stock_matches_last_log' AS check_name, COUNT(*) AS invalid_count
FROM ingredients g WHERE g.name LIKE '[DEMO] %' AND g.current_stock <>
COALESCE((SELECT m.after_quantity FROM inventory_transactions m WHERE m.ingredient_id = g.id ORDER BY m.id DESC LIMIT 1), 0);

SELECT 'consume_matches_recipe' AS check_name, COUNT(*) AS invalid_count
FROM inventory_transactions m
LEFT JOIN order_items i ON m.reference_type = 'order_item' AND i.id = m.reference_id
LEFT JOIN product_recipes r ON r.variant_id = i.variant_id AND r.ingredient_id = m.ingredient_id
WHERE m.note LIKE 'DEMO-V1 %' AND m.type = 'consume'
AND (i.id IS NULL OR r.id IS NULL OR m.quantity <> r.quantity * i.quantity OR i.status NOT IN ('preparing', 'ready', 'served'));

SELECT 'promotion_usage' AS check_name, COUNT(*) AS invalid_count
FROM promotions p WHERE p.code LIKE 'DEMO-V1-%' AND p.used_count <>
(SELECT COUNT(DISTINCT op.order_id) FROM order_promotions op JOIN payments pay ON pay.order_id = op.order_id
 WHERE op.promotion_id = p.id AND pay.status IN ('paid', 'refunded') AND pay.paid_at IS NOT NULL);
