ALTER TABLE ibexa_content_bookmark DROP FOREIGN KEY ibexa_content_bookmark_location_fk;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_bookmark
    ADD CONSTRAINT ibexa_content_bookmark_location_fk FOREIGN KEY (node_id)
        REFERENCES ibexa_content_tree (node_id) ON DELETE CASCADE ON UPDATE NO ACTION;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_bookmark DROP FOREIGN KEY ibexa_content_bookmark_user_fk;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_bookmark
    ADD CONSTRAINT ibexa_content_bookmark_user_fk FOREIGN KEY (user_id)
        REFERENCES ibexa_user (contentobject_id) ON DELETE CASCADE ON UPDATE NO ACTION;
