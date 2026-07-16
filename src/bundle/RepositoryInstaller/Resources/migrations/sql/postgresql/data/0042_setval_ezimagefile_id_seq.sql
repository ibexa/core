SELECT SETVAL('ezimagefile_id_seq', COALESCE(MAX(id), 1) ) FROM ezimagefile;
