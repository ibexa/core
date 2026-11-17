CREATE TABLE ibexa_object_state_group_new (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    default_language_id BIGINT DEFAULT 0 NOT NULL,
    identifier VARCHAR(45) DEFAULT '' NOT NULL,
    CONSTRAINT ibexa_object_state_group_default_language_fk FOREIGN KEY (default_language_id)
        REFERENCES ibexa_content_language (id) ON DELETE RESTRICT ON UPDATE CASCADE
);
-- ibexa:sql-statement-separator
INSERT INTO ibexa_object_state_group_new (id, default_language_id, identifier)
SELECT id, default_language_id, identifier FROM ibexa_object_state_group;
-- ibexa:sql-statement-separator
DROP TABLE ibexa_object_state_group;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_object_state_group_new RENAME TO ibexa_object_state_group;
-- ibexa:sql-statement-separator
CREATE UNIQUE INDEX ibexa_object_state_group_identifier ON ibexa_object_state_group (identifier);
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_object_state_group_default_language ON ibexa_object_state_group (default_language_id);
