ALTER TABLE ibexa_search_object_word_link ADD COLUMN language_id INTEGER DEFAULT '0' NOT NULL;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_search_object_word_link ADD COLUMN is_main_and_always_available BOOLEAN DEFAULT '0' NOT NULL;
-- ibexa:sql-statement-separator
UPDATE ibexa_search_object_word_link SET language_id = (language_mask & -2);
-- ibexa:sql-statement-separator
UPDATE ibexa_search_object_word_link SET is_main_and_always_available = 1 WHERE (language_mask & 1) = 1;
