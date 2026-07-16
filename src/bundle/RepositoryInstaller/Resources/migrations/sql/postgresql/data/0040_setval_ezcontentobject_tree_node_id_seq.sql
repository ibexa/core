SELECT SETVAL('ezcontentobject_tree_node_id_seq', COALESCE(MAX(node_id), 1) ) FROM ezcontentobject_tree;
