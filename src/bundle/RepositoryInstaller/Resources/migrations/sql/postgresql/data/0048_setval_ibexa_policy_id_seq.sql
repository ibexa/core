SELECT SETVAL('ibexa_policy_id_seq', COALESCE(MAX(id), 1) ) FROM ibexa_policy;
