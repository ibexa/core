DELETE FROM ibexa_object_state_language AS stale
USING ibexa_object_state AS state, ibexa_object_state_language AS twin
WHERE state.id = stale.contentobject_state_id
    AND twin.contentobject_state_id = stale.contentobject_state_id
    AND twin.language_id = stale.language_id + 1 - 2 * (stale.language_id % 2)
    AND (stale.language_id % 2 = 1) <> (stale.language_id - stale.language_id % 2 = state.default_language_id);
