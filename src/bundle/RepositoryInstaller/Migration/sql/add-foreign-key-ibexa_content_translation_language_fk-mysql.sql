ALTER TABLE ibexa_content_translation
    ADD CONSTRAINT ibexa_content_translation_language_fk
    FOREIGN KEY (language_id) REFERENCES ibexa_language (id)
    ON DELETE RESTRICT ON UPDATE CASCADE;
