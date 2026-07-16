SELECT SETVAL('ibexa_policy_limitation_value_id_seq', COALESCE(MAX(id), 1) ) FROM ibexa_policy_limitation_value;
