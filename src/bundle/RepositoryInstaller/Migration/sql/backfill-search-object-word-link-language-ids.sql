UPDATE ibexa_search_object_word_link
SET language_id = (language_mask & -2),
    is_main_and_always_available = ((language_mask & 1) = 1)
WHERE id BETWEEN :from AND :to
    AND (
        language_id <> (language_mask & -2)
        OR is_main_and_always_available <> ((language_mask & 1) = 1)
    );
