SELECT SETVAL('ezrole_id_seq', COALESCE(MAX(id), 1) ) FROM ezrole;
