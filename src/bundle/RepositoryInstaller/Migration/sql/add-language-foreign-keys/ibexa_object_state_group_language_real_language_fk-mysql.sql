ALTER TABLE ibexa_object_state_group_language
    ADD INDEX ibexa_object_state_group_language_real_language (real_language_id),
    ADD CONSTRAINT ibexa_object_state_group_language_real_language_fk FOREIGN KEY (real_language_id)
        REFERENCES ibexa_content_language (id) ON DELETE RESTRICT ON UPDATE CASCADE;
