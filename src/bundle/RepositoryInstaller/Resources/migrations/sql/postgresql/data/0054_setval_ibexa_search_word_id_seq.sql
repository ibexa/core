SELECT SETVAL('ibexa_search_word_id_seq', COALESCE(MAX(id), 1) ) FROM ibexa_search_word;
