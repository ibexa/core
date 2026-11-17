CREATE TABLE ibexa_content_field_new (
    id INTEGER NOT NULL,
    version INTEGER DEFAULT 0 NOT NULL,
    attribute_original_id INTEGER DEFAULT 0,
    content_type_field_definition_id INTEGER DEFAULT 0 NOT NULL,
    contentobject_id INTEGER DEFAULT 0 NOT NULL,
    data_float DOUBLE PRECISION DEFAULT NULL,
    data_int INTEGER DEFAULT NULL,
    data_text CLOB DEFAULT NULL,
    data_type_string VARCHAR(50) DEFAULT '',
    language_code VARCHAR(20) DEFAULT '' NOT NULL,
    language_id BIGINT DEFAULT 0 NOT NULL,
    sort_key_int INTEGER DEFAULT 0 NOT NULL,
    sort_key_string VARCHAR(255) DEFAULT '' NOT NULL,
    PRIMARY KEY(id, version),
    CONSTRAINT ibexa_content_field_language_fk FOREIGN KEY (language_id)
        REFERENCES ibexa_content_language (id) ON DELETE RESTRICT ON UPDATE CASCADE
);
-- ibexa:sql-statement-separator
INSERT INTO ibexa_content_field_new (id, version, attribute_original_id, content_type_field_definition_id, contentobject_id, data_float, data_int, data_text, data_type_string, language_code, language_id, sort_key_int, sort_key_string)
SELECT id, version, attribute_original_id, content_type_field_definition_id, contentobject_id, data_float, data_int, data_text, data_type_string, language_code, language_id, sort_key_int, sort_key_string FROM ibexa_content_field;
-- ibexa:sql-statement-separator
DROP TABLE ibexa_content_field;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_field_new RENAME TO ibexa_content_field;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_field_co_id_ver ON ibexa_content_field (contentobject_id, version);
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_field_co_id_ver_lang_code ON ibexa_content_field (contentobject_id, version, language_code);
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_field_field_definition_id ON ibexa_content_field (content_type_field_definition_id);
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_field_language_code ON ibexa_content_field (language_code);
-- ibexa:sql-statement-separator
CREATE INDEX sort_key_int ON ibexa_content_field (sort_key_int);
-- ibexa:sql-statement-separator
CREATE INDEX sort_key_string ON ibexa_content_field (sort_key_string);
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_field_language ON ibexa_content_field (language_id);
