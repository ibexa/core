SELECT SETVAL('ibexa_content_tree_node_id_seq', COALESCE(MAX(node_id), 1) ) FROM ibexa_content_tree;
