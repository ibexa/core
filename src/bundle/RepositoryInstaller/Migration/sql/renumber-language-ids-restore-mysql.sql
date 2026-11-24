INSERT INTO ibexa_object_state_group_language (contentobject_state_group_id, real_language_id, language_id, name, description) SELECT contentobject_state_group_id, real_language_id, language_id, name, description FROM ibexa_object_state_group_language_renumbering;
-- ibexa:sql-statement-separator
DROP TEMPORARY TABLE ibexa_object_state_group_language_renumbering;
-- ibexa:sql-statement-separator
DROP TEMPORARY TABLE ibexa_content_language_renumbering;
