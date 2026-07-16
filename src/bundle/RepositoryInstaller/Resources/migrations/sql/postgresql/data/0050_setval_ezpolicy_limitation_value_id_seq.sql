SELECT SETVAL('ezpolicy_limitation_value_id_seq', COALESCE(MAX(id), 1) ) FROM ezpolicy_limitation_value;
