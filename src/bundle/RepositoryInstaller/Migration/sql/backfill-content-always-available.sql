UPDATE ibexa_content
SET always_available = ((language_mask & 1) = 1)
WHERE id BETWEEN :from AND :to
    AND always_available <> ((language_mask & 1) = 1)
