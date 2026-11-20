ALTER TABLE ezcontentclass_attribute_ml DROP FOREIGN KEY ezcontentclass_attribute_ml_lang_fk;
-- ibexa:sql-statement-separator
ALTER TABLE ezcontentclass_attribute_ml
    ADD CONSTRAINT ezcontentclass_attribute_ml_lang_fk FOREIGN KEY (language_id)
        REFERENCES ezcontent_language (id) ON UPDATE CASCADE ON DELETE RESTRICT;
