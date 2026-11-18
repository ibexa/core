CREATE TEMPORARY TABLE ibexa_content_language_renumbering (old_id BIGINT NOT NULL, new_id BIGINT NOT NULL, PRIMARY KEY (old_id));
-- ibexa:sql-statement-separator
INSERT INTO ibexa_content_language_renumbering (old_id, new_id) SELECT id, 1 + ROW_NUMBER() OVER (ORDER BY id) FROM ibexa_content_language;
-- ibexa:sql-statement-separator
-- Languages keeping their id are left out of the mapping, so their rows aren't rewritten twice.
DELETE FROM ibexa_content_language_renumbering WHERE old_id = new_id;
-- ibexa:sql-statement-separator
-- The ids go negative first: primary keys are checked row by row, and a new id may still belong to another language.
-- The foreign keys cascade both steps.
-- COALESCE instead of a WHERE keeps the statements as on MySQL, which can't refer to a temporary table twice in one statement.
UPDATE ibexa_content_language SET id = COALESCE(-(SELECT r.new_id FROM ibexa_content_language_renumbering r WHERE r.old_id = ibexa_content_language.id), id);
-- ibexa:sql-statement-separator
UPDATE ibexa_content_language SET id = -id WHERE id < 0;
-- ibexa:sql-statement-separator
DROP TABLE ibexa_content_language_renumbering;
