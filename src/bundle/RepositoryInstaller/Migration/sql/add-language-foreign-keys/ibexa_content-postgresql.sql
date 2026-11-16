CREATE INDEX ibexa_content_initial_language ON ibexa_content (initial_language_id);
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content
    ADD CONSTRAINT ibexa_content_initial_language_fk FOREIGN KEY (initial_language_id)
        REFERENCES ibexa_content_language (id) ON DELETE RESTRICT ON UPDATE CASCADE;
