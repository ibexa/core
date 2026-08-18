CREATE TABLE IF NOT EXISTS ibexa_content_translation (
    content_id INT NOT NULL,
    language_id BIGINT NOT NULL,
    PRIMARY KEY(content_id, language_id)
);
-- ibexa:sql-statement-separator
CREATE INDEX IF NOT EXISTS ibexa_content_translation_language ON ibexa_content_translation (language_id, content_id);
-- ibexa:sql-statement-separator
CREATE TABLE IF NOT EXISTS ibexa_content_version_translation (
    content_version_id INT NOT NULL,
    language_id BIGINT NOT NULL,
    PRIMARY KEY(content_version_id, language_id)
);
-- ibexa:sql-statement-separator
CREATE INDEX IF NOT EXISTS ibexa_content_version_translation_language ON ibexa_content_version_translation (language_id, content_version_id);
-- ibexa:sql-statement-separator
CREATE TABLE IF NOT EXISTS ibexa_url_alias_ml_translation (
    parent INT NOT NULL,
    text_md5 VARCHAR(32) NOT NULL,
    language_id BIGINT NOT NULL,
    PRIMARY KEY(parent, text_md5, language_id)
);
-- ibexa:sql-statement-separator
CREATE INDEX IF NOT EXISTS ibexa_url_alias_ml_translation_language ON ibexa_url_alias_ml_translation (language_id);
