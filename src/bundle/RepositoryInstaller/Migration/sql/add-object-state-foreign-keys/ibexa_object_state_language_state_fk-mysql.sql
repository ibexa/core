ALTER TABLE ibexa_object_state_language
    ADD CONSTRAINT ibexa_object_state_language_state_fk FOREIGN KEY (contentobject_state_id)
        REFERENCES ibexa_object_state (id) ON DELETE CASCADE ON UPDATE CASCADE;
