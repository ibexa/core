DELETE FROM ibexa_content_version WHERE contentobject_id IS NULL;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_version ALTER contentobject_id SET NOT NULL;
-- ibexa:sql-statement-separator
CREATE UNIQUE INDEX ibexa_content_version_coid_version_unique ON ibexa_content_version (contentobject_id, version);
-- ibexa:sql-statement-separator
DROP INDEX ibexa_content_version_idx_ver;
