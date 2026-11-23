DELETE FROM ezpolicy_limitation
WHERE id IN (
    SELECT l.id
    FROM ezpolicy_limitation l
    INNER JOIN ezpolicy p ON p.id = l.policy_id
    WHERE p.function_name = 'assign'
      AND p.module_name = 'state'
      AND l.identifier IN ('Node', 'Subtree')
);
-- ibexa:sql-statement-separator
DELETE FROM ezpolicy_limitation_value
WHERE id IN (
    SELECT lv.id
    FROM ezpolicy_limitation_value lv
    LEFT JOIN ezpolicy_limitation l ON l.id = lv.limitation_id
    WHERE l.id IS NULL
);
