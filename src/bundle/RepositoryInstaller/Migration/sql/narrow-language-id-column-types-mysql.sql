ALTER TABLE ibexa_language MODIFY COLUMN id INT NOT NULL DEFAULT 0;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_translation MODIFY COLUMN language_id INT NOT NULL;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_version_translation MODIFY COLUMN language_id INT NOT NULL;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_url_alias_ml_translation MODIFY COLUMN language_id INT NOT NULL;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_type_field_definition_ml MODIFY COLUMN language_id INT NOT NULL;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content MODIFY COLUMN initial_language_id INT NOT NULL DEFAULT 0;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_version MODIFY COLUMN initial_language_id INT NOT NULL DEFAULT 0;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_type MODIFY COLUMN initial_language_id INT NOT NULL DEFAULT 0;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_object_state MODIFY COLUMN default_language_id INT NOT NULL DEFAULT 0;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_object_state_group MODIFY COLUMN default_language_id INT NOT NULL DEFAULT 0;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_object_state_language MODIFY COLUMN language_id INT NOT NULL DEFAULT 0;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_object_state_group_language MODIFY COLUMN language_id INT NOT NULL DEFAULT 0;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_object_state_group_language MODIFY COLUMN real_language_id INT NOT NULL DEFAULT 0;
