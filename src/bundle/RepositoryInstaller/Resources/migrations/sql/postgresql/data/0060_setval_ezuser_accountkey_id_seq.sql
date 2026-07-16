SELECT SETVAL('ezuser_accountkey_id_seq', COALESCE(MAX(id), 1) ) FROM ezuser_accountkey;
