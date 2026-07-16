SELECT SETVAL('eznode_assignment_id_seq', COALESCE(MAX(id), 1) ) FROM eznode_assignment;
