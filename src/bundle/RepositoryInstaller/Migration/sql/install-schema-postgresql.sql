CREATE TABLE ezbinaryfile (
    contentobject_attribute_id INT DEFAULT 0 NOT NULL,
    version INT DEFAULT 0 NOT NULL,
    download_count INT DEFAULT 0 NOT NULL,
    filename VARCHAR(255) DEFAULT '' NOT NULL,
    mime_type VARCHAR(255) DEFAULT '' NOT NULL,
    original_filename VARCHAR(255) DEFAULT '' NOT NULL,
    PRIMARY KEY(contentobject_attribute_id, version)
);
-- ibexa:sql-statement-separator
CREATE TABLE ezcobj_state (
    id SERIAL NOT NULL,
    default_language_id BIGINT DEFAULT 0 NOT NULL,
    group_id INT DEFAULT 0 NOT NULL,
    identifier VARCHAR(45) DEFAULT '' NOT NULL,
    language_mask BIGINT DEFAULT 0 NOT NULL,
    priority INT DEFAULT 0 NOT NULL,
    PRIMARY KEY(id)
);
-- ibexa:sql-statement-separator
CREATE INDEX ezcobj_state_priority ON ezcobj_state (priority);
-- ibexa:sql-statement-separator
CREATE INDEX ezcobj_state_lmask ON ezcobj_state (language_mask);
-- ibexa:sql-statement-separator
CREATE UNIQUE INDEX ezcobj_state_identifier ON ezcobj_state (group_id, identifier);
-- ibexa:sql-statement-separator
CREATE TABLE ezcobj_state_group (
    id SERIAL NOT NULL,
    default_language_id BIGINT DEFAULT 0 NOT NULL,
    identifier VARCHAR(45) DEFAULT '' NOT NULL,
    language_mask BIGINT DEFAULT 0 NOT NULL,
    PRIMARY KEY(id)
);
-- ibexa:sql-statement-separator
CREATE INDEX ezcobj_state_group_lmask ON ezcobj_state_group (language_mask);
-- ibexa:sql-statement-separator
CREATE UNIQUE INDEX ezcobj_state_group_identifier ON ezcobj_state_group (identifier);
-- ibexa:sql-statement-separator
CREATE TABLE ezcobj_state_group_language (
    contentobject_state_group_id INT DEFAULT 0 NOT NULL,
    real_language_id BIGINT DEFAULT 0 NOT NULL,
    description TEXT NOT NULL,
    language_id BIGINT DEFAULT 0 NOT NULL,
    name VARCHAR(45) DEFAULT '' NOT NULL,
    PRIMARY KEY(contentobject_state_group_id, real_language_id)
);
-- ibexa:sql-statement-separator
CREATE TABLE ezcobj_state_language (
    contentobject_state_id INT DEFAULT 0 NOT NULL,
    language_id BIGINT DEFAULT 0 NOT NULL,
    description TEXT NOT NULL,
    name VARCHAR(45) DEFAULT '' NOT NULL,
    PRIMARY KEY(contentobject_state_id, language_id)
);
-- ibexa:sql-statement-separator
CREATE TABLE ezcobj_state_link (
    contentobject_id INT DEFAULT 0 NOT NULL,
    contentobject_state_id INT DEFAULT 0 NOT NULL,
    PRIMARY KEY(contentobject_id, contentobject_state_id)
);
-- ibexa:sql-statement-separator
CREATE TABLE ezcontent_language (
    id BIGINT DEFAULT 0 NOT NULL,
    disabled INT DEFAULT 0 NOT NULL,
    locale VARCHAR(20) DEFAULT '' NOT NULL,
    name VARCHAR(255) DEFAULT '' NOT NULL,
    PRIMARY KEY(id)
);
-- ibexa:sql-statement-separator
CREATE INDEX ezcontent_language_name ON ezcontent_language (name);
-- ibexa:sql-statement-separator
CREATE TABLE ezuser (
    contentobject_id INT DEFAULT 0 NOT NULL,
    email VARCHAR(150) DEFAULT '' NOT NULL,
    login VARCHAR(150) DEFAULT '' NOT NULL,
    password_hash VARCHAR(255) DEFAULT NULL,
    password_hash_type INT DEFAULT 1 NOT NULL,
    password_updated_at INT DEFAULT NULL,
    PRIMARY KEY(contentobject_id)
);
-- ibexa:sql-statement-separator
CREATE UNIQUE INDEX ezuser_login ON ezuser (login);
-- ibexa:sql-statement-separator
CREATE TABLE ezcontentobject_tree (
    node_id SERIAL NOT NULL,
    contentobject_id INT DEFAULT NULL,
    contentobject_is_published INT DEFAULT NULL,
    contentobject_version INT DEFAULT NULL,
    depth INT DEFAULT 0 NOT NULL,
    is_hidden INT DEFAULT 0 NOT NULL,
    is_invisible INT DEFAULT 0 NOT NULL,
    main_node_id INT DEFAULT NULL,
    modified_subnode INT DEFAULT 0,
    parent_node_id INT DEFAULT 0 NOT NULL,
    path_identification_string TEXT DEFAULT NULL,
    path_string VARCHAR(255) DEFAULT '' NOT NULL,
    priority INT DEFAULT 0 NOT NULL,
    remote_id VARCHAR(100) DEFAULT '' NOT NULL,
    sort_field INT DEFAULT 1,
    sort_order INT DEFAULT 1,
    PRIMARY KEY(node_id)
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
    id SERIAL NOT NULL,
    node_id INT DEFAULT 0 NOT NULL,
    user_id INT DEFAULT 0 NOT NULL,
    name VARCHAR(255) DEFAULT '' NOT NULL,
    PRIMARY KEY(id)
);
-- ibexa:sql-statement-separator
CREATE INDEX ezcontentbrowsebookmark_location ON ezcontentbrowsebookmark (node_id);
-- ibexa:sql-statement-separator
CREATE INDEX ezcontentbrowsebookmark_user ON ezcontentbrowsebookmark (user_id);
-- ibexa:sql-statement-separator
CREATE INDEX ezcontentbrowsebookmark_user_location ON ezcontentbrowsebookmark (user_id, node_id);
-- ibexa:sql-statement-separator
CREATE TABLE ezcontentclass (
    id SERIAL NOT NULL,
    version INT DEFAULT 0 NOT NULL,
    always_available INT DEFAULT 0 NOT NULL,
    contentobject_name VARCHAR(255) DEFAULT NULL,
    created INT DEFAULT 0 NOT NULL,
    creator_id INT DEFAULT 0 NOT NULL,
    identifier VARCHAR(50) DEFAULT '' NOT NULL,
    initial_language_id BIGINT DEFAULT 0 NOT NULL,
    is_container INT DEFAULT 0 NOT NULL,
    language_mask BIGINT DEFAULT 0 NOT NULL,
    modified INT DEFAULT 0 NOT NULL,
    modifier_id INT DEFAULT 0 NOT NULL,
    remote_id VARCHAR(100) DEFAULT '' NOT NULL,
    serialized_description_list TEXT DEFAULT NULL,
    serialized_name_list TEXT DEFAULT NULL,
    sort_field INT DEFAULT 1 NOT NULL,
    sort_order INT DEFAULT 1 NOT NULL,
    url_alias_name VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY(id, version)
);
-- ibexa:sql-statement-separator
CREATE INDEX ezcontentclass_version ON ezcontentclass (version);
-- ibexa:sql-statement-separator
CREATE INDEX ezcontentclass_identifier ON ezcontentclass (identifier, version);
-- ibexa:sql-statement-separator
CREATE TABLE ezcontentclass_attribute (
    id SERIAL NOT NULL,
    version INT DEFAULT 0 NOT NULL,
    can_translate INT DEFAULT 1,
    category VARCHAR(25) DEFAULT '' NOT NULL,
    contentclass_id INT DEFAULT 0 NOT NULL,
    data_float1 DOUBLE PRECISION DEFAULT NULL,
    data_float2 DOUBLE PRECISION DEFAULT NULL,
    data_float3 DOUBLE PRECISION DEFAULT NULL,
    data_float4 DOUBLE PRECISION DEFAULT NULL,
    data_int1 INT DEFAULT NULL,
    data_int2 INT DEFAULT NULL,
    data_int3 INT DEFAULT NULL,
    data_int4 INT DEFAULT NULL,
    data_text1 VARCHAR(255) DEFAULT NULL,
    data_text2 VARCHAR(50) DEFAULT NULL,
    data_text3 VARCHAR(50) DEFAULT NULL,
    data_text4 VARCHAR(255) DEFAULT NULL,
    data_text5 TEXT DEFAULT NULL,
    data_type_string VARCHAR(50) DEFAULT '' NOT NULL,
    identifier VARCHAR(50) DEFAULT '' NOT NULL,
    is_information_collector INT DEFAULT 0 NOT NULL,
    is_required INT DEFAULT 0 NOT NULL,
    is_searchable INT DEFAULT 0 NOT NULL,
    is_thumbnail BOOLEAN DEFAULT 'false' NOT NULL,
    placement INT DEFAULT 0 NOT NULL,
    serialized_data_text TEXT DEFAULT NULL,
    serialized_description_list TEXT DEFAULT NULL,
    serialized_name_list TEXT NOT NULL,
    PRIMARY KEY(id, version)
);
-- ibexa:sql-statement-separator
CREATE INDEX ezcontentclass_attr_ccid ON ezcontentclass_attribute (contentclass_id);
-- ibexa:sql-statement-separator
CREATE INDEX ezcontentclass_attr_dts ON ezcontentclass_attribute (data_type_string);
-- ibexa:sql-statement-separator
CREATE TABLE ezcontentclass_attribute_ml (
    contentclass_attribute_id INT NOT NULL,
    version INT NOT NULL,
    language_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    data_text TEXT DEFAULT NULL,
    data_json TEXT DEFAULT NULL,
    PRIMARY KEY(contentclass_attribute_id, version, language_id)
);
-- ibexa:sql-statement-separator
CREATE INDEX ezcontentclass_attribute_ml_lang_fk ON ezcontentclass_attribute_ml (language_id);
-- ibexa:sql-statement-separator
CREATE TABLE ezcontentclass_classgroup (
    contentclass_id INT DEFAULT 0 NOT NULL,
    contentclass_version INT DEFAULT 0 NOT NULL,
    group_id INT DEFAULT 0 NOT NULL,
    group_name VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY(contentclass_id, contentclass_version, group_id)
);
-- ibexa:sql-statement-separator
CREATE TABLE ezcontentclass_name (
    contentclass_id INT DEFAULT 0 NOT NULL,
    contentclass_version INT DEFAULT 0 NOT NULL,
    language_id BIGINT DEFAULT 0 NOT NULL,
    language_locale VARCHAR(20) DEFAULT '' NOT NULL,
    name VARCHAR(255) DEFAULT '' NOT NULL,
    PRIMARY KEY(contentclass_id, contentclass_version, language_id)
);
-- ibexa:sql-statement-separator
CREATE TABLE ezcontentclassgroup (
    id SERIAL NOT NULL,
    created INT DEFAULT 0 NOT NULL,
    creator_id INT DEFAULT 0 NOT NULL,
    modified INT DEFAULT 0 NOT NULL,
    modifier_id INT DEFAULT 0 NOT NULL,
    name VARCHAR(255) DEFAULT NULL,
    is_system BOOLEAN DEFAULT 'false' NOT NULL,
    PRIMARY KEY(id)
);
-- ibexa:sql-statement-separator
CREATE TABLE ezcontentobject (
    id SERIAL NOT NULL,
    contentclass_id INT DEFAULT 0 NOT NULL,
    current_version INT DEFAULT NULL,
    initial_language_id BIGINT DEFAULT 0 NOT NULL,
    language_mask BIGINT DEFAULT 0 NOT NULL,
    modified INT DEFAULT 0 NOT NULL,
    name VARCHAR(255) DEFAULT NULL,
    owner_id INT DEFAULT 0 NOT NULL,
    published INT DEFAULT 0 NOT NULL,
    remote_id VARCHAR(100) DEFAULT NULL,
    section_id INT DEFAULT 0 NOT NULL,
    status INT DEFAULT 0,
    is_hidden BOOLEAN DEFAULT 'false' NOT NULL,
    PRIMARY KEY(id)
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
    id SERIAL NOT NULL,
    version INT DEFAULT 0 NOT NULL,
    attribute_original_id INT DEFAULT 0,
    contentclassattribute_id INT DEFAULT 0 NOT NULL,
    contentobject_id INT DEFAULT 0 NOT NULL,
    data_float DOUBLE PRECISION DEFAULT NULL,
    data_int INT DEFAULT NULL,
    data_text TEXT DEFAULT NULL,
    data_type_string VARCHAR(50) DEFAULT '',
    language_code VARCHAR(20) DEFAULT '' NOT NULL,
    language_id BIGINT DEFAULT 0 NOT NULL,
    sort_key_int INT DEFAULT 0 NOT NULL,
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
    id SERIAL NOT NULL,
    contentclassattribute_id INT DEFAULT 0 NOT NULL,
    from_contentobject_id INT DEFAULT 0 NOT NULL,
    from_contentobject_version INT DEFAULT 0 NOT NULL,
    relation_type INT DEFAULT 1 NOT NULL,
    to_contentobject_id INT DEFAULT 0 NOT NULL,
    PRIMARY KEY(id)
);
-- ibexa:sql-statement-separator
CREATE INDEX ezco_link_to_co_id ON ezcontentobject_link (to_contentobject_id);
-- ibexa:sql-statement-separator
CREATE INDEX ezco_link_from ON ezcontentobject_link (from_contentobject_id, from_contentobject_version, contentclassattribute_id);
-- ibexa:sql-statement-separator
CREATE INDEX ezco_link_cca_id ON ezcontentobject_link (contentclassattribute_id);
-- ibexa:sql-statement-separator
CREATE TABLE ezcontentobject_name (
    contentobject_id INT DEFAULT 0 NOT NULL,
    content_version INT DEFAULT 0 NOT NULL,
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
    node_id INT DEFAULT 0 NOT NULL,
    contentobject_id INT DEFAULT NULL,
    contentobject_version INT DEFAULT NULL,
    depth INT DEFAULT 0 NOT NULL,
    is_hidden INT DEFAULT 0 NOT NULL,
    is_invisible INT DEFAULT 0 NOT NULL,
    main_node_id INT DEFAULT NULL,
    modified_subnode INT DEFAULT 0,
    parent_node_id INT DEFAULT 0 NOT NULL,
    path_identification_string TEXT DEFAULT NULL,
    path_string VARCHAR(255) DEFAULT '' NOT NULL,
    priority INT DEFAULT 0 NOT NULL,
    remote_id VARCHAR(100) DEFAULT '' NOT NULL,
    sort_field INT DEFAULT 1,
    sort_order INT DEFAULT 1,
    trashed INT DEFAULT 0 NOT NULL,
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
    id SERIAL NOT NULL,
    contentobject_id INT DEFAULT NULL,
    created INT DEFAULT 0 NOT NULL,
    creator_id INT DEFAULT 0 NOT NULL,
    initial_language_id BIGINT DEFAULT 0 NOT NULL,
    language_mask BIGINT DEFAULT 0 NOT NULL,
    modified INT DEFAULT 0 NOT NULL,
    status INT DEFAULT 0 NOT NULL,
    user_id INT DEFAULT 0 NOT NULL,
    version INT DEFAULT 0 NOT NULL,
    workflow_event_pos INT DEFAULT 0,
    PRIMARY KEY(id)
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
    name TEXT NOT NULL,
    name_trunk TEXT NOT NULL,
    datatype VARCHAR(255) DEFAULT 'application/octet-stream' NOT NULL,
    scope VARCHAR(25) DEFAULT '' NOT NULL,
    size BIGINT DEFAULT 0 NOT NULL,
    mtime INT DEFAULT 0 NOT NULL,
    expired BOOLEAN DEFAULT 'false' NOT NULL,
    status BOOLEAN DEFAULT 'false' NOT NULL,
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
    contentobject_attribute_id INT DEFAULT 0 NOT NULL,
    contentobject_version INT DEFAULT 0 NOT NULL,
    latitude DOUBLE PRECISION DEFAULT '0' NOT NULL,
    longitude DOUBLE PRECISION DEFAULT '0' NOT NULL,
    address VARCHAR(150) DEFAULT NULL,
    PRIMARY KEY(contentobject_attribute_id, contentobject_version)
);
-- ibexa:sql-statement-separator
CREATE INDEX latitude_longitude_key ON ezgmaplocation (latitude, longitude);
-- ibexa:sql-statement-separator
CREATE TABLE ezimagefile (
    id SERIAL NOT NULL,
    contentobject_attribute_id INT DEFAULT 0 NOT NULL,
    filepath TEXT NOT NULL,
    PRIMARY KEY(id)
);
-- ibexa:sql-statement-separator
CREATE INDEX ezimagefile_file ON ezimagefile (filepath);
-- ibexa:sql-statement-separator
CREATE INDEX ezimagefile_coid ON ezimagefile (contentobject_attribute_id);
-- ibexa:sql-statement-separator
CREATE TABLE ezkeyword (
    id SERIAL NOT NULL,
    class_id INT DEFAULT 0 NOT NULL,
    keyword VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY(id)
);
-- ibexa:sql-statement-separator
CREATE INDEX ezkeyword_keyword ON ezkeyword (keyword);
-- ibexa:sql-statement-separator
CREATE TABLE ezkeyword_attribute_link (
    id SERIAL NOT NULL,
    keyword_id INT DEFAULT 0 NOT NULL,
    objectattribute_id INT DEFAULT 0 NOT NULL,
    version INT DEFAULT 0 NOT NULL,
    PRIMARY KEY(id)
);
-- ibexa:sql-statement-separator
CREATE INDEX ezkeyword_attr_link_oaid ON ezkeyword_attribute_link (objectattribute_id);
-- ibexa:sql-statement-separator
CREATE INDEX ezkeyword_attr_link_kid_oaid ON ezkeyword_attribute_link (keyword_id, objectattribute_id);
-- ibexa:sql-statement-separator
CREATE INDEX ezkeyword_attr_link_oaid_ver ON ezkeyword_attribute_link (objectattribute_id, version);
-- ibexa:sql-statement-separator
CREATE TABLE ezmedia (
    contentobject_attribute_id INT DEFAULT 0 NOT NULL,
    version INT DEFAULT 0 NOT NULL,
    controls VARCHAR(50) DEFAULT NULL,
    filename VARCHAR(255) DEFAULT '' NOT NULL,
    has_controller INT DEFAULT 0,
    height INT DEFAULT NULL,
    is_autoplay INT DEFAULT 0,
    is_loop INT DEFAULT 0,
    mime_type VARCHAR(50) DEFAULT '' NOT NULL,
    original_filename VARCHAR(255) DEFAULT '' NOT NULL,
    pluginspage VARCHAR(255) DEFAULT NULL,
    quality VARCHAR(50) DEFAULT NULL,
    width INT DEFAULT NULL,
    PRIMARY KEY(contentobject_attribute_id, version)
);
-- ibexa:sql-statement-separator
CREATE TABLE eznode_assignment (
    id SERIAL NOT NULL,
    contentobject_id INT DEFAULT NULL,
    contentobject_version INT DEFAULT NULL,
    from_node_id INT DEFAULT 0,
    is_main INT DEFAULT 0 NOT NULL,
    op_code INT DEFAULT 0 NOT NULL,
    parent_node INT DEFAULT NULL,
    parent_remote_id VARCHAR(100) DEFAULT '' NOT NULL,
    remote_id VARCHAR(100) DEFAULT '0' NOT NULL,
    sort_field INT DEFAULT 1,
    sort_order INT DEFAULT 1,
    priority INT DEFAULT 0 NOT NULL,
    is_hidden INT DEFAULT 0 NOT NULL,
    PRIMARY KEY(id)
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
    id SERIAL NOT NULL,
    owner_id INT DEFAULT 0 NOT NULL,
    is_pending BOOLEAN DEFAULT 'true' NOT NULL,
    type VARCHAR(128) DEFAULT '' NOT NULL,
    created INT DEFAULT 0 NOT NULL,
    data TEXT DEFAULT NULL,
    PRIMARY KEY(id)
);
-- ibexa:sql-statement-separator
CREATE INDEX eznotification_owner_is_pending ON eznotification (owner_id, is_pending);
-- ibexa:sql-statement-separator
CREATE INDEX eznotification_owner ON eznotification (owner_id);
-- ibexa:sql-statement-separator
CREATE TABLE ezpackage (
    id SERIAL NOT NULL,
    install_date INT DEFAULT 0 NOT NULL,
    name VARCHAR(100) DEFAULT '' NOT NULL,
    version VARCHAR(30) DEFAULT '0' NOT NULL,
    PRIMARY KEY(id)
);
-- ibexa:sql-statement-separator
CREATE TABLE ezpolicy (
    id SERIAL NOT NULL,
    function_name VARCHAR(255) DEFAULT NULL,
    module_name VARCHAR(255) DEFAULT NULL,
    original_id INT DEFAULT 0 NOT NULL,
    role_id INT DEFAULT NULL,
    PRIMARY KEY(id)
);
-- ibexa:sql-statement-separator
CREATE INDEX ezpolicy_role_id ON ezpolicy (role_id);
-- ibexa:sql-statement-separator
CREATE INDEX ezpolicy_original_id ON ezpolicy (original_id);
-- ibexa:sql-statement-separator
CREATE TABLE ezpolicy_limitation (
    id SERIAL NOT NULL,
    identifier VARCHAR(255) DEFAULT '' NOT NULL,
    policy_id INT DEFAULT NULL,
    PRIMARY KEY(id)
);
-- ibexa:sql-statement-separator
CREATE INDEX policy_id ON ezpolicy_limitation (policy_id);
-- ibexa:sql-statement-separator
CREATE TABLE ezpolicy_limitation_value (
    id SERIAL NOT NULL,
    limitation_id INT DEFAULT NULL,
    value VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY(id)
);
-- ibexa:sql-statement-separator
CREATE INDEX ezpolicy_limit_value_limit_id ON ezpolicy_limitation_value (limitation_id);
-- ibexa:sql-statement-separator
CREATE INDEX ezpolicy_limitation_value_val ON ezpolicy_limitation_value (value);
-- ibexa:sql-statement-separator
CREATE TABLE ezpreferences (
    id SERIAL NOT NULL,
    name VARCHAR(100) DEFAULT NULL,
    user_id INT DEFAULT 0 NOT NULL,
    value TEXT DEFAULT NULL,
    PRIMARY KEY(id)
);
-- ibexa:sql-statement-separator
CREATE INDEX ezpreferences_user_id_idx ON ezpreferences (user_id, name);
-- ibexa:sql-statement-separator
CREATE INDEX ezpreferences_name ON ezpreferences (name);
-- ibexa:sql-statement-separator
CREATE TABLE ezrole (
    id SERIAL NOT NULL,
    is_new INT DEFAULT 0 NOT NULL,
    name VARCHAR(255) DEFAULT '' NOT NULL,
    value CHAR(1) DEFAULT NULL,
    version INT DEFAULT 0,
    PRIMARY KEY(id)
);
-- ibexa:sql-statement-separator
CREATE TABLE ezsearch_object_word_link (
    id SERIAL NOT NULL,
    contentclass_attribute_id INT DEFAULT 0 NOT NULL,
    contentclass_id INT DEFAULT 0 NOT NULL,
    contentobject_id INT DEFAULT 0 NOT NULL,
    frequency DOUBLE PRECISION DEFAULT '0' NOT NULL,
    identifier VARCHAR(255) DEFAULT '' NOT NULL,
    integer_value INT DEFAULT 0 NOT NULL,
    next_word_id INT DEFAULT 0 NOT NULL,
    placement INT DEFAULT 0 NOT NULL,
    prev_word_id INT DEFAULT 0 NOT NULL,
    published INT DEFAULT 0 NOT NULL,
    section_id INT DEFAULT 0 NOT NULL,
    word_id INT DEFAULT 0 NOT NULL,
    language_mask BIGINT DEFAULT 0 NOT NULL,
    PRIMARY KEY(id)
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
    id SERIAL NOT NULL,
    object_count INT DEFAULT 0 NOT NULL,
    word VARCHAR(150) DEFAULT NULL,
    PRIMARY KEY(id)
);
-- ibexa:sql-statement-separator
CREATE INDEX ezsearch_word_word_i ON ezsearch_word (word);
-- ibexa:sql-statement-separator
CREATE INDEX ezsearch_word_obj_count ON ezsearch_word (object_count);
-- ibexa:sql-statement-separator
CREATE TABLE ezsection (
    id SERIAL NOT NULL,
    identifier VARCHAR(255) DEFAULT NULL,
    locale VARCHAR(255) DEFAULT NULL,
    name VARCHAR(255) DEFAULT NULL,
    navigation_part_identifier VARCHAR(100) DEFAULT 'ezcontentnavigationpart',
    PRIMARY KEY(id)
);
-- ibexa:sql-statement-separator
CREATE TABLE ezsite_data (
    name VARCHAR(60) DEFAULT '' NOT NULL,
    value TEXT NOT NULL,
    PRIMARY KEY(name)
);
-- ibexa:sql-statement-separator
CREATE TABLE ezurl (
    id SERIAL NOT NULL,
    created INT DEFAULT 0 NOT NULL,
    is_valid INT DEFAULT 1 NOT NULL,
    last_checked INT DEFAULT 0 NOT NULL,
    modified INT DEFAULT 0 NOT NULL,
    original_url_md5 VARCHAR(32) DEFAULT '' NOT NULL,
    url TEXT DEFAULT NULL,
    PRIMARY KEY(id)
);
-- ibexa:sql-statement-separator
CREATE INDEX ezurl_url ON ezurl (url);
-- ibexa:sql-statement-separator
CREATE TABLE ezurl_object_link (
    contentobject_attribute_id INT DEFAULT 0 NOT NULL,
    contentobject_attribute_version INT DEFAULT 0 NOT NULL,
    url_id INT DEFAULT 0 NOT NULL
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
    id SERIAL NOT NULL,
    destination_url TEXT NOT NULL,
    forward_to_id INT DEFAULT 0 NOT NULL,
    is_imported INT DEFAULT 0 NOT NULL,
    is_internal INT DEFAULT 1 NOT NULL,
    is_wildcard INT DEFAULT 0 NOT NULL,
    source_md5 VARCHAR(32) DEFAULT NULL,
    source_url TEXT NOT NULL,
    PRIMARY KEY(id)
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
    parent INT DEFAULT 0 NOT NULL,
    text_md5 VARCHAR(32) DEFAULT '' NOT NULL,
    action TEXT NOT NULL,
    action_type VARCHAR(32) DEFAULT '' NOT NULL,
    alias_redirects INT DEFAULT 1 NOT NULL,
    id INT DEFAULT 0 NOT NULL,
    is_alias INT DEFAULT 0 NOT NULL,
    is_original INT DEFAULT 0 NOT NULL,
    lang_mask BIGINT DEFAULT 0 NOT NULL,
    link INT DEFAULT 0 NOT NULL,
    text TEXT NOT NULL,
    PRIMARY KEY(parent, text_md5)
);
-- ibexa:sql-statement-separator
CREATE INDEX ezurlalias_ml_actt_org_al ON ezurlalias_ml (action_type, is_original, is_alias);
-- ibexa:sql-statement-separator
CREATE INDEX ezurlalias_ml_text_lang ON ezurlalias_ml (text, lang_mask, parent);
-- ibexa:sql-statement-separator
CREATE INDEX ezurlalias_ml_par_act_id_lnk ON ezurlalias_ml (action, id, link, parent);
-- ibexa:sql-statement-separator
CREATE INDEX ezurlalias_ml_par_lnk_txt ON ezurlalias_ml (parent, text, link);
-- ibexa:sql-statement-separator
CREATE INDEX ezurlalias_ml_act_org ON ezurlalias_ml (action, is_original);
-- ibexa:sql-statement-separator
CREATE INDEX ezurlalias_ml_text ON ezurlalias_ml (text, id, link);
-- ibexa:sql-statement-separator
CREATE INDEX ezurlalias_ml_link ON ezurlalias_ml (link);
-- ibexa:sql-statement-separator
CREATE INDEX ezurlalias_ml_id ON ezurlalias_ml (id);
-- ibexa:sql-statement-separator
CREATE TABLE ezurlalias_ml_incr (
    id SERIAL NOT NULL,
    PRIMARY KEY(id)
);
-- ibexa:sql-statement-separator
CREATE TABLE ezurlwildcard (
    id SERIAL NOT NULL,
    destination_url TEXT NOT NULL,
    source_url TEXT NOT NULL,
    type INT DEFAULT 0 NOT NULL,
    PRIMARY KEY(id)
);
-- ibexa:sql-statement-separator
CREATE TABLE ezuser_accountkey (
    id SERIAL NOT NULL,
    hash_key VARCHAR(32) DEFAULT '' NOT NULL,
    time INT DEFAULT 0 NOT NULL,
    user_id INT DEFAULT 0 NOT NULL,
    PRIMARY KEY(id)
);
-- ibexa:sql-statement-separator
CREATE INDEX hash_key ON ezuser_accountkey (hash_key);
-- ibexa:sql-statement-separator
CREATE TABLE ezuser_role (
    id SERIAL NOT NULL,
    contentobject_id INT DEFAULT NULL,
    limit_identifier VARCHAR(255) DEFAULT '',
    limit_value VARCHAR(255) DEFAULT '',
    role_id INT DEFAULT NULL,
    PRIMARY KEY(id)
);
-- ibexa:sql-statement-separator
CREATE INDEX ezuser_role_role_id ON ezuser_role (role_id);
-- ibexa:sql-statement-separator
CREATE INDEX ezuser_role_contentobject_id ON ezuser_role (contentobject_id);
-- ibexa:sql-statement-separator
CREATE TABLE ezuser_setting (
    user_id INT DEFAULT 0 NOT NULL,
    is_enabled INT DEFAULT 0 NOT NULL,
    max_login INT DEFAULT NULL,
    PRIMARY KEY(user_id)
);
-- ibexa:sql-statement-separator
CREATE TABLE ibexa_setting (
    id SERIAL NOT NULL,
    "group" VARCHAR(128) NOT NULL,
    identifier VARCHAR(128) NOT NULL,
    value JSON NOT NULL,
    PRIMARY KEY(id)
);
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_setting_id ON ibexa_setting (id);
-- ibexa:sql-statement-separator
CREATE UNIQUE INDEX ibexa_setting_group_identifier ON ibexa_setting ("group", identifier);
-- ibexa:sql-statement-separator
CREATE TABLE ibexa_token_type (
    id SERIAL NOT NULL,
    identifier VARCHAR(64) NOT NULL,
    PRIMARY KEY(id)
);
-- ibexa:sql-statement-separator
CREATE UNIQUE INDEX ibexa_token_type_unique ON ibexa_token_type (identifier);
-- ibexa:sql-statement-separator
CREATE TABLE ibexa_token (
    id SERIAL NOT NULL,
    type_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    identifier VARCHAR(128) DEFAULT NULL,
    created INT DEFAULT 0 NOT NULL,
    expires INT DEFAULT 0 NOT NULL,
    revoked BOOLEAN DEFAULT 'false' NOT NULL,
    PRIMARY KEY(id)
);
-- ibexa:sql-statement-separator
CREATE INDEX IDX_B5412887C54C8C93 ON ibexa_token (type_id);
-- ibexa:sql-statement-separator
CREATE UNIQUE INDEX ibexa_token_unique ON ibexa_token (token, identifier, type_id);
-- ibexa:sql-statement-separator
ALTER TABLE ezcontentbrowsebookmark ADD CONSTRAINT ezcontentbrowsebookmark_location_fk FOREIGN KEY (node_id) REFERENCES ezcontentobject_tree (node_id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
-- ibexa:sql-statement-separator
ALTER TABLE ezcontentbrowsebookmark ADD CONSTRAINT ezcontentbrowsebookmark_user_fk FOREIGN KEY (user_id) REFERENCES ezuser (contentobject_id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
-- ibexa:sql-statement-separator
ALTER TABLE ezcontentclass_attribute_ml ADD CONSTRAINT ezcontentclass_attribute_ml_lang_fk FOREIGN KEY (language_id) REFERENCES ezcontent_language (id) ON UPDATE CASCADE ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_token ADD CONSTRAINT ibexa_token_type_id_fk FOREIGN KEY (type_id) REFERENCES ibexa_token_type (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
