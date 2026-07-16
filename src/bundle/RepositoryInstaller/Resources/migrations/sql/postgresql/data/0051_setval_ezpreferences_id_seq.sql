SELECT SETVAL('ezpreferences_id_seq', COALESCE(MAX(id), 1) ) FROM ezpreferences;
