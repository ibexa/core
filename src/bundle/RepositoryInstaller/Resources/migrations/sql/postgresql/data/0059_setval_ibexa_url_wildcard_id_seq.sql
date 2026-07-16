SELECT SETVAL('ibexa_url_wildcard_id_seq', COALESCE(MAX(id), 1) ) FROM ibexa_url_wildcard;
