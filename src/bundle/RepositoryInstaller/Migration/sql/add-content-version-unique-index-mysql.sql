DELETE FROM ibexa_content_version WHERE contentobject_id IS NULL;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_version CHANGE contentobject_id contentobject_id INT NOT NULL;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_version ADD UNIQUE INDEX ibexa_content_version_coid_version_unique (contentobject_id, version);
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_version DROP INDEX ibexa_content_version_idx_ver;
