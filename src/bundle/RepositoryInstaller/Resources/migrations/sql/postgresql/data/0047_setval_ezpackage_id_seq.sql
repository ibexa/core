SELECT SETVAL('ezpackage_id_seq', COALESCE(MAX(id), 1) ) FROM ezpackage;
