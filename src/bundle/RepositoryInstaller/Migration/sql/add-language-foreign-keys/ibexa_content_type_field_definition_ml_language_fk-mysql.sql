ALTER TABLE ibexa_content_type_field_definition_ml
    DROP FOREIGN KEY ibexa_content_type_field_definition_ml_lang_fk,
    ADD CONSTRAINT ibexa_content_type_field_definition_ml_language_fk FOREIGN KEY (language_id)
        REFERENCES ibexa_content_language (id) ON DELETE RESTRICT ON UPDATE CASCADE;
