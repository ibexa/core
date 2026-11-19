CREATE TABLE ibexa_object_state_language_new (
    contentobject_state_id INTEGER DEFAULT 0 NOT NULL,
    language_id BIGINT DEFAULT 0 NOT NULL,
    description CLOB NOT NULL,
    name VARCHAR(45) DEFAULT '' NOT NULL,
    PRIMARY KEY(contentobject_state_id, language_id),
    CONSTRAINT ibexa_object_state_language_language_fk FOREIGN KEY (language_id)
        REFERENCES ibexa_content_language (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT ibexa_object_state_language_state_fk FOREIGN KEY (contentobject_state_id)
        REFERENCES ibexa_object_state (id) ON DELETE CASCADE ON UPDATE CASCADE
);
-- ibexa:sql-statement-separator
INSERT INTO ibexa_object_state_language_new (contentobject_state_id, language_id, description, name)
SELECT contentobject_state_id, language_id, description, name FROM ibexa_object_state_language;
-- ibexa:sql-statement-separator
DROP TABLE ibexa_object_state_language;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_object_state_language_new RENAME TO ibexa_object_state_language;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_object_state_language_language ON ibexa_object_state_language (language_id);
