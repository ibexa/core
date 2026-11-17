CREATE INDEX ibexa_object_state_default_language ON ibexa_object_state (default_language_id);
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_object_state
    ADD CONSTRAINT ibexa_object_state_default_language_fk FOREIGN KEY (default_language_id)
        REFERENCES ibexa_content_language (id) ON DELETE RESTRICT ON UPDATE CASCADE;
