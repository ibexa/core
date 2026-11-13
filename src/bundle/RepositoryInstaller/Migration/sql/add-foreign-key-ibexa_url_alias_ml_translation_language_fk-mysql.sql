ALTER TABLE ibexa_url_alias_ml_translation
    ADD CONSTRAINT ibexa_url_alias_ml_translation_language_fk
    FOREIGN KEY (language_id) REFERENCES ibexa_language (id)
    ON DELETE RESTRICT ON UPDATE CASCADE;
