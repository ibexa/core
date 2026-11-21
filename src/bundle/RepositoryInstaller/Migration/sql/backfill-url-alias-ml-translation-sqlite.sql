INSERT OR IGNORE INTO ibexa_url_alias_ml_translation (parent, text_md5, language_id)
SELECT u.parent, u.text_md5, l.id
FROM ibexa_url_alias_ml u
    JOIN ibexa_content_language l ON (u.lang_mask & l.id) = l.id
WHERE u.id BETWEEN :from AND :to
