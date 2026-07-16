SELECT SETVAL('ezcontentobject_link_id_seq', COALESCE(MAX(id), 1) ) FROM ezcontentobject_link;
