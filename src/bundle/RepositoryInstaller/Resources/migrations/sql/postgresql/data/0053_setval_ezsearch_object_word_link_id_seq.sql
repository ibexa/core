SELECT SETVAL('ezsearch_object_word_link_id_seq', COALESCE(MAX(id), 1) ) FROM ezsearch_object_word_link;
