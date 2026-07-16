SELECT SETVAL('ibexa_image_file_id_seq', COALESCE(MAX(id), 1) ) FROM ibexa_image_file;
