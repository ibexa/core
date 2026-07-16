INSERT INTO "ibexa_user_preference" ("name", "user_id", "value")
SELECT 'focus_mode', u.contentobject_id, '0' FROM "ibexa_user" u WHERE u.login = 'admin';
