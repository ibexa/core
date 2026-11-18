ALTER TABLE ibexa_object_state_group_language
    ADD CONSTRAINT ibexa_object_state_group_language_group_fk FOREIGN KEY (contentobject_state_group_id)
        REFERENCES ibexa_object_state_group (id) ON DELETE CASCADE ON UPDATE CASCADE;
