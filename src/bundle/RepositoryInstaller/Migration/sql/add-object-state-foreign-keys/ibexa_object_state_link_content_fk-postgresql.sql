ALTER TABLE ibexa_object_state_link
    ADD CONSTRAINT ibexa_object_state_link_content_fk FOREIGN KEY (contentobject_id)
        REFERENCES ibexa_content (id) ON DELETE CASCADE ON UPDATE CASCADE;
