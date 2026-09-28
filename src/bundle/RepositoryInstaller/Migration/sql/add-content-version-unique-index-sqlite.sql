DELETE FROM ibexa_content_version WHERE contentobject_id IS NULL;
-- ibexa:sql-statement-separator
CREATE TABLE ibexa_content_version_new (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    contentobject_id INTEGER NOT NULL,
    created INTEGER DEFAULT 0 NOT NULL,
    creator_id INTEGER DEFAULT 0 NOT NULL,
    initial_language_id BIGINT DEFAULT 0 NOT NULL,
    language_mask BIGINT DEFAULT 0 NOT NULL,
    modified INTEGER DEFAULT 0 NOT NULL,
    status INTEGER DEFAULT 0 NOT NULL,
    user_id INTEGER DEFAULT 0 NOT NULL,
    version INTEGER DEFAULT 0 NOT NULL,
    workflow_event_pos INTEGER DEFAULT 0
);
-- ibexa:sql-statement-separator
INSERT INTO ibexa_content_version_new (id, contentobject_id, created, creator_id, initial_language_id, language_mask, modified, status, user_id, version, workflow_event_pos)
SELECT id, contentobject_id, created, creator_id, initial_language_id, language_mask, modified, status, user_id, version, workflow_event_pos FROM ibexa_content_version;
-- ibexa:sql-statement-separator
DROP TABLE ibexa_content_version;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_version_new RENAME TO ibexa_content_version;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_version_status ON ibexa_content_version (status);
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_version_idx_status ON ibexa_content_version (contentobject_id, status);
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_version_creator_id ON ibexa_content_version (creator_id);
-- ibexa:sql-statement-separator
CREATE UNIQUE INDEX ibexa_content_version_coid_version_unique ON ibexa_content_version (contentobject_id, version);
