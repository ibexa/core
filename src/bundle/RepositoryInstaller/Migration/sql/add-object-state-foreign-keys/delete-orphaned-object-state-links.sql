DELETE FROM ibexa_object_state_link
WHERE NOT EXISTS (SELECT 1 FROM ibexa_content c WHERE c.id = ibexa_object_state_link.contentobject_id);
