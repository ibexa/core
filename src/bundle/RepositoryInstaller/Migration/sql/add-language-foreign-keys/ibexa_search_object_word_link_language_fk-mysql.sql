ALTER TABLE ibexa_search_object_word_link
    ADD INDEX ibexa_search_object_word_link_language (language_id),
    ADD CONSTRAINT ibexa_search_object_word_link_language_fk FOREIGN KEY (language_id)
        REFERENCES ibexa_content_language (id) ON DELETE RESTRICT ON UPDATE CASCADE;
