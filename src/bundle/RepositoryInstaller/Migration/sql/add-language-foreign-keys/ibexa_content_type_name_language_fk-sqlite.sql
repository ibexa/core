CREATE TABLE ibexa_content_type_name_new (
    content_type_id INTEGER DEFAULT 0 NOT NULL,
    content_type_status INTEGER DEFAULT 0 NOT NULL,
    language_id BIGINT DEFAULT 0 NOT NULL,
    language_locale VARCHAR(20) DEFAULT '' NOT NULL,
    name VARCHAR(255) DEFAULT '' NOT NULL,
    PRIMARY KEY(content_type_id, content_type_status, language_id),
    CONSTRAINT ibexa_content_type_name_language_fk FOREIGN KEY (language_id)
        REFERENCES ibexa_content_language (id) ON DELETE RESTRICT ON UPDATE CASCADE
);
-- ibexa:sql-statement-separator
INSERT INTO ibexa_content_type_name_new (content_type_id, content_type_status, language_id, language_locale, name)
SELECT content_type_id, content_type_status, language_id, language_locale, name FROM ibexa_content_type_name;
-- ibexa:sql-statement-separator
DROP TABLE ibexa_content_type_name;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_type_name_new RENAME TO ibexa_content_type_name;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_type_name_language ON ibexa_content_type_name (language_id);
