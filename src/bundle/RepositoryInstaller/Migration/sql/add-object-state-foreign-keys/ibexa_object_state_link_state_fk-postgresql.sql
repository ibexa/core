CREATE INDEX ibexa_object_state_link_state ON ibexa_object_state_link (contentobject_state_id);
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_object_state_link
    ADD CONSTRAINT ibexa_object_state_link_state_fk FOREIGN KEY (contentobject_state_id)
        REFERENCES ibexa_object_state (id) ON DELETE RESTRICT ON UPDATE CASCADE;
