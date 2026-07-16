SELECT SETVAL('ezcontentclass_attribute_id_seq', COALESCE(MAX(id), 1) ) FROM ezcontentclass_attribute;
