CREATE TABLE ibexa_object_state_new (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    default_language_id BIGINT DEFAULT 0 NOT NULL,
    group_id INTEGER DEFAULT 0 NOT NULL,
    identifier VARCHAR(45) DEFAULT '' NOT NULL,
    priority INTEGER DEFAULT 0 NOT NULL,
    CONSTRAINT ibexa_object_state_default_language_fk FOREIGN KEY (default_language_id)
        REFERENCES ibexa_content_language (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT ibexa_object_state_group_fk FOREIGN KEY (group_id)
        REFERENCES ibexa_object_state_group (id) ON DELETE RESTRICT ON UPDATE CASCADE
);
-- ibexa:sql-statement-separator
INSERT INTO ibexa_object_state_new (id, default_language_id, group_id, identifier, priority)
SELECT id, default_language_id, group_id, identifier, priority FROM ibexa_object_state;
-- ibexa:sql-statement-separator
DROP TABLE ibexa_object_state;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_object_state_new RENAME TO ibexa_object_state;
-- ibexa:sql-statement-separator
CREATE UNIQUE INDEX ibexa_object_state_identifier ON ibexa_object_state (group_id, identifier);
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_object_state_priority ON ibexa_object_state (priority);
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_object_state_default_language ON ibexa_object_state (default_language_id);
