CREATE TEMPORARY TABLE ibexa_content_language_renumbering (old_id BIGINT NOT NULL, new_id BIGINT NOT NULL, PRIMARY KEY (old_id));
-- ibexa:sql-statement-separator
INSERT INTO ibexa_content_language_renumbering (old_id, new_id) SELECT id, 1 + ROW_NUMBER() OVER (ORDER BY id) FROM ibexa_content_language;
-- ibexa:sql-statement-separator
-- InnoDB can't cascade into real_language_id, part of the primary key, while language_id in the same row still references the renumbered language.
-- So these rows are set aside, given the new ids here, and inserted back once the languages are renumbered.
CREATE TEMPORARY TABLE ibexa_object_state_group_language_renumbering AS SELECT contentobject_state_group_id, real_language_id, language_id, name, description FROM ibexa_object_state_group_language;
-- ibexa:sql-statement-separator
DELETE FROM ibexa_object_state_group_language;
-- ibexa:sql-statement-separator
UPDATE ibexa_object_state_group_language_renumbering SET language_id = COALESCE((SELECT r.new_id FROM ibexa_content_language_renumbering r WHERE r.old_id = ibexa_object_state_group_language_renumbering.language_id), language_id);
-- ibexa:sql-statement-separator
UPDATE ibexa_object_state_group_language_renumbering SET real_language_id = COALESCE((SELECT r.new_id FROM ibexa_content_language_renumbering r WHERE r.old_id = ibexa_object_state_group_language_renumbering.real_language_id), real_language_id);
