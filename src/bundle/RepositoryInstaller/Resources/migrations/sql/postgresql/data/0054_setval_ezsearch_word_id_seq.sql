SELECT SETVAL('ezsearch_word_id_seq', COALESCE(MAX(id), 1) ) FROM ezsearch_word;
