SELECT SETVAL('ibexa_url_alias_ml_incr_id_seq', COALESCE(MAX(id), 1) ) FROM ibexa_url_alias_ml_incr;
