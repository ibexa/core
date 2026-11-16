CREATE TEMPORARY TABLE ibexa_content_language_renumbering (old_id BIGINT NOT NULL, new_id BIGINT NOT NULL, PRIMARY KEY (old_id));
-- ibexa:sql-statement-separator
INSERT INTO ibexa_content_language_renumbering (old_id, new_id) SELECT id, 1 + ROW_NUMBER() OVER (ORDER BY id) FROM ibexa_content_language;
-- ibexa:sql-statement-separator
DELETE FROM ibexa_content_language_renumbering WHERE old_id = new_id;
-- ibexa:sql-statement-separator
UPDATE ibexa_content_language SET id = COALESCE(-(SELECT r.new_id FROM ibexa_content_language_renumbering r WHERE r.old_id = ibexa_content_language.id), id);
-- ibexa:sql-statement-separator
UPDATE ibexa_search_object_word_link SET language_id = -(SELECT r.new_id FROM ibexa_content_language_renumbering r WHERE r.old_id = ibexa_search_object_word_link.language_id) WHERE language_id IN (SELECT old_id FROM ibexa_content_language_renumbering);
-- ibexa:sql-statement-separator
UPDATE ibexa_content_language SET id = -id WHERE id < 0;
-- ibexa:sql-statement-separator
UPDATE ibexa_search_object_word_link SET language_id = -language_id WHERE language_id < 0;
-- ibexa:sql-statement-separator
DROP TABLE ibexa_content_language_renumbering;
