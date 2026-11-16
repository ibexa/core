CREATE TEMPORARY TABLE ibexa_content_language_renumbering (old_id BIGINT NOT NULL, new_id BIGINT NOT NULL, PRIMARY KEY (old_id));
-- ibexa:sql-statement-separator
INSERT INTO ibexa_content_language_renumbering (old_id, new_id) SELECT id, 1 + ROW_NUMBER() OVER (ORDER BY id) FROM ibexa_content_language;
-- ibexa:sql-statement-separator
DELETE FROM ibexa_content_language_renumbering WHERE old_id = new_id;
-- ibexa:sql-statement-separator
UPDATE ibexa_content_language SET id = COALESCE(-(SELECT r.new_id FROM ibexa_content_language_renumbering r WHERE r.old_id = ibexa_content_language.id), id);
-- ibexa:sql-statement-separator
UPDATE ibexa_content SET initial_language_id = COALESCE(-(SELECT r.new_id FROM ibexa_content_language_renumbering r WHERE r.old_id = ibexa_content.initial_language_id), initial_language_id);
-- ibexa:sql-statement-separator
UPDATE ibexa_content_version SET initial_language_id = COALESCE(-(SELECT r.new_id FROM ibexa_content_language_renumbering r WHERE r.old_id = ibexa_content_version.initial_language_id), initial_language_id);
-- ibexa:sql-statement-separator
UPDATE ibexa_content_type SET initial_language_id = COALESCE(-(SELECT r.new_id FROM ibexa_content_language_renumbering r WHERE r.old_id = ibexa_content_type.initial_language_id), initial_language_id);
-- ibexa:sql-statement-separator
UPDATE ibexa_content_type_name SET language_id = COALESCE(-(SELECT r.new_id FROM ibexa_content_language_renumbering r WHERE r.old_id = ibexa_content_type_name.language_id), language_id);
-- ibexa:sql-statement-separator
UPDATE ibexa_content_type_field_definition_ml SET language_id = COALESCE(-(SELECT r.new_id FROM ibexa_content_language_renumbering r WHERE r.old_id = ibexa_content_type_field_definition_ml.language_id), language_id);
-- ibexa:sql-statement-separator
UPDATE ibexa_content_field SET language_id = COALESCE(-(SELECT r.new_id FROM ibexa_content_language_renumbering r WHERE r.old_id = ibexa_content_field.language_id), language_id);
-- ibexa:sql-statement-separator
UPDATE ibexa_content_name SET language_id = COALESCE(-(SELECT r.new_id FROM ibexa_content_language_renumbering r WHERE r.old_id = ibexa_content_name.language_id), language_id);
-- ibexa:sql-statement-separator
UPDATE ibexa_content_translation SET language_id = COALESCE(-(SELECT r.new_id FROM ibexa_content_language_renumbering r WHERE r.old_id = ibexa_content_translation.language_id), language_id);
-- ibexa:sql-statement-separator
UPDATE ibexa_content_version_translation SET language_id = COALESCE(-(SELECT r.new_id FROM ibexa_content_language_renumbering r WHERE r.old_id = ibexa_content_version_translation.language_id), language_id);
-- ibexa:sql-statement-separator
UPDATE ibexa_url_alias_ml_translation SET language_id = COALESCE(-(SELECT r.new_id FROM ibexa_content_language_renumbering r WHERE r.old_id = ibexa_url_alias_ml_translation.language_id), language_id);
-- ibexa:sql-statement-separator
UPDATE ibexa_object_state SET default_language_id = COALESCE(-(SELECT r.new_id FROM ibexa_content_language_renumbering r WHERE r.old_id = ibexa_object_state.default_language_id), default_language_id);
-- ibexa:sql-statement-separator
UPDATE ibexa_object_state_group SET default_language_id = COALESCE(-(SELECT r.new_id FROM ibexa_content_language_renumbering r WHERE r.old_id = ibexa_object_state_group.default_language_id), default_language_id);
-- ibexa:sql-statement-separator
UPDATE ibexa_object_state_language SET language_id = COALESCE(-(SELECT r.new_id FROM ibexa_content_language_renumbering r WHERE r.old_id = ibexa_object_state_language.language_id), language_id);
-- ibexa:sql-statement-separator
UPDATE ibexa_object_state_group_language SET language_id = COALESCE(-(SELECT r.new_id FROM ibexa_content_language_renumbering r WHERE r.old_id = ibexa_object_state_group_language.language_id), language_id);
-- ibexa:sql-statement-separator
UPDATE ibexa_object_state_group_language SET real_language_id = COALESCE(-(SELECT r.new_id FROM ibexa_content_language_renumbering r WHERE r.old_id = ibexa_object_state_group_language.real_language_id), real_language_id);
-- ibexa:sql-statement-separator
UPDATE ibexa_search_object_word_link SET language_id = COALESCE(-(SELECT r.new_id FROM ibexa_content_language_renumbering r WHERE r.old_id = ibexa_search_object_word_link.language_id), language_id);
-- ibexa:sql-statement-separator
UPDATE ibexa_content_language SET id = -id WHERE id < 0;
-- ibexa:sql-statement-separator
UPDATE ibexa_content SET initial_language_id = -initial_language_id WHERE initial_language_id < 0;
-- ibexa:sql-statement-separator
UPDATE ibexa_content_version SET initial_language_id = -initial_language_id WHERE initial_language_id < 0;
-- ibexa:sql-statement-separator
UPDATE ibexa_content_type SET initial_language_id = -initial_language_id WHERE initial_language_id < 0;
-- ibexa:sql-statement-separator
UPDATE ibexa_content_type_name SET language_id = -language_id WHERE language_id < 0;
-- ibexa:sql-statement-separator
UPDATE ibexa_content_type_field_definition_ml SET language_id = -language_id WHERE language_id < 0;
-- ibexa:sql-statement-separator
UPDATE ibexa_content_field SET language_id = -language_id WHERE language_id < 0;
-- ibexa:sql-statement-separator
UPDATE ibexa_content_name SET language_id = -language_id WHERE language_id < 0;
-- ibexa:sql-statement-separator
UPDATE ibexa_content_translation SET language_id = -language_id WHERE language_id < 0;
-- ibexa:sql-statement-separator
UPDATE ibexa_content_version_translation SET language_id = -language_id WHERE language_id < 0;
-- ibexa:sql-statement-separator
UPDATE ibexa_url_alias_ml_translation SET language_id = -language_id WHERE language_id < 0;
-- ibexa:sql-statement-separator
UPDATE ibexa_object_state SET default_language_id = -default_language_id WHERE default_language_id < 0;
-- ibexa:sql-statement-separator
UPDATE ibexa_object_state_group SET default_language_id = -default_language_id WHERE default_language_id < 0;
-- ibexa:sql-statement-separator
UPDATE ibexa_object_state_language SET language_id = -language_id WHERE language_id < 0;
-- ibexa:sql-statement-separator
UPDATE ibexa_object_state_group_language SET language_id = -language_id WHERE language_id < 0;
-- ibexa:sql-statement-separator
UPDATE ibexa_object_state_group_language SET real_language_id = -real_language_id WHERE real_language_id < 0;
-- ibexa:sql-statement-separator
UPDATE ibexa_search_object_word_link SET language_id = -language_id WHERE language_id < 0;
-- ibexa:sql-statement-separator
DROP TABLE ibexa_content_language_renumbering;
