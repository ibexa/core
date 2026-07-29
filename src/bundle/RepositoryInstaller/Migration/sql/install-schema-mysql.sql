CREATE TABLE ezbinaryfile (
    contentobject_attribute_id INT DEFAULT 0 NOT NULL,
    version INT DEFAULT 0 NOT NULL,
    download_count INT DEFAULT 0 NOT NULL,
    filename VARCHAR(255) DEFAULT '' NOT NULL,
    mime_type VARCHAR(255) DEFAULT '' NOT NULL,
    original_filename VARCHAR(255) DEFAULT '' NOT NULL,
    PRIMARY KEY(contentobject_attribute_id, version)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
CREATE TABLE ezcobj_state (
    id INT AUTO_INCREMENT NOT NULL,
    default_language_id BIGINT DEFAULT 0 NOT NULL,
    group_id INT DEFAULT 0 NOT NULL,
    identifier VARCHAR(45) DEFAULT '' NOT NULL,
    language_mask BIGINT DEFAULT 0 NOT NULL,
    priority INT DEFAULT 0 NOT NULL,
    INDEX ezcobj_state_priority (priority),
    INDEX ezcobj_state_lmask (language_mask),
    UNIQUE INDEX ezcobj_state_identifier (group_id, identifier),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
CREATE TABLE ezcobj_state_group (
    id INT AUTO_INCREMENT NOT NULL,
    default_language_id BIGINT DEFAULT 0 NOT NULL,
    identifier VARCHAR(45) DEFAULT '' NOT NULL,
    language_mask BIGINT DEFAULT 0 NOT NULL,
    INDEX ezcobj_state_group_lmask (language_mask),
    UNIQUE INDEX ezcobj_state_group_identifier (identifier),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
CREATE TABLE ezcobj_state_group_language (
    contentobject_state_group_id INT DEFAULT 0 NOT NULL,
    real_language_id BIGINT DEFAULT 0 NOT NULL,
    description LONGTEXT NOT NULL,
    language_id BIGINT DEFAULT 0 NOT NULL,
    name VARCHAR(45) DEFAULT '' NOT NULL,
    PRIMARY KEY(contentobject_state_group_id, real_language_id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
CREATE TABLE ezcobj_state_language (
    contentobject_state_id INT DEFAULT 0 NOT NULL,
    language_id BIGINT DEFAULT 0 NOT NULL,
    description LONGTEXT NOT NULL,
    name VARCHAR(45) DEFAULT '' NOT NULL,
    PRIMARY KEY(contentobject_state_id, language_id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
CREATE TABLE ezcobj_state_link (
    contentobject_id INT DEFAULT 0 NOT NULL,
    contentobject_state_id INT DEFAULT 0 NOT NULL,
    PRIMARY KEY(contentobject_id, contentobject_state_id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
CREATE TABLE ezcontent_language (
    id BIGINT DEFAULT 0 NOT NULL,
    disabled INT DEFAULT 0 NOT NULL,
    locale VARCHAR(20) DEFAULT '' NOT NULL,
    name VARCHAR(255) DEFAULT '' NOT NULL,
    INDEX ezcontent_language_name (name(191)),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
CREATE TABLE ezuser (
    contentobject_id INT DEFAULT 0 NOT NULL,
    email VARCHAR(150) DEFAULT '' NOT NULL,
    login VARCHAR(150) DEFAULT '' NOT NULL,
    password_hash VARCHAR(255) DEFAULT NULL,
    password_hash_type INT DEFAULT 1 NOT NULL,
    password_updated_at INT DEFAULT NULL,
    UNIQUE INDEX ezuser_login (login),
    PRIMARY KEY(contentobject_id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
CREATE TABLE ezcontentobject_tree (
    node_id INT AUTO_INCREMENT NOT NULL,
    contentobject_id INT DEFAULT NULL,
    contentobject_is_published INT DEFAULT NULL,
    contentobject_version INT DEFAULT NULL,
    depth INT DEFAULT 0 NOT NULL,
    is_hidden INT DEFAULT 0 NOT NULL,
    is_invisible INT DEFAULT 0 NOT NULL,
    main_node_id INT DEFAULT NULL,
    modified_subnode INT DEFAULT 0,
    parent_node_id INT DEFAULT 0 NOT NULL,
    path_identification_string LONGTEXT DEFAULT NULL,
    path_string VARCHAR(255) DEFAULT '' NOT NULL,
    priority INT DEFAULT 0 NOT NULL,
    remote_id VARCHAR(100) DEFAULT '' NOT NULL,
    sort_field INT DEFAULT 1,
    sort_order INT DEFAULT 1,
    INDEX ezcontentobject_tree_p_node_id (parent_node_id),
    INDEX ezcontentobject_tree_path_ident (path_identification_string(50)),
    INDEX ezcontentobject_tree_contentobject_id_path_string (path_string(191), contentobject_id),
    INDEX ezcontentobject_tree_co_id (contentobject_id),
    INDEX ezcontentobject_tree_depth (depth),
    INDEX ezcontentobject_tree_path (path_string(191)),
    INDEX modified_subnode (modified_subnode),
    INDEX ezcontentobject_tree_remote_id (remote_id),
    PRIMARY KEY(node_id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
CREATE TABLE ezcontentbrowsebookmark (
    id INT AUTO_INCREMENT NOT NULL,
    node_id INT DEFAULT 0 NOT NULL,
    user_id INT DEFAULT 0 NOT NULL,
    name VARCHAR(255) DEFAULT '' NOT NULL,
    INDEX ezcontentbrowsebookmark_location (node_id),
    INDEX ezcontentbrowsebookmark_user (user_id),
    INDEX ezcontentbrowsebookmark_user_location (user_id, node_id),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
CREATE TABLE ezcontentclass (
    id INT AUTO_INCREMENT NOT NULL,
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
    serialized_description_list LONGTEXT DEFAULT NULL,
    serialized_name_list LONGTEXT DEFAULT NULL,
    sort_field INT DEFAULT 1 NOT NULL,
    sort_order INT DEFAULT 1 NOT NULL,
    url_alias_name VARCHAR(255) DEFAULT NULL,
    INDEX ezcontentclass_version (version),
    INDEX ezcontentclass_identifier (identifier, version),
    PRIMARY KEY(id, version)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
CREATE TABLE ezcontentclass_attribute (
    id INT AUTO_INCREMENT NOT NULL,
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
    data_text5 LONGTEXT DEFAULT NULL,
    data_type_string VARCHAR(50) DEFAULT '' NOT NULL,
    identifier VARCHAR(50) DEFAULT '' NOT NULL,
    is_information_collector INT DEFAULT 0 NOT NULL,
    is_required INT DEFAULT 0 NOT NULL,
    is_searchable INT DEFAULT 0 NOT NULL,
    is_thumbnail TINYINT(1) DEFAULT '0' NOT NULL,
    placement INT DEFAULT 0 NOT NULL,
    serialized_data_text LONGTEXT DEFAULT NULL,
    serialized_description_list LONGTEXT DEFAULT NULL,
    serialized_name_list LONGTEXT NOT NULL,
    INDEX ezcontentclass_attr_ccid (contentclass_id),
    INDEX ezcontentclass_attr_dts (data_type_string),
    PRIMARY KEY(id, version)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
CREATE TABLE ezcontentclass_attribute_ml (
    contentclass_attribute_id INT NOT NULL,
    version INT NOT NULL,
    language_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    data_text TEXT DEFAULT NULL,
    data_json TEXT DEFAULT NULL,
    INDEX ezcontentclass_attribute_ml_lang_fk (language_id),
    PRIMARY KEY(contentclass_attribute_id, version, language_id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
CREATE TABLE ezcontentclass_classgroup (
    contentclass_id INT DEFAULT 0 NOT NULL,
    contentclass_version INT DEFAULT 0 NOT NULL,
    group_id INT DEFAULT 0 NOT NULL,
    group_name VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY(contentclass_id, contentclass_version, group_id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
CREATE TABLE ezcontentclass_name (
    contentclass_id INT DEFAULT 0 NOT NULL,
    contentclass_version INT DEFAULT 0 NOT NULL,
    language_id BIGINT DEFAULT 0 NOT NULL,
    language_locale VARCHAR(20) DEFAULT '' NOT NULL,
    name VARCHAR(255) DEFAULT '' NOT NULL,
    PRIMARY KEY(contentclass_id, contentclass_version, language_id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
CREATE TABLE ezcontentclassgroup (
    id INT AUTO_INCREMENT NOT NULL,
    created INT DEFAULT 0 NOT NULL,
    creator_id INT DEFAULT 0 NOT NULL,
    modified INT DEFAULT 0 NOT NULL,
    modifier_id INT DEFAULT 0 NOT NULL,
    name VARCHAR(255) DEFAULT NULL,
    is_system TINYINT(1) DEFAULT '0' NOT NULL,
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
CREATE TABLE ezcontentobject (
    id INT AUTO_INCREMENT NOT NULL,
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
    is_hidden TINYINT(1) DEFAULT '0' NOT NULL,
    INDEX ezcontentobject_classid (contentclass_id),
    INDEX ezcontentobject_lmask (language_mask),
    INDEX ezcontentobject_pub (published),
    INDEX ezcontentobject_section (section_id),
    INDEX ezcontentobject_currentversion (current_version),
    INDEX ezcontentobject_owner (owner_id),
    INDEX ezcontentobject_status (status),
    UNIQUE INDEX ezcontentobject_remote_id (remote_id),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
CREATE TABLE ezcontentobject_attribute (
    id INT AUTO_INCREMENT NOT NULL,
    version INT DEFAULT 0 NOT NULL,
    attribute_original_id INT DEFAULT 0,
    contentclassattribute_id INT DEFAULT 0 NOT NULL,
    contentobject_id INT DEFAULT 0 NOT NULL,
    data_float DOUBLE PRECISION DEFAULT NULL,
    data_int INT DEFAULT NULL,
    data_text LONGTEXT DEFAULT NULL,
    data_type_string VARCHAR(50) DEFAULT '',
    language_code VARCHAR(20) DEFAULT '' NOT NULL,
    language_id BIGINT DEFAULT 0 NOT NULL,
    sort_key_int INT DEFAULT 0 NOT NULL,
    sort_key_string VARCHAR(255) DEFAULT '' NOT NULL,
    INDEX ezcontentobject_attribute_co_id_ver_lang_code (contentobject_id, version, language_code),
    INDEX ezcontentobject_classattr_id (contentclassattribute_id),
    INDEX sort_key_string (sort_key_string(191)),
    INDEX ezcontentobject_attribute_language_code (language_code),
    INDEX sort_key_int (sort_key_int),
    INDEX ezcontentobject_attribute_co_id_ver (contentobject_id, version),
    PRIMARY KEY(id, version)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
CREATE TABLE ezcontentobject_link (
    id INT AUTO_INCREMENT NOT NULL,
    contentclassattribute_id INT DEFAULT 0 NOT NULL,
    from_contentobject_id INT DEFAULT 0 NOT NULL,
    from_contentobject_version INT DEFAULT 0 NOT NULL,
    relation_type INT DEFAULT 1 NOT NULL,
    to_contentobject_id INT DEFAULT 0 NOT NULL,
    INDEX ezco_link_to_co_id (to_contentobject_id),
    INDEX ezco_link_from (from_contentobject_id, from_contentobject_version, contentclassattribute_id),
    INDEX ezco_link_cca_id (contentclassattribute_id),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
CREATE TABLE ezcontentobject_name (
    contentobject_id INT DEFAULT 0 NOT NULL,
    content_version INT DEFAULT 0 NOT NULL,
    content_translation VARCHAR(20) DEFAULT '' NOT NULL,
    language_id BIGINT DEFAULT 0 NOT NULL,
    name VARCHAR(255) DEFAULT NULL,
    real_translation VARCHAR(20) DEFAULT NULL,
    INDEX ezcontentobject_name_lang_id (language_id),
    INDEX ezcontentobject_name_cov_id (content_version),
    INDEX ezcontentobject_name_name (name(191)),
    PRIMARY KEY(contentobject_id, content_version, content_translation)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
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
    path_identification_string LONGTEXT DEFAULT NULL,
    path_string VARCHAR(255) DEFAULT '' NOT NULL,
    priority INT DEFAULT 0 NOT NULL,
    remote_id VARCHAR(100) DEFAULT '' NOT NULL,
    sort_field INT DEFAULT 1,
    sort_order INT DEFAULT 1,
    trashed INT DEFAULT 0 NOT NULL,
    INDEX ezcobj_trash_depth (depth),
    INDEX ezcobj_trash_p_node_id (parent_node_id),
    INDEX ezcobj_trash_path_ident (path_identification_string(50)),
    INDEX ezcobj_trash_co_id (contentobject_id),
    INDEX ezcobj_trash_modified_subnode (modified_subnode),
    INDEX ezcobj_trash_path (path_string(191)),
    PRIMARY KEY(node_id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
CREATE TABLE ezcontentobject_version (
    id INT AUTO_INCREMENT NOT NULL,
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
    INDEX ezcobj_version_status (status),
    INDEX idx_object_version_objver (contentobject_id, version),
    INDEX ezcontobj_version_obj_status (contentobject_id, status),
    INDEX ezcobj_version_creator_id (creator_id),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
CREATE TABLE ezdfsfile (
    name_hash VARCHAR(34) DEFAULT '' NOT NULL,
    name TEXT NOT NULL,
    name_trunk TEXT NOT NULL,
    datatype VARCHAR(255) DEFAULT 'application/octet-stream' NOT NULL,
    scope VARCHAR(25) DEFAULT '' NOT NULL,
    size BIGINT UNSIGNED DEFAULT 0 NOT NULL,
    mtime INT DEFAULT 0 NOT NULL,
    expired TINYINT(1) DEFAULT '0' NOT NULL,
    status TINYINT(1) DEFAULT '0' NOT NULL,
    INDEX ezdfsfile_name_trunk (name_trunk(191)),
    INDEX ezdfsfile_expired_name (expired, name(191)),
    INDEX ezdfsfile_name (name(191)),
    INDEX ezdfsfile_mtime (mtime),
    PRIMARY KEY(name_hash)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
CREATE TABLE ezgmaplocation (
    contentobject_attribute_id INT DEFAULT 0 NOT NULL,
    contentobject_version INT DEFAULT 0 NOT NULL,
    latitude DOUBLE PRECISION DEFAULT '0' NOT NULL,
    longitude DOUBLE PRECISION DEFAULT '0' NOT NULL,
    address VARCHAR(150) DEFAULT NULL,
    INDEX latitude_longitude_key (latitude, longitude),
    PRIMARY KEY(contentobject_attribute_id, contentobject_version)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
CREATE TABLE ezimagefile (
    id INT AUTO_INCREMENT NOT NULL,
    contentobject_attribute_id INT DEFAULT 0 NOT NULL,
    filepath LONGTEXT NOT NULL,
    INDEX ezimagefile_file (filepath(191)),
    INDEX ezimagefile_coid (contentobject_attribute_id),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
CREATE TABLE ezkeyword (
    id INT AUTO_INCREMENT NOT NULL,
    class_id INT DEFAULT 0 NOT NULL,
    keyword VARCHAR(255) DEFAULT NULL,
    INDEX ezkeyword_keyword (keyword(191)),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
CREATE TABLE ezkeyword_attribute_link (
    id INT AUTO_INCREMENT NOT NULL,
    keyword_id INT DEFAULT 0 NOT NULL,
    objectattribute_id INT DEFAULT 0 NOT NULL,
    version INT DEFAULT 0 NOT NULL,
    INDEX ezkeyword_attr_link_oaid (objectattribute_id),
    INDEX ezkeyword_attr_link_kid_oaid (keyword_id, objectattribute_id),
    INDEX ezkeyword_attr_link_oaid_ver (objectattribute_id, version),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
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
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
CREATE TABLE eznode_assignment (
    id INT AUTO_INCREMENT NOT NULL,
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
    INDEX eznode_assignment_is_main (is_main),
    INDEX eznode_assignment_coid_cov (contentobject_id, contentobject_version),
    INDEX eznode_assignment_parent_node (parent_node),
    INDEX eznode_assignment_co_version (contentobject_version),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
CREATE TABLE eznotification (
    id INT AUTO_INCREMENT NOT NULL,
    owner_id INT DEFAULT 0 NOT NULL,
    is_pending TINYINT(1) DEFAULT '1' NOT NULL,
    type VARCHAR(128) DEFAULT '' NOT NULL,
    created INT DEFAULT 0 NOT NULL,
    data LONGTEXT DEFAULT NULL,
    INDEX eznotification_owner_is_pending (owner_id, is_pending),
    INDEX eznotification_owner (owner_id),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
CREATE TABLE ezpackage (
    id INT AUTO_INCREMENT NOT NULL,
    install_date INT DEFAULT 0 NOT NULL,
    name VARCHAR(100) DEFAULT '' NOT NULL,
    version VARCHAR(30) DEFAULT '0' NOT NULL,
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
CREATE TABLE ezpolicy (
    id INT AUTO_INCREMENT NOT NULL,
    function_name VARCHAR(255) DEFAULT NULL,
    module_name VARCHAR(255) DEFAULT NULL,
    original_id INT DEFAULT 0 NOT NULL,
    role_id INT DEFAULT NULL,
    INDEX ezpolicy_role_id (role_id),
    INDEX ezpolicy_original_id (original_id),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
CREATE TABLE ezpolicy_limitation (
    id INT AUTO_INCREMENT NOT NULL,
    identifier VARCHAR(255) DEFAULT '' NOT NULL,
    policy_id INT DEFAULT NULL,
    INDEX policy_id (policy_id),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
CREATE TABLE ezpolicy_limitation_value (
    id INT AUTO_INCREMENT NOT NULL,
    limitation_id INT DEFAULT NULL,
    value VARCHAR(255) DEFAULT NULL,
    INDEX ezpolicy_limit_value_limit_id (limitation_id),
    INDEX ezpolicy_limitation_value_val (value(191)),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
CREATE TABLE ezpreferences (
    id INT AUTO_INCREMENT NOT NULL,
    name VARCHAR(100) DEFAULT NULL,
    user_id INT DEFAULT 0 NOT NULL,
    value LONGTEXT DEFAULT NULL,
    INDEX ezpreferences_user_id_idx (user_id, name),
    INDEX ezpreferences_name (name),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
CREATE TABLE ezrole (
    id INT AUTO_INCREMENT NOT NULL,
    is_new INT DEFAULT 0 NOT NULL,
    name VARCHAR(255) DEFAULT '' NOT NULL,
    value CHAR(1) DEFAULT NULL,
    version INT DEFAULT 0,
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
CREATE TABLE ezsearch_object_word_link (
    id INT AUTO_INCREMENT NOT NULL,
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
    INDEX ezsearch_object_word_link_object (contentobject_id),
    INDEX ezsearch_object_word_link_identifier (identifier(191)),
    INDEX ezsearch_object_word_link_integer_value (integer_value),
    INDEX ezsearch_object_word_link_word (word_id),
    INDEX ezsearch_object_word_link_frequency (frequency),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
CREATE TABLE ezsearch_word (
    id INT AUTO_INCREMENT NOT NULL,
    object_count INT DEFAULT 0 NOT NULL,
    word VARCHAR(150) DEFAULT NULL,
    INDEX ezsearch_word_word_i (word),
    INDEX ezsearch_word_obj_count (object_count),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
CREATE TABLE ezsection (
    id INT AUTO_INCREMENT NOT NULL,
    identifier VARCHAR(255) DEFAULT NULL,
    locale VARCHAR(255) DEFAULT NULL,
    name VARCHAR(255) DEFAULT NULL,
    navigation_part_identifier VARCHAR(100) DEFAULT 'ezcontentnavigationpart',
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
CREATE TABLE ezsite_data (
    name VARCHAR(60) DEFAULT '' NOT NULL,
    value LONGTEXT NOT NULL,
    PRIMARY KEY(name)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
CREATE TABLE ezurl (
    id INT AUTO_INCREMENT NOT NULL,
    created INT DEFAULT 0 NOT NULL,
    is_valid INT DEFAULT 1 NOT NULL,
    last_checked INT DEFAULT 0 NOT NULL,
    modified INT DEFAULT 0 NOT NULL,
    original_url_md5 VARCHAR(32) DEFAULT '' NOT NULL,
    url LONGTEXT DEFAULT NULL,
    INDEX ezurl_url (url(191)),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
CREATE TABLE ezurl_object_link (
    contentobject_attribute_id INT DEFAULT 0 NOT NULL,
    contentobject_attribute_version INT DEFAULT 0 NOT NULL,
    url_id INT DEFAULT 0 NOT NULL,
    INDEX ezurl_ol_coa_id (contentobject_attribute_id),
    INDEX ezurl_ol_url_id (url_id),
    INDEX ezurl_ol_coa_version (contentobject_attribute_version),
    INDEX ezurl_ol_coa_id_cav (contentobject_attribute_id, contentobject_attribute_version)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
CREATE TABLE ezurlalias (
    id INT AUTO_INCREMENT NOT NULL,
    destination_url LONGTEXT NOT NULL,
    forward_to_id INT DEFAULT 0 NOT NULL,
    is_imported INT DEFAULT 0 NOT NULL,
    is_internal INT DEFAULT 1 NOT NULL,
    is_wildcard INT DEFAULT 0 NOT NULL,
    source_md5 VARCHAR(32) DEFAULT NULL,
    source_url LONGTEXT NOT NULL,
    INDEX ezurlalias_source_md5 (source_md5),
    INDEX ezurlalias_wcard_fwd (is_wildcard, forward_to_id),
    INDEX ezurlalias_forward_to_id (forward_to_id),
    INDEX ezurlalias_imp_wcard_fwd (is_imported, is_wildcard, forward_to_id),
    INDEX ezurlalias_source_url (source_url(191)),
    INDEX ezurlalias_desturl (destination_url(191)),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
CREATE TABLE ezurlalias_ml (
    parent INT DEFAULT 0 NOT NULL,
    text_md5 VARCHAR(32) DEFAULT '' NOT NULL,
    action LONGTEXT NOT NULL,
    action_type VARCHAR(32) DEFAULT '' NOT NULL,
    alias_redirects INT DEFAULT 1 NOT NULL,
    id INT DEFAULT 0 NOT NULL,
    is_alias INT DEFAULT 0 NOT NULL,
    is_original INT DEFAULT 0 NOT NULL,
    lang_mask BIGINT DEFAULT 0 NOT NULL,
    link INT DEFAULT 0 NOT NULL,
    text LONGTEXT NOT NULL,
    INDEX ezurlalias_ml_actt_org_al (action_type, is_original, is_alias),
    INDEX ezurlalias_ml_text_lang (text(32), lang_mask, parent),
    INDEX ezurlalias_ml_par_act_id_lnk (action(32), id, link, parent),
    INDEX ezurlalias_ml_par_lnk_txt (parent, text(32), link),
    INDEX ezurlalias_ml_act_org (action(32), is_original),
    INDEX ezurlalias_ml_text (text(32), id, link),
    INDEX ezurlalias_ml_link (link),
    INDEX ezurlalias_ml_id (id),
    PRIMARY KEY(parent, text_md5)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
CREATE TABLE ezurlalias_ml_incr (
    id INT AUTO_INCREMENT NOT NULL,
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
CREATE TABLE ezurlwildcard (
    id INT AUTO_INCREMENT NOT NULL,
    destination_url LONGTEXT NOT NULL,
    source_url LONGTEXT NOT NULL,
    type INT DEFAULT 0 NOT NULL,
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
CREATE TABLE ezuser_accountkey (
    id INT AUTO_INCREMENT NOT NULL,
    hash_key VARCHAR(32) DEFAULT '' NOT NULL,
    time INT DEFAULT 0 NOT NULL,
    user_id INT DEFAULT 0 NOT NULL,
    INDEX hash_key (hash_key),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
CREATE TABLE ezuser_role (
    id INT AUTO_INCREMENT NOT NULL,
    contentobject_id INT DEFAULT NULL,
    limit_identifier VARCHAR(255) DEFAULT '',
    limit_value VARCHAR(255) DEFAULT '',
    role_id INT DEFAULT NULL,
    INDEX ezuser_role_role_id (role_id),
    INDEX ezuser_role_contentobject_id (contentobject_id),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
CREATE TABLE ezuser_setting (
    user_id INT DEFAULT 0 NOT NULL,
    is_enabled INT DEFAULT 0 NOT NULL,
    max_login INT DEFAULT NULL,
    PRIMARY KEY(user_id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
CREATE TABLE ibexa_setting (
    id INT AUTO_INCREMENT NOT NULL,
    `group` VARCHAR(128) NOT NULL,
    identifier VARCHAR(128) NOT NULL,
    value JSON NOT NULL,
    INDEX ibexa_setting_id (id),
    UNIQUE INDEX ibexa_setting_group_identifier (`group`, identifier),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
CREATE TABLE ibexa_token_type (
    id INT AUTO_INCREMENT NOT NULL,
    identifier VARCHAR(64) NOT NULL,
    UNIQUE INDEX ibexa_token_type_unique (identifier),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
CREATE TABLE ibexa_token (
    id INT AUTO_INCREMENT NOT NULL,
    type_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    identifier VARCHAR(128) DEFAULT NULL,
    created INT DEFAULT 0 NOT NULL,
    expires INT DEFAULT 0 NOT NULL,
    revoked TINYINT(1) DEFAULT '0' NOT NULL,
    INDEX IDX_B5412887C54C8C93 (type_id),
    UNIQUE INDEX ibexa_token_unique (token, identifier, type_id),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
-- ibexa:sql-statement-separator
ALTER TABLE ezcontentbrowsebookmark ADD CONSTRAINT ezcontentbrowsebookmark_location_fk FOREIGN KEY (node_id) REFERENCES ezcontentobject_tree (node_id) ON UPDATE NO ACTION ON DELETE CASCADE;
-- ibexa:sql-statement-separator
ALTER TABLE ezcontentbrowsebookmark ADD CONSTRAINT ezcontentbrowsebookmark_user_fk FOREIGN KEY (user_id) REFERENCES ezuser (contentobject_id) ON UPDATE NO ACTION ON DELETE CASCADE;
-- ibexa:sql-statement-separator
ALTER TABLE ezcontentclass_attribute_ml ADD CONSTRAINT ezcontentclass_attribute_ml_lang_fk FOREIGN KEY (language_id) REFERENCES ezcontent_language (id) ON UPDATE CASCADE ON DELETE CASCADE;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_token ADD CONSTRAINT ibexa_token_type_id_fk FOREIGN KEY (type_id) REFERENCES ibexa_token_type (id) ON DELETE CASCADE;
