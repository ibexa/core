SELECT SETVAL('ibexa_role_id_seq', COALESCE(MAX(id), 1) ) FROM ibexa_role;
