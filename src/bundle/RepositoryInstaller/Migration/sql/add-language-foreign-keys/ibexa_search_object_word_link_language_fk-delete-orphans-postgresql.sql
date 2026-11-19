DELETE FROM ibexa_search_object_word_link
WHERE NOT EXISTS (
        SELECT 1 FROM ibexa_content_language AS existing_language
        WHERE existing_language.id = ibexa_search_object_word_link.language_id
    );
