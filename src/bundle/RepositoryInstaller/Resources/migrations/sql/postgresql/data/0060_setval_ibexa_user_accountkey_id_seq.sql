SELECT SETVAL('ibexa_user_accountkey_id_seq', COALESCE(MAX(id), 1) ) FROM ibexa_user_accountkey;
