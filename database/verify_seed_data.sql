-- After importing seed_data.sql, every invalid_count must be 0.
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
