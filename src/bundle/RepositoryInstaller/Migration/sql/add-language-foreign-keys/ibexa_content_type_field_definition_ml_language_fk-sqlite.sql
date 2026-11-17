CREATE TABLE ibexa_content_type_field_definition_ml_new (
    content_type_field_definition_id INTEGER NOT NULL,
    status INTEGER NOT NULL,
    language_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description CLOB DEFAULT NULL,
    data_text CLOB DEFAULT NULL,
    data_json CLOB DEFAULT NULL,
    PRIMARY KEY(content_type_field_definition_id, status, language_id),
    CONSTRAINT ibexa_content_type_field_definition_ml_language_fk FOREIGN KEY (language_id)
        REFERENCES ibexa_content_language (id) ON DELETE RESTRICT ON UPDATE CASCADE
);
-- ibexa:sql-statement-separator
INSERT INTO ibexa_content_type_field_definition_ml_new (content_type_field_definition_id, status, language_id, name, description, data_text, data_json)
SELECT content_type_field_definition_id, status, language_id, name, description, data_text, data_json FROM ibexa_content_type_field_definition_ml;
-- ibexa:sql-statement-separator
DROP TABLE ibexa_content_type_field_definition_ml;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_type_field_definition_ml_new RENAME TO ibexa_content_type_field_definition_ml;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_type_field_definition_ml_lang_fk ON ibexa_content_type_field_definition_ml (language_id);
