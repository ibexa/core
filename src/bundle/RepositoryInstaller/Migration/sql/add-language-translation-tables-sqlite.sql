CREATE TABLE IF NOT EXISTS ibexa_content_translation (
    content_id INTEGER NOT NULL,
    language_id BIGINT NOT NULL,
    PRIMARY KEY(content_id, language_id),
    CONSTRAINT ibexa_content_translation_content_fk
        FOREIGN KEY (content_id) REFERENCES ibexa_content (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT ibexa_content_translation_language_fk
        FOREIGN KEY (language_id) REFERENCES ibexa_content_language (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
);
-- ibexa:sql-statement-separator
CREATE INDEX IF NOT EXISTS ibexa_content_translation_language ON ibexa_content_translation (language_id, content_id);
-- ibexa:sql-statement-separator
CREATE TABLE IF NOT EXISTS ibexa_content_version_translation (
    content_version_id INTEGER NOT NULL,
    language_id BIGINT NOT NULL,
    PRIMARY KEY(content_version_id, language_id),
    CONSTRAINT ibexa_content_version_translation_version_fk
        FOREIGN KEY (content_version_id) REFERENCES ibexa_content_version (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT ibexa_content_version_translation_language_fk
        FOREIGN KEY (language_id) REFERENCES ibexa_content_language (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
);
-- ibexa:sql-statement-separator
CREATE INDEX IF NOT EXISTS ibexa_content_version_translation_language ON ibexa_content_version_translation (language_id, content_version_id);
-- ibexa:sql-statement-separator
CREATE TABLE IF NOT EXISTS ibexa_url_alias_ml_translation (
    parent INTEGER NOT NULL,
    text_md5 VARCHAR(32) NOT NULL,
    language_id BIGINT NOT NULL,
    PRIMARY KEY(parent, text_md5, language_id),
    CONSTRAINT ibexa_url_alias_ml_translation_alias_fk
        FOREIGN KEY (parent, text_md5) REFERENCES ibexa_url_alias_ml (parent, text_md5)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT ibexa_url_alias_ml_translation_language_fk
        FOREIGN KEY (language_id) REFERENCES ibexa_content_language (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
);
-- ibexa:sql-statement-separator
CREATE INDEX IF NOT EXISTS ibexa_url_alias_ml_translation_language ON ibexa_url_alias_ml_translation (language_id);
