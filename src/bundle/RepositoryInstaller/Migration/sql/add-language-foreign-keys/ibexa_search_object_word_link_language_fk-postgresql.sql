CREATE INDEX ibexa_search_object_word_link_language ON ibexa_search_object_word_link (language_id);
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_search_object_word_link
    ADD CONSTRAINT ibexa_search_object_word_link_language_fk FOREIGN KEY (language_id)
        REFERENCES ibexa_content_language (id) ON DELETE RESTRICT ON UPDATE CASCADE;
