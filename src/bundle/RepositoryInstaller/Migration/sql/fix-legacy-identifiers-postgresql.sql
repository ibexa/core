UPDATE ibexa_object_state_group SET identifier = 'ibexa_lock' WHERE identifier = 'ez_lock';
-- ibexa:sql-statement-separator
UPDATE ibexa_content_type_field_definition SET data_type_string = 'ibexa_string' WHERE data_type_string = 'ezstring';
-- ibexa:sql-statement-separator
UPDATE ibexa_content_field SET data_type_string = 'ibexa_string' WHERE data_type_string = 'ezstring';
