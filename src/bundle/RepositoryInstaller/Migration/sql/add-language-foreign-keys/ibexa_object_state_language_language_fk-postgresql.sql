CREATE INDEX ibexa_object_state_language_language ON ibexa_object_state_language (language_id);
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_object_state_language
    ADD CONSTRAINT ibexa_object_state_language_language_fk FOREIGN KEY (language_id)
        REFERENCES ibexa_content_language (id) ON DELETE RESTRICT ON UPDATE CASCADE;
