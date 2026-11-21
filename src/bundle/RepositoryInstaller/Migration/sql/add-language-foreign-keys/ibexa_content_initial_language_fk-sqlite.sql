CREATE TABLE ibexa_content_new (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    content_type_id INTEGER DEFAULT 0 NOT NULL,
    current_version INTEGER DEFAULT NULL,
    initial_language_id BIGINT DEFAULT 0 NOT NULL,
    modified INTEGER DEFAULT 0 NOT NULL,
    name VARCHAR(255) DEFAULT NULL,
    owner_id INTEGER DEFAULT 0 NOT NULL,
    published INTEGER DEFAULT 0 NOT NULL,
    remote_id VARCHAR(100) DEFAULT NULL,
    section_id INTEGER DEFAULT 0 NOT NULL,
    status INTEGER DEFAULT 0,
    is_hidden BOOLEAN DEFAULT '0' NOT NULL,
    always_available BOOLEAN DEFAULT '0' NOT NULL,
    CONSTRAINT ibexa_content_initial_language_fk FOREIGN KEY (initial_language_id)
        REFERENCES ibexa_content_language (id) ON DELETE RESTRICT ON UPDATE CASCADE
);
-- ibexa:sql-statement-separator
INSERT INTO ibexa_content_new (id, content_type_id, current_version, initial_language_id, modified, name, owner_id, published, remote_id, section_id, status, is_hidden, always_available)
SELECT id, content_type_id, current_version, initial_language_id, modified, name, owner_id, published, remote_id, section_id, status, is_hidden, always_available FROM ibexa_content;
-- ibexa:sql-statement-separator
DROP TABLE ibexa_content;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_new RENAME TO ibexa_content;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_currentversion ON ibexa_content (current_version);
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_owner ON ibexa_content (owner_id);
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_pub ON ibexa_content (published);
-- ibexa:sql-statement-separator
CREATE UNIQUE INDEX ibexa_content_remote_id ON ibexa_content (remote_id);
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_section ON ibexa_content (section_id);
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_status ON ibexa_content (status);
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_type_id ON ibexa_content (content_type_id);
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_initial_language ON ibexa_content (initial_language_id);
