ALTER TABLE ibexa_object_state DROP INDEX ibexa_object_state_lmask;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_object_state DROP COLUMN language_mask;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_object_state_group DROP INDEX ibexa_object_state_group_lmask;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_object_state_group DROP COLUMN language_mask;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_type DROP COLUMN language_mask;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content DROP INDEX ibexa_content_lmask;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content DROP COLUMN language_mask;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_version DROP COLUMN language_mask;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_search_object_word_link DROP COLUMN language_mask;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_url_alias_ml DROP INDEX ibexa_url_alias_ml_text_lang;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_url_alias_ml ADD INDEX ibexa_url_alias_ml_text_lang (text(32), parent);
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_url_alias_ml DROP COLUMN lang_mask;
