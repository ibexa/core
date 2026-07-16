SELECT SETVAL('ezcontentclass_id_seq', COALESCE(MAX(id), 1) ) FROM ezcontentclass;
