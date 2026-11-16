ALTER TABLE ibexa_content_type_field_definition_ml
    ADD CONSTRAINT ibexa_content_type_field_definition_ml_language_fk FOREIGN KEY (language_id)
        REFERENCES ibexa_content_language (id) ON DELETE RESTRICT ON UPDATE CASCADE;
