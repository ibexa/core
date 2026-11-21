INSERT IGNORE INTO ibexa_content_version_translation (content_version_id, language_id)
SELECT v.id, l.id
FROM ibexa_content_version v
    JOIN ibexa_content_language l ON (v.language_mask & l.id) = l.id
WHERE v.id BETWEEN :from AND :to
