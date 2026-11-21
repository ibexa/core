UPDATE ibexa_url_alias_ml
SET always_available = ((lang_mask & 1) = 1)
WHERE id BETWEEN :from AND :to
    AND always_available <> ((lang_mask & 1) = 1);
