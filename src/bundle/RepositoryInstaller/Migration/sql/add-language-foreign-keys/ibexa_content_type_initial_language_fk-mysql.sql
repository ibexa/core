ALTER TABLE ibexa_content_type
    ADD INDEX ibexa_content_type_initial_language (initial_language_id),
    ADD CONSTRAINT ibexa_content_type_initial_language_fk FOREIGN KEY (initial_language_id)
        REFERENCES ibexa_content_language (id) ON DELETE RESTRICT ON UPDATE CASCADE;
