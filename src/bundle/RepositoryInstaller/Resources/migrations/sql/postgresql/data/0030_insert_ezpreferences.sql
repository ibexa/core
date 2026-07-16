INSERT INTO "ezpreferences" ("name", "user_id", "value")
SELECT 'focus_mode', u.contentobject_id, '0' FROM "ezuser" u WHERE u.login = 'admin';
