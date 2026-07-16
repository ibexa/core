SELECT SETVAL('ibexa_content_id_seq', COALESCE(MAX(id), 1) ) FROM ibexa_content;
