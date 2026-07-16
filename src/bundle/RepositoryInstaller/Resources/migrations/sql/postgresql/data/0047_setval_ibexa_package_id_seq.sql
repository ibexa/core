SELECT SETVAL('ibexa_package_id_seq', COALESCE(MAX(id), 1) ) FROM ibexa_package;
