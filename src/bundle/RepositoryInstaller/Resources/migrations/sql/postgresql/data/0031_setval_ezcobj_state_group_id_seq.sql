-- Set proper sequence values after inserting data
SELECT SETVAL('ezcobj_state_group_id_seq', COALESCE(MAX(id), 1) ) FROM ezcobj_state_group;
