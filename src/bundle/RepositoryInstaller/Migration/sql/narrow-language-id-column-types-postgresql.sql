ALTER TABLE ibexa_content_translation DROP CONSTRAINT ibexa_content_translation_language_fk;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_version_translation DROP CONSTRAINT ibexa_content_version_translation_language_fk;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_url_alias_ml_translation DROP CONSTRAINT ibexa_url_alias_ml_translation_language_fk;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_type_field_definition_ml DROP CONSTRAINT ibexa_content_type_field_definition_ml_lang_fk;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_language ALTER COLUMN id TYPE INTEGER USING id::INTEGER;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_translation ALTER COLUMN language_id TYPE INTEGER USING language_id::INTEGER;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_version_translation ALTER COLUMN language_id TYPE INTEGER USING language_id::INTEGER;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_url_alias_ml_translation ALTER COLUMN language_id TYPE INTEGER USING language_id::INTEGER;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_type_field_definition_ml ALTER COLUMN language_id TYPE INTEGER USING language_id::INTEGER;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content ALTER COLUMN initial_language_id TYPE INTEGER USING initial_language_id::INTEGER;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_version ALTER COLUMN initial_language_id TYPE INTEGER USING initial_language_id::INTEGER;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_type ALTER COLUMN initial_language_id TYPE INTEGER USING initial_language_id::INTEGER;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_object_state ALTER COLUMN default_language_id TYPE INTEGER USING default_language_id::INTEGER;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_object_state_group ALTER COLUMN default_language_id TYPE INTEGER USING default_language_id::INTEGER;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_object_state_language ALTER COLUMN language_id TYPE INTEGER USING language_id::INTEGER;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_object_state_group_language ALTER COLUMN language_id TYPE INTEGER USING language_id::INTEGER;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_object_state_group_language ALTER COLUMN real_language_id TYPE INTEGER USING real_language_id::INTEGER;
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
