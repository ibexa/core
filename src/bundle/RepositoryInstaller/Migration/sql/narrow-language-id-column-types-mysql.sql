ALTER TABLE ibexa_content_translation DROP FOREIGN KEY ibexa_content_translation_language_fk;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_version_translation DROP FOREIGN KEY ibexa_content_version_translation_language_fk;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_url_alias_ml_translation DROP FOREIGN KEY ibexa_url_alias_ml_translation_language_fk;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_type_field_definition_ml DROP FOREIGN KEY ibexa_content_type_field_definition_ml_lang_fk;
-- ibexa:sql-statement-separator
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
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_translation
    ADD CONSTRAINT ibexa_content_translation_language_fk
    FOREIGN KEY (language_id) REFERENCES ibexa_language (id)
    ON DELETE RESTRICT ON UPDATE CASCADE;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_version_translation
    ADD CONSTRAINT ibexa_content_version_translation_language_fk
    FOREIGN KEY (language_id) REFERENCES ibexa_language (id)
    ON DELETE RESTRICT ON UPDATE CASCADE;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_url_alias_ml_translation
    ADD CONSTRAINT ibexa_url_alias_ml_translation_language_fk
    FOREIGN KEY (language_id) REFERENCES ibexa_language (id)
    ON DELETE RESTRICT ON UPDATE CASCADE;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_type_field_definition_ml
    ADD CONSTRAINT ibexa_content_type_field_definition_ml_lang_fk
    FOREIGN KEY (language_id) REFERENCES ibexa_language (id)
    ON DELETE CASCADE ON UPDATE CASCADE;
