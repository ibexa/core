CREATE TABLE ibexa_content_type_new (
    id INTEGER NOT NULL,
    status INTEGER DEFAULT 0 NOT NULL,
    always_available INTEGER DEFAULT 0 NOT NULL,
    contentobject_name VARCHAR(255) DEFAULT NULL,
    created INTEGER DEFAULT 0 NOT NULL,
    creator_id INTEGER DEFAULT 0 NOT NULL,
    identifier VARCHAR(50) DEFAULT '' NOT NULL,
    initial_language_id BIGINT DEFAULT 0 NOT NULL,
    is_container INTEGER DEFAULT 0 NOT NULL,
    modified INTEGER DEFAULT 0 NOT NULL,
    modifier_id INTEGER DEFAULT 0 NOT NULL,
    remote_id VARCHAR(100) DEFAULT '' NOT NULL,
    serialized_description_list CLOB DEFAULT NULL,
    serialized_name_list CLOB DEFAULT NULL,
    sort_field INTEGER DEFAULT 1 NOT NULL,
    sort_order INTEGER DEFAULT 1 NOT NULL,
    url_alias_name VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY(id, status),
    CONSTRAINT ibexa_content_type_initial_language_fk FOREIGN KEY (initial_language_id)
        REFERENCES ibexa_content_language (id) ON DELETE RESTRICT ON UPDATE CASCADE
);
-- ibexa:sql-statement-separator
INSERT INTO ibexa_content_type_new (id, status, always_available, contentobject_name, created, creator_id, identifier, initial_language_id, is_container, modified, modifier_id, remote_id, serialized_description_list, serialized_name_list, sort_field, sort_order, url_alias_name)
SELECT id, status, always_available, contentobject_name, created, creator_id, identifier, initial_language_id, is_container, modified, modifier_id, remote_id, serialized_description_list, serialized_name_list, sort_field, sort_order, url_alias_name FROM ibexa_content_type;
-- ibexa:sql-statement-separator
DROP TABLE ibexa_content_type;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_type_new RENAME TO ibexa_content_type;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_type_identifier ON ibexa_content_type (identifier, status);
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_type_status ON ibexa_content_type (status);
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_type_initial_language ON ibexa_content_type (initial_language_id);
