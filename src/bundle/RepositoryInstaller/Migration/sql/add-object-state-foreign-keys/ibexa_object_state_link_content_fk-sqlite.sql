CREATE TABLE ibexa_object_state_link_new (
    contentobject_id INTEGER DEFAULT 0 NOT NULL,
    contentobject_state_id INTEGER DEFAULT 0 NOT NULL,
    PRIMARY KEY(contentobject_id, contentobject_state_id),
    CONSTRAINT ibexa_object_state_link_state_fk FOREIGN KEY (contentobject_state_id)
        REFERENCES ibexa_object_state (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT ibexa_object_state_link_content_fk FOREIGN KEY (contentobject_id)
        REFERENCES ibexa_content (id) ON DELETE CASCADE ON UPDATE CASCADE
);
-- ibexa:sql-statement-separator
INSERT INTO ibexa_object_state_link_new (contentobject_id, contentobject_state_id)
SELECT contentobject_id, contentobject_state_id FROM ibexa_object_state_link;
-- ibexa:sql-statement-separator
DROP TABLE ibexa_object_state_link;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_object_state_link_new RENAME TO ibexa_object_state_link;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_object_state_link_state ON ibexa_object_state_link (contentobject_state_id);
