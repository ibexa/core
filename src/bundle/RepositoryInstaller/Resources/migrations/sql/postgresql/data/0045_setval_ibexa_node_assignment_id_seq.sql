SELECT SETVAL('ibexa_node_assignment_id_seq', COALESCE(MAX(id), 1) ) FROM ibexa_node_assignment;
