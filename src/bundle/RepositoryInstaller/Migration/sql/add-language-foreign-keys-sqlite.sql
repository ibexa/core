CREATE TABLE ibexa_content_new (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    content_type_id INTEGER DEFAULT 0 NOT NULL,
    current_version INTEGER DEFAULT NULL,
    initial_language_id BIGINT DEFAULT 0 NOT NULL,
    modified INTEGER DEFAULT 0 NOT NULL,
    name VARCHAR(255) DEFAULT NULL,
    owner_id INTEGER DEFAULT 0 NOT NULL,
    published INTEGER DEFAULT 0 NOT NULL,
    remote_id VARCHAR(100) DEFAULT NULL,
    section_id INTEGER DEFAULT 0 NOT NULL,
    status INTEGER DEFAULT 0,
    is_hidden BOOLEAN DEFAULT '0' NOT NULL,
    always_available BOOLEAN DEFAULT '0' NOT NULL,
    CONSTRAINT ibexa_content_initial_language_fk FOREIGN KEY (initial_language_id)
        REFERENCES ibexa_content_language (id) ON DELETE RESTRICT ON UPDATE CASCADE
);
-- ibexa:sql-statement-separator
INSERT INTO ibexa_content_new (id, content_type_id, current_version, initial_language_id, modified, name, owner_id, published, remote_id, section_id, status, is_hidden, always_available)
SELECT id, content_type_id, current_version, initial_language_id, modified, name, owner_id, published, remote_id, section_id, status, is_hidden, always_available FROM ibexa_content;
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
CREATE INDEX ibexa_content_initial_language ON ibexa_content (initial_language_id);
-- ibexa:sql-statement-separator
CREATE TABLE ibexa_content_version_new (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    contentobject_id INTEGER NOT NULL,
    created INTEGER DEFAULT 0 NOT NULL,
    creator_id INTEGER DEFAULT 0 NOT NULL,
    initial_language_id BIGINT DEFAULT 0 NOT NULL,
    modified INTEGER DEFAULT 0 NOT NULL,
    status INTEGER DEFAULT 0 NOT NULL,
    user_id INTEGER DEFAULT 0 NOT NULL,
    version INTEGER DEFAULT 0 NOT NULL,
    workflow_event_pos INTEGER DEFAULT 0,
    always_available BOOLEAN DEFAULT '0' NOT NULL,
    CONSTRAINT ibexa_content_version_initial_language_fk FOREIGN KEY (initial_language_id)
        REFERENCES ibexa_content_language (id) ON DELETE RESTRICT ON UPDATE CASCADE
);
-- ibexa:sql-statement-separator
INSERT INTO ibexa_content_version_new (id, contentobject_id, created, creator_id, initial_language_id, modified, status, user_id, version, workflow_event_pos, always_available)
SELECT id, contentobject_id, created, creator_id, initial_language_id, modified, status, user_id, version, workflow_event_pos, always_available FROM ibexa_content_version;
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
CREATE INDEX ibexa_content_version_initial_language ON ibexa_content_version (initial_language_id);
-- ibexa:sql-statement-separator
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
-- ibexa:sql-statement-separator
CREATE TABLE ibexa_content_type_name_new (
    content_type_id INTEGER DEFAULT 0 NOT NULL,
    content_type_status INTEGER DEFAULT 0 NOT NULL,
    language_id BIGINT DEFAULT 0 NOT NULL,
    language_locale VARCHAR(20) DEFAULT '' NOT NULL,
    name VARCHAR(255) DEFAULT '' NOT NULL,
    PRIMARY KEY(content_type_id, content_type_status, language_id),
    CONSTRAINT ibexa_content_type_name_language_fk FOREIGN KEY (language_id)
        REFERENCES ibexa_content_language (id) ON DELETE RESTRICT ON UPDATE CASCADE
);
-- ibexa:sql-statement-separator
INSERT INTO ibexa_content_type_name_new (content_type_id, content_type_status, language_id, language_locale, name)
SELECT content_type_id, content_type_status, language_id, language_locale, name FROM ibexa_content_type_name;
-- ibexa:sql-statement-separator
DROP TABLE ibexa_content_type_name;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_type_name_new RENAME TO ibexa_content_type_name;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_type_name_language ON ibexa_content_type_name (language_id);
-- ibexa:sql-statement-separator
CREATE TABLE ibexa_content_field_new (
    id INTEGER NOT NULL,
    version INTEGER DEFAULT 0 NOT NULL,
    attribute_original_id INTEGER DEFAULT 0,
    content_type_field_definition_id INTEGER DEFAULT 0 NOT NULL,
    contentobject_id INTEGER DEFAULT 0 NOT NULL,
    data_float DOUBLE PRECISION DEFAULT NULL,
    data_int INTEGER DEFAULT NULL,
    data_text CLOB DEFAULT NULL,
    data_type_string VARCHAR(50) DEFAULT '',
    language_code VARCHAR(20) DEFAULT '' NOT NULL,
    language_id BIGINT DEFAULT 0 NOT NULL,
    sort_key_int INTEGER DEFAULT 0 NOT NULL,
    sort_key_string VARCHAR(255) DEFAULT '' NOT NULL,
    PRIMARY KEY(id, version),
    CONSTRAINT ibexa_content_field_language_fk FOREIGN KEY (language_id)
        REFERENCES ibexa_content_language (id) ON DELETE RESTRICT ON UPDATE CASCADE
);
-- ibexa:sql-statement-separator
INSERT INTO ibexa_content_field_new (id, version, attribute_original_id, content_type_field_definition_id, contentobject_id, data_float, data_int, data_text, data_type_string, language_code, language_id, sort_key_int, sort_key_string)
SELECT id, version, attribute_original_id, content_type_field_definition_id, contentobject_id, data_float, data_int, data_text, data_type_string, language_code, language_id, sort_key_int, sort_key_string FROM ibexa_content_field;
-- ibexa:sql-statement-separator
DROP TABLE ibexa_content_field;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_field_new RENAME TO ibexa_content_field;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_field_co_id_ver ON ibexa_content_field (contentobject_id, version);
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_field_co_id_ver_lang_code ON ibexa_content_field (contentobject_id, version, language_code);
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_field_field_definition_id ON ibexa_content_field (content_type_field_definition_id);
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_field_language_code ON ibexa_content_field (language_code);
-- ibexa:sql-statement-separator
CREATE INDEX sort_key_int ON ibexa_content_field (sort_key_int);
-- ibexa:sql-statement-separator
CREATE INDEX sort_key_string ON ibexa_content_field (sort_key_string);
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_field_language ON ibexa_content_field (language_id);
-- ibexa:sql-statement-separator
CREATE TABLE ibexa_content_name_new (
    contentobject_id INTEGER DEFAULT 0 NOT NULL,
    content_version INTEGER DEFAULT 0 NOT NULL,
    content_translation VARCHAR(20) DEFAULT '' NOT NULL,
    language_id BIGINT DEFAULT 0 NOT NULL,
    name VARCHAR(255) DEFAULT NULL,
    real_translation VARCHAR(20) DEFAULT NULL,
    PRIMARY KEY(contentobject_id, content_version, content_translation),
    CONSTRAINT ibexa_content_name_language_fk FOREIGN KEY (language_id)
        REFERENCES ibexa_content_language (id) ON DELETE RESTRICT ON UPDATE CASCADE
);
-- ibexa:sql-statement-separator
INSERT INTO ibexa_content_name_new (contentobject_id, content_version, content_translation, language_id, name, real_translation)
SELECT contentobject_id, content_version, content_translation, language_id, name, real_translation FROM ibexa_content_name;
-- ibexa:sql-statement-separator
DROP TABLE ibexa_content_name;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_name_new RENAME TO ibexa_content_name;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_name_cov_id ON ibexa_content_name (content_version);
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_name_lang_id ON ibexa_content_name (language_id);
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_name_name ON ibexa_content_name (name);
-- ibexa:sql-statement-separator
CREATE TABLE ibexa_object_state_new (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    default_language_id BIGINT DEFAULT 0 NOT NULL,
    group_id INTEGER DEFAULT 0 NOT NULL,
    identifier VARCHAR(45) DEFAULT '' NOT NULL,
    priority INTEGER DEFAULT 0 NOT NULL,
    CONSTRAINT ibexa_object_state_default_language_fk FOREIGN KEY (default_language_id)
        REFERENCES ibexa_content_language (id) ON DELETE RESTRICT ON UPDATE CASCADE
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
CREATE INDEX ibexa_object_state_default_language ON ibexa_object_state (default_language_id);
-- ibexa:sql-statement-separator
CREATE TABLE ibexa_object_state_group_new (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    default_language_id BIGINT DEFAULT 0 NOT NULL,
    identifier VARCHAR(45) DEFAULT '' NOT NULL,
    CONSTRAINT ibexa_object_state_group_default_language_fk FOREIGN KEY (default_language_id)
        REFERENCES ibexa_content_language (id) ON DELETE RESTRICT ON UPDATE CASCADE
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
CREATE INDEX ibexa_object_state_group_default_language ON ibexa_object_state_group (default_language_id);
-- ibexa:sql-statement-separator
CREATE TABLE ibexa_object_state_language_new (
    contentobject_state_id INTEGER DEFAULT 0 NOT NULL,
    language_id BIGINT DEFAULT 0 NOT NULL,
    description CLOB NOT NULL,
    name VARCHAR(45) DEFAULT '' NOT NULL,
    PRIMARY KEY(contentobject_state_id, language_id),
    CONSTRAINT ibexa_object_state_language_language_fk FOREIGN KEY (language_id)
        REFERENCES ibexa_content_language (id) ON DELETE RESTRICT ON UPDATE CASCADE
);
-- ibexa:sql-statement-separator
INSERT INTO ibexa_object_state_language_new (contentobject_state_id, language_id, description, name)
SELECT contentobject_state_id, language_id, description, name FROM ibexa_object_state_language;
-- ibexa:sql-statement-separator
DROP TABLE ibexa_object_state_language;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_object_state_language_new RENAME TO ibexa_object_state_language;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_object_state_language_language ON ibexa_object_state_language (language_id);
-- ibexa:sql-statement-separator
CREATE TABLE ibexa_object_state_group_language_new (
    contentobject_state_group_id INTEGER DEFAULT 0 NOT NULL,
    real_language_id BIGINT DEFAULT 0 NOT NULL,
    description CLOB NOT NULL,
    language_id BIGINT DEFAULT 0 NOT NULL,
    name VARCHAR(45) DEFAULT '' NOT NULL,
    PRIMARY KEY(contentobject_state_group_id, real_language_id),
    CONSTRAINT ibexa_object_state_group_language_language_fk FOREIGN KEY (language_id)
        REFERENCES ibexa_content_language (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT ibexa_object_state_group_language_real_language_fk FOREIGN KEY (real_language_id)
        REFERENCES ibexa_content_language (id) ON DELETE RESTRICT ON UPDATE CASCADE
);
-- ibexa:sql-statement-separator
INSERT INTO ibexa_object_state_group_language_new (contentobject_state_group_id, real_language_id, description, language_id, name)
SELECT contentobject_state_group_id, real_language_id, description, language_id, name FROM ibexa_object_state_group_language;
-- ibexa:sql-statement-separator
DROP TABLE ibexa_object_state_group_language;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_object_state_group_language_new RENAME TO ibexa_object_state_group_language;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_object_state_group_language_language ON ibexa_object_state_group_language (language_id);
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_object_state_group_language_real_language ON ibexa_object_state_group_language (real_language_id);
-- ibexa:sql-statement-separator
CREATE TABLE ibexa_content_type_field_definition_ml_new (
    content_type_field_definition_id INTEGER NOT NULL,
    status INTEGER NOT NULL,
    language_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description CLOB DEFAULT NULL,
    data_text CLOB DEFAULT NULL,
    data_json CLOB DEFAULT NULL,
    PRIMARY KEY(content_type_field_definition_id, status, language_id),
    CONSTRAINT ibexa_content_type_field_definition_ml_language_fk FOREIGN KEY (language_id)
        REFERENCES ibexa_content_language (id) ON DELETE RESTRICT ON UPDATE CASCADE
);
-- ibexa:sql-statement-separator
INSERT INTO ibexa_content_type_field_definition_ml_new (content_type_field_definition_id, status, language_id, name, description, data_text, data_json)
SELECT content_type_field_definition_id, status, language_id, name, description, data_text, data_json FROM ibexa_content_type_field_definition_ml;
-- ibexa:sql-statement-separator
DROP TABLE ibexa_content_type_field_definition_ml;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_type_field_definition_ml_new RENAME TO ibexa_content_type_field_definition_ml;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_type_field_definition_ml_lang_fk ON ibexa_content_type_field_definition_ml (language_id);
