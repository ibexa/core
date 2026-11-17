ALTER TABLE ibexa_content_name
    ADD CONSTRAINT ibexa_content_name_language_fk FOREIGN KEY (language_id)
        REFERENCES ibexa_content_language (id) ON DELETE RESTRICT ON UPDATE CASCADE;
