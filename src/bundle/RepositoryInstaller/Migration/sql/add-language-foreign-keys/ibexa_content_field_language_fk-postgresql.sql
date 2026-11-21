CREATE INDEX ibexa_content_field_language ON ibexa_content_field (language_id);
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_field
    ADD CONSTRAINT ibexa_content_field_language_fk FOREIGN KEY (language_id)
        REFERENCES ibexa_content_language (id) ON DELETE RESTRICT ON UPDATE CASCADE;
