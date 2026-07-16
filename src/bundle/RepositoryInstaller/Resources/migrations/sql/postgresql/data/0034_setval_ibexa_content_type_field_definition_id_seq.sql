SELECT SETVAL('ibexa_content_type_field_definition_id_seq', COALESCE(MAX(id), 1) ) FROM ibexa_content_type_field_definition;
