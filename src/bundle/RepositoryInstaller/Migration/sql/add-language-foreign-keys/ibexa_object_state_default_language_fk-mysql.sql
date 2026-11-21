ALTER TABLE ibexa_object_state
    ADD INDEX ibexa_object_state_default_language (default_language_id),
    ADD CONSTRAINT ibexa_object_state_default_language_fk FOREIGN KEY (default_language_id)
        REFERENCES ibexa_content_language (id) ON DELETE RESTRICT ON UPDATE CASCADE;
