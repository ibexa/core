ALTER TABLE ibexa_content ADD COLUMN always_available TINYINT(1) DEFAULT '0' NOT NULL;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_version ADD COLUMN always_available TINYINT(1) DEFAULT '0' NOT NULL;
-- ibexa:sql-statement-separator
UPDATE ibexa_content SET always_available = 1 WHERE (language_mask & 1) = 1;
-- ibexa:sql-statement-separator
UPDATE ibexa_content_version SET always_available = 1 WHERE (language_mask & 1) = 1;
