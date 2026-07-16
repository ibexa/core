SELECT SETVAL('ibexa_url_id_seq', COALESCE(MAX(id), 1) ) FROM ibexa_url;
