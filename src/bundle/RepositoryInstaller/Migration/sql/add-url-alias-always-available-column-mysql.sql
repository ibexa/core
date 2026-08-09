ALTER TABLE ibexa_url_alias_ml ADD COLUMN is_always_available TINYINT(1) DEFAULT '0' NOT NULL;
-- ibexa:sql-statement-separator
UPDATE ibexa_url_alias_ml SET is_always_available = 1 WHERE (lang_mask & 1) = 1;
