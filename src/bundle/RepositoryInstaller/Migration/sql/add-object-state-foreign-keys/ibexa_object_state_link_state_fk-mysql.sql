ALTER TABLE ibexa_object_state_link
    ADD INDEX ibexa_object_state_link_state (contentobject_state_id),
    ADD CONSTRAINT ibexa_object_state_link_state_fk FOREIGN KEY (contentobject_state_id)
        REFERENCES ibexa_object_state (id) ON DELETE RESTRICT ON UPDATE CASCADE;
