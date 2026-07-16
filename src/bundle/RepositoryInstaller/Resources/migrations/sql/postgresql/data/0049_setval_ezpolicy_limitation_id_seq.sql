SELECT SETVAL('ezpolicy_limitation_id_seq', COALESCE(MAX(id), 1) ) FROM ezpolicy_limitation;
