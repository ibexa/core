ALTER TABLE ibexa_object_state
    ADD CONSTRAINT ibexa_object_state_group_fk FOREIGN KEY (group_id)
        REFERENCES ibexa_object_state_group (id) ON DELETE RESTRICT ON UPDATE CASCADE;
