SELECT SETVAL('ezsection_id_seq', COALESCE(MAX(id), 1) ) FROM ezsection;
