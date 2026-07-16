SELECT SETVAL('ezcontentobject_version_id_seq', COALESCE(MAX(id), 1) ) FROM ezcontentobject_version;
