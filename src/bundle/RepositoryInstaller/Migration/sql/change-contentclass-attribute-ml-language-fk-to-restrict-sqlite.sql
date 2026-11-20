CREATE TEMPORARY TABLE __temp__ezcontentclass_attribute_ml AS
    SELECT contentclass_attribute_id, version, language_id, name, description, data_text, data_json
    FROM ezcontentclass_attribute_ml;
-- ibexa:sql-statement-separator
DROP TABLE ezcontentclass_attribute_ml;
-- ibexa:sql-statement-separator
CREATE TABLE ezcontentclass_attribute_ml (
    contentclass_attribute_id INTEGER NOT NULL,
    version INTEGER NOT NULL,
    language_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description CLOB DEFAULT NULL,
    data_text CLOB DEFAULT NULL,
    data_json CLOB DEFAULT NULL,
    PRIMARY KEY(contentclass_attribute_id, version, language_id),
    CONSTRAINT ezcontentclass_attribute_ml_lang_fk FOREIGN KEY (language_id)
        REFERENCES ezcontent_language (id) ON UPDATE CASCADE ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE
);
-- ibexa:sql-statement-separator
INSERT INTO ezcontentclass_attribute_ml (
    contentclass_attribute_id, version, language_id, name, description, data_text, data_json
)
    SELECT contentclass_attribute_id, version, language_id, name, description, data_text, data_json
    FROM __temp__ezcontentclass_attribute_ml;
-- ibexa:sql-statement-separator
DROP TABLE __temp__ezcontentclass_attribute_ml;
-- ibexa:sql-statement-separator
CREATE INDEX ezcontentclass_attribute_ml_lang_fk ON ezcontentclass_attribute_ml (language_id);
