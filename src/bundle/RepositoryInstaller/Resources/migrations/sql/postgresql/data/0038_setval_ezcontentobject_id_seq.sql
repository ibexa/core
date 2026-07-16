SELECT SETVAL('ezcontentobject_id_seq', COALESCE(MAX(id), 1) ) FROM ezcontentobject;
