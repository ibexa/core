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
