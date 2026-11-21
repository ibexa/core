INSERT IGNORE INTO ibexa_content_translation (content_id, language_id)
SELECT c.id, l.id
FROM ibexa_content c
    JOIN ibexa_content_language l ON (c.language_mask & l.id) = l.id
WHERE c.id BETWEEN :from AND :to
