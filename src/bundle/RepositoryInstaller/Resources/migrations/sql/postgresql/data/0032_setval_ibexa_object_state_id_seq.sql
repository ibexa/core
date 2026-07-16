SELECT SETVAL('ibexa_object_state_id_seq', COALESCE(MAX(id), 1) ) FROM ibexa_object_state;
