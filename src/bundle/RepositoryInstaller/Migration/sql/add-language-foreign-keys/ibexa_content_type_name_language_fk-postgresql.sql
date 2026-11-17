CREATE INDEX ibexa_content_type_name_language ON ibexa_content_type_name (language_id);
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_type_name
    ADD CONSTRAINT ibexa_content_type_name_language_fk FOREIGN KEY (language_id)
        REFERENCES ibexa_content_language (id) ON DELETE RESTRICT ON UPDATE CASCADE;
