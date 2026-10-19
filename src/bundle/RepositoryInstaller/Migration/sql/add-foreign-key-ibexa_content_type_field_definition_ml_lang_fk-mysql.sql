ALTER TABLE ibexa_content_type_field_definition_ml
    ADD CONSTRAINT ibexa_content_type_field_definition_ml_lang_fk
    FOREIGN KEY (language_id) REFERENCES ibexa_language (id)
    ON DELETE CASCADE ON UPDATE CASCADE;
