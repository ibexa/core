SELECT SETVAL('ibexa_section_id_seq', COALESCE(MAX(id), 1) ) FROM ibexa_section;
