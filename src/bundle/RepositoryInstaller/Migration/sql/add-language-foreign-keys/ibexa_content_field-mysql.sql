ALTER TABLE ibexa_content_field
    ADD INDEX ibexa_content_field_language (language_id),
    ADD CONSTRAINT ibexa_content_field_language_fk FOREIGN KEY (language_id)
        REFERENCES ibexa_content_language (id) ON DELETE RESTRICT ON UPDATE CASCADE;
