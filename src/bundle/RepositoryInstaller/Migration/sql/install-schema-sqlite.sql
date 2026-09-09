CREATE TABLE ezbinaryfile (
    contentobject_attribute_id INTEGER DEFAULT 0 NOT NULL,
    version INTEGER DEFAULT 0 NOT NULL,
    download_count INTEGER DEFAULT 0 NOT NULL,
    filename VARCHAR(255) DEFAULT '' NOT NULL,
    mime_type VARCHAR(255) DEFAULT '' NOT NULL,
    original_filename VARCHAR(255) DEFAULT '' NOT NULL,
    PRIMARY KEY(contentobject_attribute_id, version)
);
-- ibexa:sql-statement-separator
CREATE TABLE ezcobj_state (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    default_language_id BIGINT DEFAULT 0 NOT NULL,
    group_id INTEGER DEFAULT 0 NOT NULL,
    identifier VARCHAR(45) DEFAULT '' NOT NULL,
    language_mask BIGINT DEFAULT 0 NOT NULL,
    priority INTEGER DEFAULT 0 NOT NULL
);
-- ibexa:sql-statement-separator
CREATE INDEX ezcobj_state_priority ON ezcobj_state (priority);
-- ibexa:sql-statement-separator
CREATE INDEX ezcobj_state_lmask ON ezcobj_state (language_mask);
-- ibexa:sql-statement-separator
CREATE UNIQUE INDEX ezcobj_state_identifier ON ezcobj_state (group_id, identifier);
-- ibexa:sql-statement-separator
CREATE TABLE ezcobj_state_group (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    default_language_id BIGINT DEFAULT 0 NOT NULL,
    identifier VARCHAR(45) DEFAULT '' NOT NULL,
    language_mask BIGINT DEFAULT 0 NOT NULL
);
-- ibexa:sql-statement-separator
CREATE INDEX ezcobj_state_group_lmask ON ezcobj_state_group (language_mask);
-- ibexa:sql-statement-separator
CREATE UNIQUE INDEX ezcobj_state_group_identifier ON ezcobj_state_group (identifier);
-- ibexa:sql-statement-separator
CREATE TABLE ezcobj_state_group_language (
    contentobject_state_group_id INTEGER DEFAULT 0 NOT NULL,
    real_language_id BIGINT DEFAULT 0 NOT NULL,
    description CLOB NOT NULL,
    language_id BIGINT DEFAULT 0 NOT NULL,
    name VARCHAR(45) DEFAULT '' NOT NULL,
    PRIMARY KEY(contentobject_state_group_id, real_language_id)
);
-- ibexa:sql-statement-separator
CREATE TABLE ezcobj_state_language (
    contentobject_state_id INTEGER DEFAULT 0 NOT NULL,
    language_id BIGINT DEFAULT 0 NOT NULL,
    description CLOB NOT NULL,
    name VARCHAR(45) DEFAULT '' NOT NULL,
    PRIMARY KEY(contentobject_state_id, language_id)
);
-- ibexa:sql-statement-separator
CREATE TABLE ezcobj_state_link (
    contentobject_id INTEGER DEFAULT 0 NOT NULL,
    contentobject_state_id INTEGER DEFAULT 0 NOT NULL,
    PRIMARY KEY(contentobject_id, contentobject_state_id)
);
-- ibexa:sql-statement-separator
CREATE TABLE ezcontent_language (
    id BIGINT DEFAULT 0 NOT NULL,
    disabled INTEGER DEFAULT 0 NOT NULL,
    locale VARCHAR(20) DEFAULT '' NOT NULL,
    name VARCHAR(255) DEFAULT '' NOT NULL,
    PRIMARY KEY(id)
);
-- ibexa:sql-statement-separator
CREATE INDEX ezcontent_language_name ON ezcontent_language (name);
-- ibexa:sql-statement-separator
CREATE TABLE ezuser (
    contentobject_id INTEGER DEFAULT 0 NOT NULL,
    email VARCHAR(150) DEFAULT '' NOT NULL,
    login VARCHAR(150) DEFAULT '' NOT NULL,
    password_hash VARCHAR(255) DEFAULT NULL,
    password_hash_type INTEGER DEFAULT 1 NOT NULL,
    password_updated_at INTEGER DEFAULT NULL,
    PRIMARY KEY(contentobject_id)
);
-- ibexa:sql-statement-separator
CREATE UNIQUE INDEX ezuser_login ON ezuser (login);
-- ibexa:sql-statement-separator
CREATE TABLE ezcontentobject_tree (
    node_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    contentobject_id INTEGER DEFAULT NULL,
    contentobject_is_published INTEGER DEFAULT NULL,
    contentobject_version INTEGER DEFAULT NULL,
    depth INTEGER DEFAULT 0 NOT NULL,
    is_hidden INTEGER DEFAULT 0 NOT NULL,
    is_invisible INTEGER DEFAULT 0 NOT NULL,
    main_node_id INTEGER DEFAULT NULL,
    modified_subnode INTEGER DEFAULT 0,
    parent_node_id INTEGER DEFAULT 0 NOT NULL,
    path_identification_string CLOB DEFAULT NULL,
    path_string VARCHAR(255) DEFAULT '' NOT NULL,
    priority INTEGER DEFAULT 0 NOT NULL,
    remote_id VARCHAR(100) DEFAULT '' NOT NULL,
    sort_field INTEGER DEFAULT 1,
    sort_order INTEGER DEFAULT 1
);
-- ibexa:sql-statement-separator
CREATE INDEX ezcontentobject_tree_p_node_id ON ezcontentobject_tree (parent_node_id);
-- ibexa:sql-statement-separator
CREATE INDEX ezcontentobject_tree_path_ident ON ezcontentobject_tree (path_identification_string);
-- ibexa:sql-statement-separator
CREATE INDEX ezcontentobject_tree_contentobject_id_path_string ON ezcontentobject_tree (path_string, contentobject_id);
-- ibexa:sql-statement-separator
CREATE INDEX ezcontentobject_tree_co_id ON ezcontentobject_tree (contentobject_id);
-- ibexa:sql-statement-separator
CREATE INDEX ezcontentobject_tree_depth ON ezcontentobject_tree (depth);
-- ibexa:sql-statement-separator
CREATE INDEX ezcontentobject_tree_path ON ezcontentobject_tree (path_string);
-- ibexa:sql-statement-separator
CREATE INDEX modified_subnode ON ezcontentobject_tree (modified_subnode);
-- ibexa:sql-statement-separator
CREATE INDEX ezcontentobject_tree_remote_id ON ezcontentobject_tree (remote_id);
-- ibexa:sql-statement-separator
CREATE TABLE ezcontentbrowsebookmark (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    node_id INTEGER DEFAULT 0 NOT NULL,
    user_id INTEGER DEFAULT 0 NOT NULL,
    name VARCHAR(255) DEFAULT '' NOT NULL,
    CONSTRAINT ezcontentbrowsebookmark_location_fk FOREIGN KEY (node_id) REFERENCES ezcontentobject_tree (node_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE,
    CONSTRAINT ezcontentbrowsebookmark_user_fk FOREIGN KEY (user_id) REFERENCES ezuser (contentobject_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
);
-- ibexa:sql-statement-separator
CREATE INDEX ezcontentbrowsebookmark_location ON ezcontentbrowsebookmark (node_id);
-- ibexa:sql-statement-separator
CREATE INDEX ezcontentbrowsebookmark_user ON ezcontentbrowsebookmark (user_id);
-- ibexa:sql-statement-separator
CREATE INDEX ezcontentbrowsebookmark_user_location ON ezcontentbrowsebookmark (user_id, node_id);
-- ibexa:sql-statement-separator
CREATE TABLE ezcontentclass (
    id INTEGER NOT NULL,
    version INTEGER DEFAULT 0 NOT NULL,
    always_available INTEGER DEFAULT 0 NOT NULL,
    contentobject_name VARCHAR(255) DEFAULT NULL,
    created INTEGER DEFAULT 0 NOT NULL,
    creator_id INTEGER DEFAULT 0 NOT NULL,
    identifier VARCHAR(50) DEFAULT '' NOT NULL,
    initial_language_id BIGINT DEFAULT 0 NOT NULL,
    is_container INTEGER DEFAULT 0 NOT NULL,
    language_mask BIGINT DEFAULT 0 NOT NULL,
    modified INTEGER DEFAULT 0 NOT NULL,
    modifier_id INTEGER DEFAULT 0 NOT NULL,
    remote_id VARCHAR(100) DEFAULT '' NOT NULL,
    serialized_description_list CLOB DEFAULT NULL,
    serialized_name_list CLOB DEFAULT NULL,
    sort_field INTEGER DEFAULT 1 NOT NULL,
    sort_order INTEGER DEFAULT 1 NOT NULL,
    url_alias_name VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY(id, version)
);
-- ibexa:sql-statement-separator
CREATE INDEX ezcontentclass_version ON ezcontentclass (version);
-- ibexa:sql-statement-separator
CREATE INDEX ezcontentclass_identifier ON ezcontentclass (identifier, version);
-- ibexa:sql-statement-separator
CREATE TABLE ezcontentclass_attribute (
    id INTEGER NOT NULL,
    version INTEGER DEFAULT 0 NOT NULL,
    can_translate INTEGER DEFAULT 1,
    category VARCHAR(25) DEFAULT '' NOT NULL,
    contentclass_id INTEGER DEFAULT 0 NOT NULL,
    data_float1 DOUBLE PRECISION DEFAULT NULL,
    data_float2 DOUBLE PRECISION DEFAULT NULL,
    data_float3 DOUBLE PRECISION DEFAULT NULL,
    data_float4 DOUBLE PRECISION DEFAULT NULL,
    data_int1 INTEGER DEFAULT NULL,
    data_int2 INTEGER DEFAULT NULL,
    data_int3 INTEGER DEFAULT NULL,
    data_int4 INTEGER DEFAULT NULL,
    data_text1 VARCHAR(255) DEFAULT NULL,
    data_text2 VARCHAR(50) DEFAULT NULL,
    data_text3 VARCHAR(50) DEFAULT NULL,
    data_text4 VARCHAR(255) DEFAULT NULL,
    data_text5 CLOB DEFAULT NULL,
    data_type_string VARCHAR(50) DEFAULT '' NOT NULL,
    identifier VARCHAR(50) DEFAULT '' NOT NULL,
    is_information_collector INTEGER DEFAULT 0 NOT NULL,
    is_required INTEGER DEFAULT 0 NOT NULL,
    is_searchable INTEGER DEFAULT 0 NOT NULL,
    is_thumbnail BOOLEAN DEFAULT '0' NOT NULL,
    placement INTEGER DEFAULT 0 NOT NULL,
    serialized_data_text CLOB DEFAULT NULL,
    serialized_description_list CLOB DEFAULT NULL,
    serialized_name_list CLOB NOT NULL,
    PRIMARY KEY(id, version)
);
-- ibexa:sql-statement-separator
CREATE INDEX ezcontentclass_attr_ccid ON ezcontentclass_attribute (contentclass_id);
-- ibexa:sql-statement-separator
CREATE INDEX ezcontentclass_attr_dts ON ezcontentclass_attribute (data_type_string);
-- ibexa:sql-statement-separator
CREATE TABLE ezcontentclass_attribute_ml (
    contentclass_attribute_id INTEGER NOT NULL,
    version INTEGER NOT NULL,
    language_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description CLOB DEFAULT NULL,
    data_text CLOB DEFAULT NULL,
    data_json CLOB DEFAULT NULL,
    PRIMARY KEY(contentclass_attribute_id, version, language_id),
    CONSTRAINT ezcontentclass_attribute_ml_lang_fk FOREIGN KEY (language_id) REFERENCES ezcontent_language (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
);
-- ibexa:sql-statement-separator
CREATE INDEX ezcontentclass_attribute_ml_lang_fk ON ezcontentclass_attribute_ml (language_id);
-- ibexa:sql-statement-separator
CREATE TABLE ezcontentclass_classgroup (
    contentclass_id INTEGER DEFAULT 0 NOT NULL,
    contentclass_version INTEGER DEFAULT 0 NOT NULL,
    group_id INTEGER DEFAULT 0 NOT NULL,
    group_name VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY(contentclass_id, contentclass_version, group_id)
);
-- ibexa:sql-statement-separator
CREATE TABLE ezcontentclass_name (
    contentclass_id INTEGER DEFAULT 0 NOT NULL,
    contentclass_version INTEGER DEFAULT 0 NOT NULL,
    language_id BIGINT DEFAULT 0 NOT NULL,
    language_locale VARCHAR(20) DEFAULT '' NOT NULL,
    name VARCHAR(255) DEFAULT '' NOT NULL,
    PRIMARY KEY(contentclass_id, contentclass_version, language_id)
);
-- ibexa:sql-statement-separator
CREATE TABLE ezcontentclassgroup (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    created INTEGER DEFAULT 0 NOT NULL,
    creator_id INTEGER DEFAULT 0 NOT NULL,
    modified INTEGER DEFAULT 0 NOT NULL,
    modifier_id INTEGER DEFAULT 0 NOT NULL,
    name VARCHAR(255) DEFAULT NULL,
    is_system BOOLEAN DEFAULT '0' NOT NULL
);
-- ibexa:sql-statement-separator
CREATE TABLE ezcontentobject (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    contentclass_id INTEGER DEFAULT 0 NOT NULL,
    current_version INTEGER DEFAULT NULL,
    initial_language_id BIGINT DEFAULT 0 NOT NULL,
    language_mask BIGINT DEFAULT 0 NOT NULL,
    modified INTEGER DEFAULT 0 NOT NULL,
    name VARCHAR(255) DEFAULT NULL,
    owner_id INTEGER DEFAULT 0 NOT NULL,
    published INTEGER DEFAULT 0 NOT NULL,
    remote_id VARCHAR(100) DEFAULT NULL,
    section_id INTEGER DEFAULT 0 NOT NULL,
    status INTEGER DEFAULT 0,
    is_hidden BOOLEAN DEFAULT '0' NOT NULL
);
-- ibexa:sql-statement-separator
CREATE INDEX ezcontentobject_classid ON ezcontentobject (contentclass_id);
-- ibexa:sql-statement-separator
CREATE INDEX ezcontentobject_lmask ON ezcontentobject (language_mask);
-- ibexa:sql-statement-separator
CREATE INDEX ezcontentobject_pub ON ezcontentobject (published);
-- ibexa:sql-statement-separator
CREATE INDEX ezcontentobject_section ON ezcontentobject (section_id);
-- ibexa:sql-statement-separator
CREATE INDEX ezcontentobject_currentversion ON ezcontentobject (current_version);
-- ibexa:sql-statement-separator
CREATE INDEX ezcontentobject_owner ON ezcontentobject (owner_id);
-- ibexa:sql-statement-separator
CREATE INDEX ezcontentobject_status ON ezcontentobject (status);
-- ibexa:sql-statement-separator
CREATE UNIQUE INDEX ezcontentobject_remote_id ON ezcontentobject (remote_id);
-- ibexa:sql-statement-separator
CREATE TABLE ezcontentobject_attribute (
    id INTEGER NOT NULL,
    version INTEGER DEFAULT 0 NOT NULL,
    attribute_original_id INTEGER DEFAULT 0,
    contentclassattribute_id INTEGER DEFAULT 0 NOT NULL,
    contentobject_id INTEGER DEFAULT 0 NOT NULL,
    data_float DOUBLE PRECISION DEFAULT NULL,
    data_int INTEGER DEFAULT NULL,
    data_text CLOB DEFAULT NULL,
    data_type_string VARCHAR(50) DEFAULT '',
    language_code VARCHAR(20) DEFAULT '' NOT NULL,
    language_id BIGINT DEFAULT 0 NOT NULL,
    sort_key_int INTEGER DEFAULT 0 NOT NULL,
    sort_key_string VARCHAR(255) DEFAULT '' NOT NULL,
    PRIMARY KEY(id, version)
);
-- ibexa:sql-statement-separator
CREATE INDEX ezcontentobject_attribute_co_id_ver_lang_code ON ezcontentobject_attribute (contentobject_id, version, language_code);
-- ibexa:sql-statement-separator
CREATE INDEX ezcontentobject_classattr_id ON ezcontentobject_attribute (contentclassattribute_id);
-- ibexa:sql-statement-separator
CREATE INDEX sort_key_string ON ezcontentobject_attribute (sort_key_string);
-- ibexa:sql-statement-separator
CREATE INDEX ezcontentobject_attribute_language_code ON ezcontentobject_attribute (language_code);
-- ibexa:sql-statement-separator
CREATE INDEX sort_key_int ON ezcontentobject_attribute (sort_key_int);
-- ibexa:sql-statement-separator
CREATE INDEX ezcontentobject_attribute_co_id_ver ON ezcontentobject_attribute (contentobject_id, version);
-- ibexa:sql-statement-separator
CREATE TABLE ezcontentobject_link (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    contentclassattribute_id INTEGER DEFAULT 0 NOT NULL,
    from_contentobject_id INTEGER DEFAULT 0 NOT NULL,
    from_contentobject_version INTEGER DEFAULT 0 NOT NULL,
    relation_type INTEGER DEFAULT 1 NOT NULL,
    to_contentobject_id INTEGER DEFAULT 0 NOT NULL
);
-- ibexa:sql-statement-separator
CREATE INDEX ezco_link_to_co_id ON ezcontentobject_link (to_contentobject_id);
-- ibexa:sql-statement-separator
CREATE INDEX ezco_link_from ON ezcontentobject_link (from_contentobject_id, from_contentobject_version, contentclassattribute_id);
-- ibexa:sql-statement-separator
CREATE INDEX ezco_link_cca_id ON ezcontentobject_link (contentclassattribute_id);
-- ibexa:sql-statement-separator
CREATE TABLE ezcontentobject_name (
    contentobject_id INTEGER DEFAULT 0 NOT NULL,
    content_version INTEGER DEFAULT 0 NOT NULL,
    content_translation VARCHAR(20) DEFAULT '' NOT NULL,
    language_id BIGINT DEFAULT 0 NOT NULL,
    name VARCHAR(255) DEFAULT NULL,
    real_translation VARCHAR(20) DEFAULT NULL,
    PRIMARY KEY(contentobject_id, content_version, content_translation)
);
-- ibexa:sql-statement-separator
CREATE INDEX ezcontentobject_name_lang_id ON ezcontentobject_name (language_id);
-- ibexa:sql-statement-separator
CREATE INDEX ezcontentobject_name_cov_id ON ezcontentobject_name (content_version);
-- ibexa:sql-statement-separator
CREATE INDEX ezcontentobject_name_name ON ezcontentobject_name (name);
-- ibexa:sql-statement-separator
CREATE TABLE ezcontentobject_trash (
    node_id INTEGER DEFAULT 0 NOT NULL,
    contentobject_id INTEGER DEFAULT NULL,
    contentobject_version INTEGER DEFAULT NULL,
    depth INTEGER DEFAULT 0 NOT NULL,
    is_hidden INTEGER DEFAULT 0 NOT NULL,
    is_invisible INTEGER DEFAULT 0 NOT NULL,
    main_node_id INTEGER DEFAULT NULL,
    modified_subnode INTEGER DEFAULT 0,
    parent_node_id INTEGER DEFAULT 0 NOT NULL,
    path_identification_string CLOB DEFAULT NULL,
    path_string VARCHAR(255) DEFAULT '' NOT NULL,
    priority INTEGER DEFAULT 0 NOT NULL,
    remote_id VARCHAR(100) DEFAULT '' NOT NULL,
    sort_field INTEGER DEFAULT 1,
    sort_order INTEGER DEFAULT 1,
    trashed INTEGER DEFAULT 0 NOT NULL,
    PRIMARY KEY(node_id)
);
-- ibexa:sql-statement-separator
CREATE INDEX ezcobj_trash_depth ON ezcontentobject_trash (depth);
-- ibexa:sql-statement-separator
CREATE INDEX ezcobj_trash_p_node_id ON ezcontentobject_trash (parent_node_id);
-- ibexa:sql-statement-separator
CREATE INDEX ezcobj_trash_path_ident ON ezcontentobject_trash (path_identification_string);
-- ibexa:sql-statement-separator
CREATE INDEX ezcobj_trash_co_id ON ezcontentobject_trash (contentobject_id);
-- ibexa:sql-statement-separator
CREATE INDEX ezcobj_trash_modified_subnode ON ezcontentobject_trash (modified_subnode);
-- ibexa:sql-statement-separator
CREATE INDEX ezcobj_trash_path ON ezcontentobject_trash (path_string);
-- ibexa:sql-statement-separator
CREATE TABLE ezcontentobject_version (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    contentobject_id INTEGER DEFAULT NULL,
    created INTEGER DEFAULT 0 NOT NULL,
    creator_id INTEGER DEFAULT 0 NOT NULL,
    initial_language_id BIGINT DEFAULT 0 NOT NULL,
    language_mask BIGINT DEFAULT 0 NOT NULL,
    modified INTEGER DEFAULT 0 NOT NULL,
    status INTEGER DEFAULT 0 NOT NULL,
    user_id INTEGER DEFAULT 0 NOT NULL,
    version INTEGER DEFAULT 0 NOT NULL,
    workflow_event_pos INTEGER DEFAULT 0
);
-- ibexa:sql-statement-separator
CREATE INDEX ezcobj_version_status ON ezcontentobject_version (status);
-- ibexa:sql-statement-separator
CREATE INDEX idx_object_version_objver ON ezcontentobject_version (contentobject_id, version);
-- ibexa:sql-statement-separator
CREATE INDEX ezcontobj_version_obj_status ON ezcontentobject_version (contentobject_id, status);
-- ibexa:sql-statement-separator
CREATE INDEX ezcobj_version_creator_id ON ezcontentobject_version (creator_id);
-- ibexa:sql-statement-separator
CREATE TABLE ezdfsfile (
    name_hash VARCHAR(34) DEFAULT '' NOT NULL,
    name CLOB NOT NULL,
    name_trunk CLOB NOT NULL,
    datatype VARCHAR(255) DEFAULT 'application/octet-stream' NOT NULL,
    scope VARCHAR(25) DEFAULT '' NOT NULL,
    size BIGINT UNSIGNED DEFAULT 0 NOT NULL,
    mtime INTEGER DEFAULT 0 NOT NULL,
    expired BOOLEAN DEFAULT '0' NOT NULL,
    status BOOLEAN DEFAULT '0' NOT NULL,
    PRIMARY KEY(name_hash)
);
-- ibexa:sql-statement-separator
CREATE INDEX ezdfsfile_name_trunk ON ezdfsfile (name_trunk);
-- ibexa:sql-statement-separator
CREATE INDEX ezdfsfile_expired_name ON ezdfsfile (expired, name);
-- ibexa:sql-statement-separator
CREATE INDEX ezdfsfile_name ON ezdfsfile (name);
-- ibexa:sql-statement-separator
CREATE INDEX ezdfsfile_mtime ON ezdfsfile (mtime);
-- ibexa:sql-statement-separator
CREATE TABLE ezgmaplocation (
    contentobject_attribute_id INTEGER DEFAULT 0 NOT NULL,
    contentobject_version INTEGER DEFAULT 0 NOT NULL,
    latitude DOUBLE PRECISION DEFAULT '0' NOT NULL,
    longitude DOUBLE PRECISION DEFAULT '0' NOT NULL,
    address VARCHAR(150) DEFAULT NULL,
    PRIMARY KEY(contentobject_attribute_id, contentobject_version)
);
-- ibexa:sql-statement-separator
CREATE INDEX latitude_longitude_key ON ezgmaplocation (latitude, longitude);
-- ibexa:sql-statement-separator
CREATE TABLE ezimagefile (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    contentobject_attribute_id INTEGER DEFAULT 0 NOT NULL,
    filepath CLOB NOT NULL
);
-- ibexa:sql-statement-separator
CREATE INDEX ezimagefile_file ON ezimagefile (filepath);
-- ibexa:sql-statement-separator
CREATE INDEX ezimagefile_coid ON ezimagefile (contentobject_attribute_id);
-- ibexa:sql-statement-separator
CREATE TABLE ezkeyword (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    class_id INTEGER DEFAULT 0 NOT NULL,
    keyword VARCHAR(255) DEFAULT NULL
);
-- ibexa:sql-statement-separator
CREATE INDEX ezkeyword_keyword ON ezkeyword (keyword);
-- ibexa:sql-statement-separator
CREATE TABLE ezkeyword_attribute_link (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    keyword_id INTEGER DEFAULT 0 NOT NULL,
    objectattribute_id INTEGER DEFAULT 0 NOT NULL,
    version INTEGER DEFAULT 0 NOT NULL
);
-- ibexa:sql-statement-separator
CREATE INDEX ezkeyword_attr_link_oaid ON ezkeyword_attribute_link (objectattribute_id);
-- ibexa:sql-statement-separator
CREATE INDEX ezkeyword_attr_link_kid_oaid ON ezkeyword_attribute_link (keyword_id, objectattribute_id);
-- ibexa:sql-statement-separator
CREATE INDEX ezkeyword_attr_link_oaid_ver ON ezkeyword_attribute_link (objectattribute_id, version);
-- ibexa:sql-statement-separator
CREATE TABLE ezmedia (
    contentobject_attribute_id INTEGER DEFAULT 0 NOT NULL,
    version INTEGER DEFAULT 0 NOT NULL,
    controls VARCHAR(50) DEFAULT NULL,
    filename VARCHAR(255) DEFAULT '' NOT NULL,
    has_controller INTEGER DEFAULT 0,
    height INTEGER DEFAULT NULL,
    is_autoplay INTEGER DEFAULT 0,
    is_loop INTEGER DEFAULT 0,
    mime_type VARCHAR(50) DEFAULT '' NOT NULL,
    original_filename VARCHAR(255) DEFAULT '' NOT NULL,
    pluginspage VARCHAR(255) DEFAULT NULL,
    quality VARCHAR(50) DEFAULT NULL,
    width INTEGER DEFAULT NULL,
    PRIMARY KEY(contentobject_attribute_id, version)
);
-- ibexa:sql-statement-separator
CREATE TABLE eznode_assignment (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    contentobject_id INTEGER DEFAULT NULL,
    contentobject_version INTEGER DEFAULT NULL,
    from_node_id INTEGER DEFAULT 0,
    is_main INTEGER DEFAULT 0 NOT NULL,
    op_code INTEGER DEFAULT 0 NOT NULL,
    parent_node INTEGER DEFAULT NULL,
    parent_remote_id VARCHAR(100) DEFAULT '' NOT NULL,
    remote_id VARCHAR(100) DEFAULT '0' NOT NULL,
    sort_field INTEGER DEFAULT 1,
    sort_order INTEGER DEFAULT 1,
    priority INTEGER DEFAULT 0 NOT NULL,
    is_hidden INTEGER DEFAULT 0 NOT NULL
);
-- ibexa:sql-statement-separator
CREATE INDEX eznode_assignment_is_main ON eznode_assignment (is_main);
-- ibexa:sql-statement-separator
CREATE INDEX eznode_assignment_coid_cov ON eznode_assignment (contentobject_id, contentobject_version);
-- ibexa:sql-statement-separator
CREATE INDEX eznode_assignment_parent_node ON eznode_assignment (parent_node);
-- ibexa:sql-statement-separator
CREATE INDEX eznode_assignment_co_version ON eznode_assignment (contentobject_version);
-- ibexa:sql-statement-separator
CREATE TABLE eznotification (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    owner_id INTEGER DEFAULT 0 NOT NULL,
    is_pending BOOLEAN DEFAULT '1' NOT NULL,
    type VARCHAR(128) DEFAULT '' NOT NULL,
    created INTEGER DEFAULT 0 NOT NULL,
    data CLOB DEFAULT NULL
);
-- ibexa:sql-statement-separator
CREATE INDEX eznotification_owner_is_pending ON eznotification (owner_id, is_pending);
-- ibexa:sql-statement-separator
CREATE INDEX eznotification_owner ON eznotification (owner_id);
-- ibexa:sql-statement-separator
CREATE TABLE ezpackage (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    install_date INTEGER DEFAULT 0 NOT NULL,
    name VARCHAR(100) DEFAULT '' NOT NULL,
    version VARCHAR(30) DEFAULT '0' NOT NULL
);
-- ibexa:sql-statement-separator
CREATE TABLE ezpolicy (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    function_name VARCHAR(255) DEFAULT NULL,
    module_name VARCHAR(255) DEFAULT NULL,
    original_id INTEGER DEFAULT 0 NOT NULL,
    role_id INTEGER DEFAULT NULL
);
-- ibexa:sql-statement-separator
CREATE INDEX ezpolicy_role_id ON ezpolicy (role_id);
-- ibexa:sql-statement-separator
CREATE INDEX ezpolicy_original_id ON ezpolicy (original_id);
-- ibexa:sql-statement-separator
CREATE TABLE ezpolicy_limitation (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    identifier VARCHAR(255) DEFAULT '' NOT NULL,
    policy_id INTEGER DEFAULT NULL
);
-- ibexa:sql-statement-separator
CREATE INDEX policy_id ON ezpolicy_limitation (policy_id);
-- ibexa:sql-statement-separator
CREATE TABLE ezpolicy_limitation_value (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    limitation_id INTEGER DEFAULT NULL,
    value VARCHAR(255) DEFAULT NULL
);
-- ibexa:sql-statement-separator
CREATE INDEX ezpolicy_limit_value_limit_id ON ezpolicy_limitation_value (limitation_id);
-- ibexa:sql-statement-separator
CREATE INDEX ezpolicy_limitation_value_val ON ezpolicy_limitation_value (value);
-- ibexa:sql-statement-separator
CREATE TABLE ezpreferences (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    name VARCHAR(100) DEFAULT NULL,
    user_id INTEGER DEFAULT 0 NOT NULL,
    value CLOB DEFAULT NULL
);
-- ibexa:sql-statement-separator
CREATE INDEX ezpreferences_user_id_idx ON ezpreferences (user_id, name);
-- ibexa:sql-statement-separator
CREATE INDEX ezpreferences_name ON ezpreferences (name);
-- ibexa:sql-statement-separator
CREATE TABLE ezrole (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    is_new INTEGER DEFAULT 0 NOT NULL,
    name VARCHAR(255) DEFAULT '' NOT NULL,
    value CHAR(1) DEFAULT NULL,
    version INTEGER DEFAULT 0
);
-- ibexa:sql-statement-separator
CREATE TABLE ezsearch_object_word_link (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    contentclass_attribute_id INTEGER DEFAULT 0 NOT NULL,
    contentclass_id INTEGER DEFAULT 0 NOT NULL,
    contentobject_id INTEGER DEFAULT 0 NOT NULL,
    frequency DOUBLE PRECISION DEFAULT '0' NOT NULL,
    identifier VARCHAR(255) DEFAULT '' NOT NULL,
    integer_value INTEGER DEFAULT 0 NOT NULL,
    next_word_id INTEGER DEFAULT 0 NOT NULL,
    placement INTEGER DEFAULT 0 NOT NULL,
    prev_word_id INTEGER DEFAULT 0 NOT NULL,
    published INTEGER DEFAULT 0 NOT NULL,
    section_id INTEGER DEFAULT 0 NOT NULL,
    word_id INTEGER DEFAULT 0 NOT NULL,
    language_mask BIGINT DEFAULT 0 NOT NULL
);
-- ibexa:sql-statement-separator
CREATE INDEX ezsearch_object_word_link_object ON ezsearch_object_word_link (contentobject_id);
-- ibexa:sql-statement-separator
CREATE INDEX ezsearch_object_word_link_identifier ON ezsearch_object_word_link (identifier);
-- ibexa:sql-statement-separator
CREATE INDEX ezsearch_object_word_link_integer_value ON ezsearch_object_word_link (integer_value);
-- ibexa:sql-statement-separator
CREATE INDEX ezsearch_object_word_link_word ON ezsearch_object_word_link (word_id);
-- ibexa:sql-statement-separator
CREATE INDEX ezsearch_object_word_link_frequency ON ezsearch_object_word_link (frequency);
-- ibexa:sql-statement-separator
CREATE TABLE ezsearch_word (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    object_count INTEGER DEFAULT 0 NOT NULL,
    word VARCHAR(150) DEFAULT NULL
);
-- ibexa:sql-statement-separator
CREATE INDEX ezsearch_word_word_i ON ezsearch_word (word);
-- ibexa:sql-statement-separator
CREATE INDEX ezsearch_word_obj_count ON ezsearch_word (object_count);
-- ibexa:sql-statement-separator
CREATE TABLE ezsection (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    identifier VARCHAR(255) DEFAULT NULL,
    locale VARCHAR(255) DEFAULT NULL,
    name VARCHAR(255) DEFAULT NULL,
    navigation_part_identifier VARCHAR(100) DEFAULT 'ezcontentnavigationpart'
);
-- ibexa:sql-statement-separator
CREATE TABLE ezsite_data (
    name VARCHAR(60) DEFAULT '' NOT NULL,
    value CLOB NOT NULL,
    PRIMARY KEY(name)
);
-- ibexa:sql-statement-separator
CREATE TABLE ezurl (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    created INTEGER DEFAULT 0 NOT NULL,
    is_valid INTEGER DEFAULT 1 NOT NULL,
    last_checked INTEGER DEFAULT 0 NOT NULL,
    modified INTEGER DEFAULT 0 NOT NULL,
    original_url_md5 VARCHAR(32) DEFAULT '' NOT NULL,
    url CLOB DEFAULT NULL
);
-- ibexa:sql-statement-separator
CREATE INDEX ezurl_url ON ezurl (url);
-- ibexa:sql-statement-separator
CREATE TABLE ezurl_object_link (
    contentobject_attribute_id INTEGER DEFAULT 0 NOT NULL,
    contentobject_attribute_version INTEGER DEFAULT 0 NOT NULL,
    url_id INTEGER DEFAULT 0 NOT NULL
);
-- ibexa:sql-statement-separator
CREATE INDEX ezurl_ol_coa_id ON ezurl_object_link (contentobject_attribute_id);
-- ibexa:sql-statement-separator
CREATE INDEX ezurl_ol_url_id ON ezurl_object_link (url_id);
-- ibexa:sql-statement-separator
CREATE INDEX ezurl_ol_coa_version ON ezurl_object_link (contentobject_attribute_version);
-- ibexa:sql-statement-separator
CREATE INDEX ezurl_ol_coa_id_cav ON ezurl_object_link (contentobject_attribute_id, contentobject_attribute_version);
-- ibexa:sql-statement-separator
CREATE TABLE ezurlalias (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    destination_url CLOB NOT NULL,
    forward_to_id INTEGER DEFAULT 0 NOT NULL,
    is_imported INTEGER DEFAULT 0 NOT NULL,
    is_internal INTEGER DEFAULT 1 NOT NULL,
    is_wildcard INTEGER DEFAULT 0 NOT NULL,
    source_md5 VARCHAR(32) DEFAULT NULL,
    source_url CLOB NOT NULL
);
-- ibexa:sql-statement-separator
CREATE INDEX ezurlalias_source_md5 ON ezurlalias (source_md5);
-- ibexa:sql-statement-separator
CREATE INDEX ezurlalias_wcard_fwd ON ezurlalias (is_wildcard, forward_to_id);
-- ibexa:sql-statement-separator
CREATE INDEX ezurlalias_forward_to_id ON ezurlalias (forward_to_id);
-- ibexa:sql-statement-separator
CREATE INDEX ezurlalias_imp_wcard_fwd ON ezurlalias (is_imported, is_wildcard, forward_to_id);
-- ibexa:sql-statement-separator
CREATE INDEX ezurlalias_source_url ON ezurlalias (source_url);
-- ibexa:sql-statement-separator
CREATE INDEX ezurlalias_desturl ON ezurlalias (destination_url);
-- ibexa:sql-statement-separator
CREATE TABLE ezurlalias_ml (
    parent INTEGER DEFAULT 0 NOT NULL,
    text_md5 VARCHAR(32) DEFAULT '' NOT NULL,
    "action" CLOB NOT NULL,
    action_type VARCHAR(32) DEFAULT '' NOT NULL,
    alias_redirects INTEGER DEFAULT 1 NOT NULL,
    id INTEGER DEFAULT 0 NOT NULL,
    is_alias INTEGER DEFAULT 0 NOT NULL,
    is_original INTEGER DEFAULT 0 NOT NULL,
    lang_mask BIGINT DEFAULT 0 NOT NULL,
    link INTEGER DEFAULT 0 NOT NULL,
    text CLOB NOT NULL,
    PRIMARY KEY(parent, text_md5)
);
-- ibexa:sql-statement-separator
CREATE INDEX ezurlalias_ml_actt_org_al ON ezurlalias_ml (action_type, is_original, is_alias);
-- ibexa:sql-statement-separator
CREATE INDEX ezurlalias_ml_text_lang ON ezurlalias_ml (text, lang_mask, parent);
-- ibexa:sql-statement-separator
CREATE INDEX ezurlalias_ml_par_act_id_lnk ON ezurlalias_ml ("action", id, link, parent);
-- ibexa:sql-statement-separator
CREATE INDEX ezurlalias_ml_par_lnk_txt ON ezurlalias_ml (parent, text, link);
-- ibexa:sql-statement-separator
CREATE INDEX ezurlalias_ml_act_org ON ezurlalias_ml ("action", is_original);
-- ibexa:sql-statement-separator
CREATE INDEX ezurlalias_ml_text ON ezurlalias_ml (text, id, link);
-- ibexa:sql-statement-separator
CREATE INDEX ezurlalias_ml_link ON ezurlalias_ml (link);
-- ibexa:sql-statement-separator
CREATE INDEX ezurlalias_ml_id ON ezurlalias_ml (id);
-- ibexa:sql-statement-separator
CREATE TABLE ezurlalias_ml_incr (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL
);
-- ibexa:sql-statement-separator
CREATE TABLE ezurlwildcard (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    destination_url CLOB NOT NULL,
    source_url CLOB NOT NULL,
    type INTEGER DEFAULT 0 NOT NULL
);
-- ibexa:sql-statement-separator
CREATE TABLE ezuser_accountkey (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    hash_key VARCHAR(32) DEFAULT '' NOT NULL,
    time INTEGER DEFAULT 0 NOT NULL,
    user_id INTEGER DEFAULT 0 NOT NULL
);
-- ibexa:sql-statement-separator
CREATE INDEX hash_key ON ezuser_accountkey (hash_key);
-- ibexa:sql-statement-separator
CREATE TABLE ezuser_role (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    contentobject_id INTEGER DEFAULT NULL,
    limit_identifier VARCHAR(255) DEFAULT '',
    limit_value VARCHAR(255) DEFAULT '',
    role_id INTEGER DEFAULT NULL
);
-- ibexa:sql-statement-separator
CREATE INDEX ezuser_role_role_id ON ezuser_role (role_id);
-- ibexa:sql-statement-separator
CREATE INDEX ezuser_role_contentobject_id ON ezuser_role (contentobject_id);
-- ibexa:sql-statement-separator
CREATE TABLE ezuser_setting (
    user_id INTEGER DEFAULT 0 NOT NULL,
    is_enabled INTEGER DEFAULT 0 NOT NULL,
    max_login INTEGER DEFAULT NULL,
    PRIMARY KEY(user_id)
);
-- ibexa:sql-statement-separator
CREATE TABLE ibexa_setting (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "group" VARCHAR(128) NOT NULL,
    identifier VARCHAR(128) NOT NULL,
    value CLOB NOT NULL --(DC2Type:json)
);
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_setting_id ON ibexa_setting (id);
-- ibexa:sql-statement-separator
CREATE UNIQUE INDEX ibexa_setting_group_identifier ON ibexa_setting ("group", identifier);
-- ibexa:sql-statement-separator
CREATE TABLE ibexa_token_type (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    identifier VARCHAR(64) NOT NULL
);
-- ibexa:sql-statement-separator
CREATE UNIQUE INDEX ibexa_token_type_unique ON ibexa_token_type (identifier);
-- ibexa:sql-statement-separator
CREATE TABLE ibexa_token (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    type_id INTEGER NOT NULL,
    token VARCHAR(255) NOT NULL,
    identifier VARCHAR(128) DEFAULT NULL,
    created INTEGER DEFAULT 0 NOT NULL,
    expires INTEGER DEFAULT 0 NOT NULL,
    revoked BOOLEAN DEFAULT '0' NOT NULL,
    CONSTRAINT ibexa_token_type_id_fk FOREIGN KEY (type_id) REFERENCES ibexa_token_type (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
);
-- ibexa:sql-statement-separator
CREATE INDEX IDX_B5412887C54C8C93 ON ibexa_token (type_id);
-- ibexa:sql-statement-separator
CREATE UNIQUE INDEX ibexa_token_unique ON ibexa_token (token, identifier, type_id);
