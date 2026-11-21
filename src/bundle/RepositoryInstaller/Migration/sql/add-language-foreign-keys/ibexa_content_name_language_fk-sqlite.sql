CREATE TABLE ibexa_content_name_new (
    contentobject_id INTEGER DEFAULT 0 NOT NULL,
    content_version INTEGER DEFAULT 0 NOT NULL,
    content_translation VARCHAR(20) DEFAULT '' NOT NULL,
    language_id BIGINT DEFAULT 0 NOT NULL,
    name VARCHAR(255) DEFAULT NULL,
    real_translation VARCHAR(20) DEFAULT NULL,
    PRIMARY KEY(contentobject_id, content_version, content_translation),
    CONSTRAINT ibexa_content_name_language_fk FOREIGN KEY (language_id)
        REFERENCES ibexa_content_language (id) ON DELETE RESTRICT ON UPDATE CASCADE
);
-- ibexa:sql-statement-separator
INSERT INTO ibexa_content_name_new (contentobject_id, content_version, content_translation, language_id, name, real_translation)
SELECT contentobject_id, content_version, content_translation, language_id, name, real_translation FROM ibexa_content_name;
-- ibexa:sql-statement-separator
DROP TABLE ibexa_content_name;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_name_new RENAME TO ibexa_content_name;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_name_cov_id ON ibexa_content_name (content_version);
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_name_lang_id ON ibexa_content_name (language_id);
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_name_name ON ibexa_content_name (name);
