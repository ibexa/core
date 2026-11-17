CREATE TABLE ibexa_object_state_group_language_new (
    contentobject_state_group_id INTEGER DEFAULT 0 NOT NULL,
    real_language_id BIGINT DEFAULT 0 NOT NULL,
    description CLOB NOT NULL,
    language_id BIGINT DEFAULT 0 NOT NULL,
    name VARCHAR(45) DEFAULT '' NOT NULL,
    PRIMARY KEY(contentobject_state_group_id, real_language_id),
    CONSTRAINT ibexa_object_state_group_language_language_fk FOREIGN KEY (language_id)
        REFERENCES ibexa_content_language (id) ON DELETE RESTRICT ON UPDATE CASCADE
);
-- ibexa:sql-statement-separator
INSERT INTO ibexa_object_state_group_language_new (contentobject_state_group_id, real_language_id, description, language_id, name)
SELECT contentobject_state_group_id, real_language_id, description, language_id, name FROM ibexa_object_state_group_language;
-- ibexa:sql-statement-separator
DROP TABLE ibexa_object_state_group_language;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_object_state_group_language_new RENAME TO ibexa_object_state_group_language;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_object_state_group_language_language ON ibexa_object_state_group_language (language_id);
