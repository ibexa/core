-- Set proper sequence values after inserting data
SELECT SETVAL('ibexa_object_state_group_id_seq', COALESCE(MAX(id), 1) ) FROM ibexa_object_state_group;
