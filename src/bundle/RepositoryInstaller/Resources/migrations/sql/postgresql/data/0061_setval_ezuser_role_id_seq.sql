SELECT SETVAL('ezuser_role_id_seq', COALESCE(MAX(id), 1) ) FROM ezuser_role;
