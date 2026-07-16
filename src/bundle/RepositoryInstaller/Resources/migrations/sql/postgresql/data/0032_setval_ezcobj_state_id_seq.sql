SELECT SETVAL('ezcobj_state_id_seq', COALESCE(MAX(id), 1) ) FROM ezcobj_state;
