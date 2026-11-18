CREATE TEMPORARY TABLE ibexa_content_language_renumbering (old_id BIGINT NOT NULL, new_id BIGINT NOT NULL, PRIMARY KEY (old_id));
-- ibexa:sql-statement-separator
INSERT INTO ibexa_content_language_renumbering (old_id, new_id) SELECT id, 1 + ROW_NUMBER() OVER (ORDER BY id) FROM ibexa_content_language;
-- ibexa:sql-statement-separator
-- Languages keeping their id are left out of the mapping, so their rows aren't rewritten twice.
DELETE FROM ibexa_content_language_renumbering WHERE old_id = new_id;
-- ibexa:sql-statement-separator
-- InnoDB can't cascade into real_language_id, part of the primary key, while language_id in the same row still references the renumbered language.
-- So these rows are set aside, given the new ids here, and inserted back once the languages are renumbered.
CREATE TEMPORARY TABLE ibexa_object_state_group_language_renumbering AS SELECT * FROM ibexa_object_state_group_language;
-- ibexa:sql-statement-separator
DELETE FROM ibexa_object_state_group_language;
-- ibexa:sql-statement-separator
UPDATE ibexa_object_state_group_language_renumbering SET language_id = COALESCE((SELECT r.new_id FROM ibexa_content_language_renumbering r WHERE r.old_id = ibexa_object_state_group_language_renumbering.language_id), language_id);
-- ibexa:sql-statement-separator
UPDATE ibexa_object_state_group_language_renumbering SET real_language_id = COALESCE((SELECT r.new_id FROM ibexa_content_language_renumbering r WHERE r.old_id = ibexa_object_state_group_language_renumbering.real_language_id), real_language_id);
-- ibexa:sql-statement-separator
-- The ids go negative first: primary keys are checked row by row, and a new id may still belong to another language.
-- The foreign keys cascade both steps.
-- There's no WHERE, as MySQL can't refer to a temporary table twice in one statement. COALESCE keeps the ids missing from the mapping.
UPDATE ibexa_content_language SET id = COALESCE(-(SELECT r.new_id FROM ibexa_content_language_renumbering r WHERE r.old_id = ibexa_content_language.id), id);
-- ibexa:sql-statement-separator
-- No foreign key here, so it follows the languages by hand.
UPDATE ibexa_search_object_word_link w INNER JOIN ibexa_content_language_renumbering r ON r.old_id = w.language_id SET w.language_id = -r.new_id;
-- ibexa:sql-statement-separator
UPDATE ibexa_content_language SET id = -id WHERE id < 0;
-- ibexa:sql-statement-separator
UPDATE ibexa_search_object_word_link SET language_id = -language_id WHERE language_id < 0;
-- ibexa:sql-statement-separator
INSERT INTO ibexa_object_state_group_language SELECT * FROM ibexa_object_state_group_language_renumbering;
-- ibexa:sql-statement-separator
DROP TEMPORARY TABLE ibexa_object_state_group_language_renumbering;
-- ibexa:sql-statement-separator
DROP TEMPORARY TABLE ibexa_content_language_renumbering;
