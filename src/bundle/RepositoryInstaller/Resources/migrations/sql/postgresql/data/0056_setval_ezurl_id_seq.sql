SELECT SETVAL('ezurl_id_seq', COALESCE(MAX(id), 1) ) FROM ezurl;
