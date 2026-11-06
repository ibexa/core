CREATE TABLE ibexa_language_new (
    id INTEGER DEFAULT 0 NOT NULL,
    disabled INTEGER DEFAULT 0 NOT NULL,
    locale VARCHAR(20) DEFAULT '' NOT NULL,
    name VARCHAR(255) DEFAULT '' NOT NULL,
    PRIMARY KEY(id)
);
-- ibexa:sql-statement-separator
INSERT INTO ibexa_language_new (id, disabled, locale, name)
SELECT id, disabled, locale, name FROM ibexa_language;
-- ibexa:sql-statement-separator
DROP TABLE ibexa_language;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_language_new RENAME TO ibexa_language;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_language_name ON ibexa_language (name);
-- ibexa:sql-statement-separator
CREATE TABLE ibexa_content_translation_new (
    content_id INTEGER NOT NULL,
    language_id INTEGER NOT NULL,
    PRIMARY KEY(content_id, language_id),
    CONSTRAINT ibexa_content_translation_content_fk FOREIGN KEY (content_id)
        REFERENCES ibexa_content (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT ibexa_content_translation_language_fk FOREIGN KEY (language_id)
        REFERENCES ibexa_language (id) ON DELETE RESTRICT ON UPDATE CASCADE
);
-- ibexa:sql-statement-separator
INSERT INTO ibexa_content_translation_new (content_id, language_id)
SELECT content_id, language_id FROM ibexa_content_translation;
-- ibexa:sql-statement-separator
DROP TABLE ibexa_content_translation;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_translation_new RENAME TO ibexa_content_translation;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_translation_language ON ibexa_content_translation (language_id, content_id);
-- ibexa:sql-statement-separator
CREATE TABLE ibexa_content_version_translation_new (
    content_version_id INTEGER NOT NULL,
    language_id INTEGER NOT NULL,
    PRIMARY KEY(content_version_id, language_id),
    CONSTRAINT ibexa_content_version_translation_version_fk FOREIGN KEY (content_version_id)
        REFERENCES ibexa_content_version (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT ibexa_content_version_translation_language_fk FOREIGN KEY (language_id)
        REFERENCES ibexa_language (id) ON DELETE RESTRICT ON UPDATE CASCADE
);
-- ibexa:sql-statement-separator
INSERT INTO ibexa_content_version_translation_new (content_version_id, language_id)
SELECT content_version_id, language_id FROM ibexa_content_version_translation;
-- ibexa:sql-statement-separator
DROP TABLE ibexa_content_version_translation;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_version_translation_new RENAME TO ibexa_content_version_translation;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_version_translation_language
    ON ibexa_content_version_translation (language_id, content_version_id);
-- ibexa:sql-statement-separator
CREATE TABLE ibexa_url_alias_ml_translation_new (
    parent INTEGER NOT NULL,
    text_md5 VARCHAR(32) NOT NULL,
    language_id INTEGER NOT NULL,
    PRIMARY KEY(parent, text_md5, language_id),
    CONSTRAINT ibexa_url_alias_ml_translation_alias_fk FOREIGN KEY (parent, text_md5)
        REFERENCES ibexa_url_alias_ml (parent, text_md5) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT ibexa_url_alias_ml_translation_language_fk FOREIGN KEY (language_id)
        REFERENCES ibexa_language (id) ON DELETE RESTRICT ON UPDATE CASCADE
);
-- ibexa:sql-statement-separator
INSERT INTO ibexa_url_alias_ml_translation_new (parent, text_md5, language_id)
SELECT parent, text_md5, language_id FROM ibexa_url_alias_ml_translation;
-- ibexa:sql-statement-separator
DROP TABLE ibexa_url_alias_ml_translation;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_url_alias_ml_translation_new RENAME TO ibexa_url_alias_ml_translation;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_url_alias_ml_translation_language ON ibexa_url_alias_ml_translation (language_id);
-- ibexa:sql-statement-separator
CREATE TABLE ibexa_content_type_field_definition_ml_new (
    content_type_field_definition_id INTEGER NOT NULL,
    status INTEGER NOT NULL,
    language_id INTEGER NOT NULL,
    name VARCHAR(255) NOT NULL,
    description CLOB DEFAULT NULL,
    data_text CLOB DEFAULT NULL,
    data_json CLOB DEFAULT NULL,
    PRIMARY KEY(content_type_field_definition_id, status, language_id),
    CONSTRAINT ezcontentclass_attribute_ml_lang_fk FOREIGN KEY (language_id)
        REFERENCES ibexa_language (id) ON UPDATE CASCADE ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
);
-- ibexa:sql-statement-separator
INSERT INTO ibexa_content_type_field_definition_ml_new (content_type_field_definition_id, status, language_id, name,
    description, data_text, data_json)
SELECT content_type_field_definition_id, status, language_id, name, description, data_text, data_json
FROM ibexa_content_type_field_definition_ml;
-- ibexa:sql-statement-separator
DROP TABLE ibexa_content_type_field_definition_ml;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_type_field_definition_ml_new RENAME TO ibexa_content_type_field_definition_ml;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_type_field_definition_ml_lang_fk ON ibexa_content_type_field_definition_ml (language_id);
-- ibexa:sql-statement-separator
CREATE TABLE ibexa_content_new (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    content_type_id INTEGER DEFAULT 0 NOT NULL,
    current_version INTEGER DEFAULT NULL,
    initial_language_id INTEGER DEFAULT 0 NOT NULL,
    modified INTEGER DEFAULT 0 NOT NULL,
    name VARCHAR(255) DEFAULT NULL,
    owner_id INTEGER DEFAULT 0 NOT NULL,
    published INTEGER DEFAULT 0 NOT NULL,
    remote_id VARCHAR(100) DEFAULT NULL,
    section_id INTEGER DEFAULT 0 NOT NULL,
    status INTEGER DEFAULT 0,
    is_hidden BOOLEAN DEFAULT '0' NOT NULL,
    always_available BOOLEAN DEFAULT '0' NOT NULL
);
-- ibexa:sql-statement-separator
INSERT INTO ibexa_content_new (id, content_type_id, current_version, initial_language_id, modified, name, owner_id,
    published, remote_id, section_id, status, is_hidden, always_available)
SELECT id, content_type_id, current_version, initial_language_id, modified, name, owner_id, published, remote_id,
    section_id, status, is_hidden, always_available FROM ibexa_content;
-- ibexa:sql-statement-separator
DROP TABLE ibexa_content;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_new RENAME TO ibexa_content;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_currentversion ON ibexa_content (current_version);
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_owner ON ibexa_content (owner_id);
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_pub ON ibexa_content (published);
-- ibexa:sql-statement-separator
CREATE UNIQUE INDEX ibexa_content_remote_id ON ibexa_content (remote_id);
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_section ON ibexa_content (section_id);
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_status ON ibexa_content (status);
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_type_id ON ibexa_content (content_type_id);
-- ibexa:sql-statement-separator
CREATE TABLE ibexa_content_version_new (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    contentobject_id INTEGER NOT NULL,
    created INTEGER DEFAULT 0 NOT NULL,
    creator_id INTEGER DEFAULT 0 NOT NULL,
    initial_language_id INTEGER DEFAULT 0 NOT NULL,
    modified INTEGER DEFAULT 0 NOT NULL,
    status INTEGER DEFAULT 0 NOT NULL,
    user_id INTEGER DEFAULT 0 NOT NULL,
    version INTEGER DEFAULT 0 NOT NULL,
    workflow_event_pos INTEGER DEFAULT 0,
    always_available BOOLEAN DEFAULT '0' NOT NULL
);
-- ibexa:sql-statement-separator
INSERT INTO ibexa_content_version_new (id, contentobject_id, created, creator_id, initial_language_id, modified, status,
    user_id, version, workflow_event_pos, always_available)
SELECT id, contentobject_id, created, creator_id, initial_language_id, modified, status, user_id, version,
    workflow_event_pos, always_available FROM ibexa_content_version;
-- ibexa:sql-statement-separator
DROP TABLE ibexa_content_version;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_version_new RENAME TO ibexa_content_version;
-- ibexa:sql-statement-separator
CREATE UNIQUE INDEX ibexa_content_version_coid_version_unique ON ibexa_content_version (contentobject_id, version);
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_version_creator_id ON ibexa_content_version (creator_id);
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_version_idx_status ON ibexa_content_version (contentobject_id, status);
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_version_status ON ibexa_content_version (status);
-- ibexa:sql-statement-separator
CREATE TABLE ibexa_content_type_new (
    id INTEGER NOT NULL,
    status INTEGER DEFAULT 0 NOT NULL,
    always_available INTEGER DEFAULT 0 NOT NULL,
    contentobject_name VARCHAR(255) DEFAULT NULL,
    created INTEGER DEFAULT 0 NOT NULL,
    creator_id INTEGER DEFAULT 0 NOT NULL,
    identifier VARCHAR(50) DEFAULT '' NOT NULL,
    initial_language_id INTEGER DEFAULT 0 NOT NULL,
    is_container INTEGER DEFAULT 0 NOT NULL,
    modified INTEGER DEFAULT 0 NOT NULL,
    modifier_id INTEGER DEFAULT 0 NOT NULL,
    remote_id VARCHAR(100) DEFAULT '' NOT NULL,
    serialized_description_list CLOB DEFAULT NULL,
    serialized_name_list CLOB DEFAULT NULL,
    sort_field INTEGER DEFAULT 1 NOT NULL,
    sort_order INTEGER DEFAULT 1 NOT NULL,
    url_alias_name VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY(id, status)
);
-- ibexa:sql-statement-separator
INSERT INTO ibexa_content_type_new (id, status, always_available, contentobject_name, created, creator_id, identifier,
    initial_language_id, is_container, modified, modifier_id, remote_id, serialized_description_list,
    serialized_name_list, sort_field, sort_order, url_alias_name)
SELECT id, status, always_available, contentobject_name, created, creator_id, identifier, initial_language_id,
    is_container, modified, modifier_id, remote_id, serialized_description_list, serialized_name_list, sort_field,
    sort_order, url_alias_name FROM ibexa_content_type;
-- ibexa:sql-statement-separator
DROP TABLE ibexa_content_type;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_type_new RENAME TO ibexa_content_type;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_type_identifier ON ibexa_content_type (identifier, status);
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_type_status ON ibexa_content_type (status);
-- ibexa:sql-statement-separator
CREATE TABLE ibexa_object_state_new (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    default_language_id INTEGER DEFAULT 0 NOT NULL,
    group_id INTEGER DEFAULT 0 NOT NULL,
    identifier VARCHAR(45) DEFAULT '' NOT NULL,
    priority INTEGER DEFAULT 0 NOT NULL
);
-- ibexa:sql-statement-separator
INSERT INTO ibexa_object_state_new (id, default_language_id, group_id, identifier, priority)
SELECT id, default_language_id, group_id, identifier, priority FROM ibexa_object_state;
-- ibexa:sql-statement-separator
DROP TABLE ibexa_object_state;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_object_state_new RENAME TO ibexa_object_state;
-- ibexa:sql-statement-separator
CREATE UNIQUE INDEX ibexa_object_state_identifier ON ibexa_object_state (group_id, identifier);
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_object_state_priority ON ibexa_object_state (priority);
-- ibexa:sql-statement-separator
CREATE TABLE ibexa_object_state_group_new (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    default_language_id INTEGER DEFAULT 0 NOT NULL,
    identifier VARCHAR(45) DEFAULT '' NOT NULL
);
-- ibexa:sql-statement-separator
INSERT INTO ibexa_object_state_group_new (id, default_language_id, identifier)
SELECT id, default_language_id, identifier FROM ibexa_object_state_group;
-- ibexa:sql-statement-separator
DROP TABLE ibexa_object_state_group;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_object_state_group_new RENAME TO ibexa_object_state_group;
-- ibexa:sql-statement-separator
CREATE UNIQUE INDEX ibexa_object_state_group_identifier ON ibexa_object_state_group (identifier);
-- ibexa:sql-statement-separator
CREATE TABLE ibexa_object_state_language_new (
    contentobject_state_id INTEGER DEFAULT 0 NOT NULL,
    language_id INTEGER DEFAULT 0 NOT NULL,
    description CLOB NOT NULL,
    name VARCHAR(45) DEFAULT '' NOT NULL,
    PRIMARY KEY(contentobject_state_id, language_id)
);
-- ibexa:sql-statement-separator
INSERT INTO ibexa_object_state_language_new (contentobject_state_id, language_id, description, name)
SELECT contentobject_state_id, language_id, description, name FROM ibexa_object_state_language;
-- ibexa:sql-statement-separator
DROP TABLE ibexa_object_state_language;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_object_state_language_new RENAME TO ibexa_object_state_language;
-- ibexa:sql-statement-separator
CREATE TABLE ibexa_object_state_group_language_new (
    contentobject_state_group_id INTEGER DEFAULT 0 NOT NULL,
    real_language_id INTEGER DEFAULT 0 NOT NULL,
    description CLOB NOT NULL,
    language_id INTEGER DEFAULT 0 NOT NULL,
    name VARCHAR(45) DEFAULT '' NOT NULL,
    PRIMARY KEY(contentobject_state_group_id, real_language_id)
);
-- ibexa:sql-statement-separator
INSERT INTO ibexa_object_state_group_language_new (contentobject_state_group_id, real_language_id, description,
    language_id, name)
SELECT contentobject_state_group_id, real_language_id, description, language_id, name
FROM ibexa_object_state_group_language;
-- ibexa:sql-statement-separator
DROP TABLE ibexa_object_state_group_language;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_object_state_group_language_new RENAME TO ibexa_object_state_group_language;
