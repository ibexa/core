DELETE FROM ibexa_object_state_language
WHERE EXISTS (
        SELECT 1
        FROM ibexa_object_state_language AS twin
        WHERE twin.contentobject_state_id = ibexa_object_state_language.contentobject_state_id
            AND twin.language_id = ibexa_object_state_language.language_id + 1 - 2 * (ibexa_object_state_language.language_id % 2)
    )
    AND (language_id % 2 = 1) <> (
        language_id - language_id % 2 = (
            SELECT state.default_language_id
            FROM ibexa_object_state AS state
            WHERE state.id = ibexa_object_state_language.contentobject_state_id
        )
    );
