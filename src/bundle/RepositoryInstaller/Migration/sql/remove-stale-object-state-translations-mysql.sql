DELETE stale
FROM ibexa_object_state_language AS stale
    INNER JOIN ibexa_object_state AS state ON state.id = stale.contentobject_state_id
    INNER JOIN ibexa_object_state_language AS twin
        ON twin.contentobject_state_id = stale.contentobject_state_id
        AND twin.language_id = stale.language_id + 1 - 2 * (stale.language_id % 2)
WHERE NOT EXISTS (
        SELECT 1 FROM ibexa_content_language AS odd_language
        WHERE odd_language.id = stale.language_id + 1 - stale.language_id % 2
    )
    AND (stale.language_id % 2 = 1) <> (stale.language_id - stale.language_id % 2 = state.default_language_id);
