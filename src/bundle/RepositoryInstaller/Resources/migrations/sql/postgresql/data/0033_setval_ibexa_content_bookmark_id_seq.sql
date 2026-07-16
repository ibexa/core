SELECT SETVAL('ibexa_content_bookmark_id_seq', COALESCE(MAX(id), 1) ) FROM ibexa_content_bookmark;
