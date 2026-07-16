SELECT SETVAL('ezpolicy_id_seq', COALESCE(MAX(id), 1) ) FROM ezpolicy;
