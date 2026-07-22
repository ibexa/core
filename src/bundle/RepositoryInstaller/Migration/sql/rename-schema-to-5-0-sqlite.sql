ALTER TABLE ezbinaryfile RENAME TO ibexa_binary_file;
-- ibexa:sql-statement-separator
ALTER TABLE ezcobj_state RENAME TO ibexa_object_state;
-- ibexa:sql-statement-separator
DROP INDEX ezcobj_state_priority;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_object_state_priority ON ibexa_object_state (priority);
-- ibexa:sql-statement-separator
DROP INDEX ezcobj_state_lmask;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_object_state_lmask ON ibexa_object_state (language_mask);
-- ibexa:sql-statement-separator
DROP INDEX ezcobj_state_identifier;
-- ibexa:sql-statement-separator
CREATE UNIQUE INDEX ibexa_object_state_identifier ON ibexa_object_state (group_id, identifier);
-- ibexa:sql-statement-separator
ALTER TABLE ezcobj_state_group RENAME TO ibexa_object_state_group;
-- ibexa:sql-statement-separator
DROP INDEX ezcobj_state_group_lmask;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_object_state_group_lmask ON ibexa_object_state_group (language_mask);
-- ibexa:sql-statement-separator
DROP INDEX ezcobj_state_group_identifier;
-- ibexa:sql-statement-separator
CREATE UNIQUE INDEX ibexa_object_state_group_identifier ON ibexa_object_state_group (identifier);
-- ibexa:sql-statement-separator
ALTER TABLE ezcobj_state_group_language RENAME TO ibexa_object_state_group_language;
-- ibexa:sql-statement-separator
ALTER TABLE ezcobj_state_language RENAME TO ibexa_object_state_language;
-- ibexa:sql-statement-separator
ALTER TABLE ezcobj_state_link RENAME TO ibexa_object_state_link;
-- ibexa:sql-statement-separator
ALTER TABLE ezcontent_language RENAME TO ibexa_content_language;
-- ibexa:sql-statement-separator
DROP INDEX ezcontent_language_name;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_language_name ON ibexa_content_language (name);
-- ibexa:sql-statement-separator
ALTER TABLE ezcontentbrowsebookmark RENAME TO ibexa_content_bookmark;
-- ibexa:sql-statement-separator
DROP INDEX ezcontentbrowsebookmark_location;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_bookmark_location ON ibexa_content_bookmark (node_id);
-- ibexa:sql-statement-separator
DROP INDEX ezcontentbrowsebookmark_user;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_bookmark_user ON ibexa_content_bookmark (user_id);
-- ibexa:sql-statement-separator
DROP INDEX ezcontentbrowsebookmark_user_location;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_bookmark_user_location ON ibexa_content_bookmark (user_id, node_id);
-- ibexa:sql-statement-separator
ALTER TABLE ezcontentclass RENAME TO ibexa_content_type;
-- ibexa:sql-statement-separator
DROP INDEX ezcontentclass_version;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_type_version ON ibexa_content_type (version);
-- ibexa:sql-statement-separator
DROP INDEX ezcontentclass_identifier;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_type_identifier ON ibexa_content_type (identifier, version);
-- ibexa:sql-statement-separator
ALTER TABLE ezcontentclass_attribute RENAME TO ibexa_content_type_field_definition;
-- ibexa:sql-statement-separator
DROP INDEX ezcontentclass_attr_ccid;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_type_field_definition_ctid ON ibexa_content_type_field_definition (contentclass_id);
-- ibexa:sql-statement-separator
DROP INDEX ezcontentclass_attr_dts;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_type_field_definition_dts ON ibexa_content_type_field_definition (data_type_string);
-- ibexa:sql-statement-separator
ALTER TABLE ezcontentclass_attribute_ml RENAME TO ibexa_content_type_field_definition_ml;
-- ibexa:sql-statement-separator
DROP INDEX ezcontentclass_attribute_ml_lang_fk;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_type_field_definition_ml_lang_fk ON ibexa_content_type_field_definition_ml (language_id);
-- ibexa:sql-statement-separator
ALTER TABLE ezcontentclass_classgroup RENAME TO ibexa_content_type_group_assignment;
-- ibexa:sql-statement-separator
ALTER TABLE ezcontentclass_name RENAME TO ibexa_content_type_name;
-- ibexa:sql-statement-separator
ALTER TABLE ezcontentclassgroup RENAME TO ibexa_content_type_group;
-- ibexa:sql-statement-separator
ALTER TABLE ezcontentobject_tree RENAME TO ibexa_content_tree;
-- ibexa:sql-statement-separator
DROP INDEX ezcontentobject_tree_p_node_id;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_tree_p_node_id ON ibexa_content_tree (parent_node_id);
-- ibexa:sql-statement-separator
DROP INDEX ezcontentobject_tree_path_ident;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_tree_path_ident ON ibexa_content_tree (path_identification_string);
-- ibexa:sql-statement-separator
DROP INDEX ezcontentobject_tree_contentobject_id_path_string;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_tree_contentobject_id_path_string ON ibexa_content_tree (path_string, contentobject_id);
-- ibexa:sql-statement-separator
DROP INDEX ezcontentobject_tree_co_id;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_tree_co_id ON ibexa_content_tree (contentobject_id);
-- ibexa:sql-statement-separator
DROP INDEX ezcontentobject_tree_depth;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_tree_depth ON ibexa_content_tree (depth);
-- ibexa:sql-statement-separator
DROP INDEX ezcontentobject_tree_path;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_tree_path ON ibexa_content_tree (path_string);
-- ibexa:sql-statement-separator
DROP INDEX modified_subnode;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_modified_subnode ON ibexa_content_tree (modified_subnode);
-- ibexa:sql-statement-separator
DROP INDEX ezcontentobject_tree_remote_id;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_tree_remote_id ON ibexa_content_tree (remote_id);
-- ibexa:sql-statement-separator
ALTER TABLE ezcontentobject RENAME TO ibexa_content;
-- ibexa:sql-statement-separator
DROP INDEX ezcontentobject_classid;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_type_id ON ibexa_content (contentclass_id);
-- ibexa:sql-statement-separator
DROP INDEX ezcontentobject_lmask;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_lmask ON ibexa_content (language_mask);
-- ibexa:sql-statement-separator
DROP INDEX ezcontentobject_pub;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_pub ON ibexa_content (published);
-- ibexa:sql-statement-separator
DROP INDEX ezcontentobject_section;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_section ON ibexa_content (section_id);
-- ibexa:sql-statement-separator
DROP INDEX ezcontentobject_currentversion;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_currentversion ON ibexa_content (current_version);
-- ibexa:sql-statement-separator
DROP INDEX ezcontentobject_owner;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_owner ON ibexa_content (owner_id);
-- ibexa:sql-statement-separator
DROP INDEX ezcontentobject_status;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_status ON ibexa_content (status);
-- ibexa:sql-statement-separator
DROP INDEX ezcontentobject_remote_id;
-- ibexa:sql-statement-separator
CREATE UNIQUE INDEX ibexa_content_remote_id ON ibexa_content (remote_id);
-- ibexa:sql-statement-separator
ALTER TABLE ezcontentobject_attribute RENAME TO ibexa_content_field;
-- ibexa:sql-statement-separator
DROP INDEX ezcontentobject_attribute_co_id_ver_lang_code;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_field_co_id_ver_lang_code ON ibexa_content_field (contentobject_id, version, language_code);
-- ibexa:sql-statement-separator
DROP INDEX ezcontentobject_classattr_id;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_field_classattr_id ON ibexa_content_field (contentclassattribute_id);
-- ibexa:sql-statement-separator
DROP INDEX ezcontentobject_attribute_language_code;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_field_language_code ON ibexa_content_field (language_code);
-- ibexa:sql-statement-separator
DROP INDEX ezcontentobject_attribute_co_id_ver;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_field_co_id_ver ON ibexa_content_field (contentobject_id, version);
-- ibexa:sql-statement-separator
ALTER TABLE ezcontentobject_link RENAME TO ibexa_content_relation;
-- ibexa:sql-statement-separator
DROP INDEX ezco_link_to_co_id;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_relation_to_co_id ON ibexa_content_relation (to_contentobject_id);
-- ibexa:sql-statement-separator
DROP INDEX ezco_link_from;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_relation_from ON ibexa_content_relation (from_contentobject_id, from_contentobject_version, contentclassattribute_id);
-- ibexa:sql-statement-separator
DROP INDEX ezco_link_cca_id;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_relation_cca_id ON ibexa_content_relation (contentclassattribute_id);
-- ibexa:sql-statement-separator
ALTER TABLE ezcontentobject_name RENAME TO ibexa_content_name;
-- ibexa:sql-statement-separator
DROP INDEX ezcontentobject_name_lang_id;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_name_lang_id ON ibexa_content_name (language_id);
-- ibexa:sql-statement-separator
DROP INDEX ezcontentobject_name_cov_id;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_name_cov_id ON ibexa_content_name (content_version);
-- ibexa:sql-statement-separator
DROP INDEX ezcontentobject_name_name;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_name_name ON ibexa_content_name (name);
-- ibexa:sql-statement-separator
ALTER TABLE ezcontentobject_trash RENAME TO ibexa_content_trash;
-- ibexa:sql-statement-separator
DROP INDEX ezcobj_trash_depth;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_trash_depth ON ibexa_content_trash (depth);
-- ibexa:sql-statement-separator
DROP INDEX ezcobj_trash_p_node_id;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_trash_p_node_id ON ibexa_content_trash (parent_node_id);
-- ibexa:sql-statement-separator
DROP INDEX ezcobj_trash_path_ident;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_trash_path_ident ON ibexa_content_trash (path_identification_string);
-- ibexa:sql-statement-separator
DROP INDEX ezcobj_trash_co_id;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_trash_co_id ON ibexa_content_trash (contentobject_id);
-- ibexa:sql-statement-separator
DROP INDEX ezcobj_trash_modified_subnode;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_trash_modified_subnode ON ibexa_content_trash (modified_subnode);
-- ibexa:sql-statement-separator
DROP INDEX ezcobj_trash_path;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_trash_path ON ibexa_content_trash (path_string);
-- ibexa:sql-statement-separator
ALTER TABLE ezcontentobject_version RENAME TO ibexa_content_version;
-- ibexa:sql-statement-separator
DROP INDEX ezcobj_version_status;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_version_status ON ibexa_content_version (status);
-- ibexa:sql-statement-separator
DROP INDEX idx_object_version_objver;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_version_idx_ver ON ibexa_content_version (contentobject_id, version);
-- ibexa:sql-statement-separator
DROP INDEX ezcontobj_version_obj_status;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_version_idx_status ON ibexa_content_version (contentobject_id, status);
-- ibexa:sql-statement-separator
DROP INDEX ezcobj_version_creator_id;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_version_creator_id ON ibexa_content_version (creator_id);
-- ibexa:sql-statement-separator
ALTER TABLE ezdfsfile RENAME TO ibexa_dfs_file;
-- ibexa:sql-statement-separator
DROP INDEX ezdfsfile_name_trunk;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_dfs_file_name_trunk ON ibexa_dfs_file (name_trunk);
-- ibexa:sql-statement-separator
DROP INDEX ezdfsfile_expired_name;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_dfs_file_expired_name ON ibexa_dfs_file (expired, name);
-- ibexa:sql-statement-separator
DROP INDEX ezdfsfile_name;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_dfs_file_name ON ibexa_dfs_file (name);
-- ibexa:sql-statement-separator
DROP INDEX ezdfsfile_mtime;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_dfs_file_mtime ON ibexa_dfs_file (mtime);
-- ibexa:sql-statement-separator
ALTER TABLE ezgmaplocation RENAME TO ibexa_map_location;
-- ibexa:sql-statement-separator
DROP INDEX latitude_longitude_key;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_map_location_latitude_longitude_key ON ibexa_map_location (latitude, longitude);
-- ibexa:sql-statement-separator
ALTER TABLE ezimagefile RENAME TO ibexa_image_file;
-- ibexa:sql-statement-separator
DROP INDEX ezimagefile_file;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_image_file_file ON ibexa_image_file (filepath);
-- ibexa:sql-statement-separator
DROP INDEX ezimagefile_coid;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_image_file_coid ON ibexa_image_file (contentobject_attribute_id);
-- ibexa:sql-statement-separator
ALTER TABLE ezkeyword RENAME TO ibexa_keyword;
-- ibexa:sql-statement-separator
DROP INDEX ezkeyword_keyword;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_keyword_keyword ON ibexa_keyword (keyword);
-- ibexa:sql-statement-separator
ALTER TABLE ezkeyword_attribute_link RENAME TO ibexa_keyword_field_link;
-- ibexa:sql-statement-separator
DROP INDEX ezkeyword_attr_link_oaid;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_keyword_field_link_oaid ON ibexa_keyword_field_link (objectattribute_id);
-- ibexa:sql-statement-separator
DROP INDEX ezkeyword_attr_link_kid_oaid;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_keyword_field_link_kid_oaid ON ibexa_keyword_field_link (keyword_id, objectattribute_id);
-- ibexa:sql-statement-separator
DROP INDEX ezkeyword_attr_link_oaid_ver;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_keyword_field_link_oaid_ver ON ibexa_keyword_field_link (objectattribute_id, version);
-- ibexa:sql-statement-separator
ALTER TABLE ezmedia RENAME TO ibexa_media;
-- ibexa:sql-statement-separator
ALTER TABLE eznode_assignment RENAME TO ibexa_node_assignment;
-- ibexa:sql-statement-separator
DROP INDEX eznode_assignment_is_main;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_node_assignment_is_main ON ibexa_node_assignment (is_main);
-- ibexa:sql-statement-separator
DROP INDEX eznode_assignment_coid_cov;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_node_assignment_coid_cov ON ibexa_node_assignment (contentobject_id, contentobject_version);
-- ibexa:sql-statement-separator
DROP INDEX eznode_assignment_parent_node;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_node_assignment_parent_node ON ibexa_node_assignment (parent_node);
-- ibexa:sql-statement-separator
DROP INDEX eznode_assignment_co_version;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_node_assignment_co_version ON ibexa_node_assignment (contentobject_version);
-- ibexa:sql-statement-separator
ALTER TABLE eznotification RENAME TO ibexa_notification;
-- ibexa:sql-statement-separator
DROP INDEX eznotification_owner_is_pending;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_notification_owner_is_pending ON ibexa_notification (owner_id, is_pending);
-- ibexa:sql-statement-separator
DROP INDEX eznotification_owner;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_notification_owner ON ibexa_notification (owner_id);
-- ibexa:sql-statement-separator
ALTER TABLE ezpackage RENAME TO ibexa_package;
-- ibexa:sql-statement-separator
ALTER TABLE ezpolicy RENAME TO ibexa_policy;
-- ibexa:sql-statement-separator
DROP INDEX ezpolicy_role_id;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_policy_role_id ON ibexa_policy (role_id);
-- ibexa:sql-statement-separator
DROP INDEX ezpolicy_original_id;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_policy_original_id ON ibexa_policy (original_id);
-- ibexa:sql-statement-separator
ALTER TABLE ezpolicy_limitation RENAME TO ibexa_policy_limitation;
-- ibexa:sql-statement-separator
DROP INDEX policy_id;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_policy_id ON ibexa_policy_limitation (policy_id);
-- ibexa:sql-statement-separator
ALTER TABLE ezpolicy_limitation_value RENAME TO ibexa_policy_limitation_value;
-- ibexa:sql-statement-separator
DROP INDEX ezpolicy_limit_value_limit_id;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_policy_limit_value_limit_id ON ibexa_policy_limitation_value (limitation_id);
-- ibexa:sql-statement-separator
DROP INDEX ezpolicy_limitation_value_val;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_policy_limitation_value_val ON ibexa_policy_limitation_value (value);
-- ibexa:sql-statement-separator
ALTER TABLE ezpreferences RENAME TO ibexa_user_preference;
-- ibexa:sql-statement-separator
DROP INDEX ezpreferences_user_id_idx;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_user_preference_user_id_idx ON ibexa_user_preference (user_id, name);
-- ibexa:sql-statement-separator
DROP INDEX ezpreferences_name;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_user_preference_name ON ibexa_user_preference (name);
-- ibexa:sql-statement-separator
ALTER TABLE ezrole RENAME TO ibexa_role;
-- ibexa:sql-statement-separator
ALTER TABLE ezsearch_object_word_link RENAME TO ibexa_search_object_word_link;
-- ibexa:sql-statement-separator
DROP INDEX ezsearch_object_word_link_object;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_search_object_word_link_object ON ibexa_search_object_word_link (contentobject_id);
-- ibexa:sql-statement-separator
DROP INDEX ezsearch_object_word_link_identifier;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_search_object_word_link_identifier ON ibexa_search_object_word_link (identifier);
-- ibexa:sql-statement-separator
DROP INDEX ezsearch_object_word_link_integer_value;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_search_object_word_link_integer_value ON ibexa_search_object_word_link (integer_value);
-- ibexa:sql-statement-separator
DROP INDEX ezsearch_object_word_link_word;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_search_object_word_link_word ON ibexa_search_object_word_link (word_id);
-- ibexa:sql-statement-separator
DROP INDEX ezsearch_object_word_link_frequency;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_search_object_word_link_frequency ON ibexa_search_object_word_link (frequency);
-- ibexa:sql-statement-separator
ALTER TABLE ezsearch_word RENAME TO ibexa_search_word;
-- ibexa:sql-statement-separator
DROP INDEX ezsearch_word_word_i;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_search_word_word_i ON ibexa_search_word (word);
-- ibexa:sql-statement-separator
DROP INDEX ezsearch_word_obj_count;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_search_word_obj_count ON ibexa_search_word (object_count);
-- ibexa:sql-statement-separator
ALTER TABLE ezsection RENAME TO ibexa_section;
-- ibexa:sql-statement-separator
ALTER TABLE ezsite_data RENAME TO ibexa_site_data;
-- ibexa:sql-statement-separator
ALTER TABLE ezurl RENAME TO ibexa_url;
-- ibexa:sql-statement-separator
DROP INDEX ezurl_url;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_url_url ON ibexa_url (url);
-- ibexa:sql-statement-separator
ALTER TABLE ezurl_object_link RENAME TO ibexa_url_content_link;
-- ibexa:sql-statement-separator
DROP INDEX ezurl_ol_coa_id;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_url_ol_coa_id ON ibexa_url_content_link (contentobject_attribute_id);
-- ibexa:sql-statement-separator
DROP INDEX ezurl_ol_url_id;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_url_ol_url_id ON ibexa_url_content_link (url_id);
-- ibexa:sql-statement-separator
DROP INDEX ezurl_ol_coa_version;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_url_ol_coa_version ON ibexa_url_content_link (contentobject_attribute_version);
-- ibexa:sql-statement-separator
DROP INDEX ezurl_ol_coa_id_cav;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_url_ol_coa_id_cav ON ibexa_url_content_link (contentobject_attribute_id, contentobject_attribute_version);
-- ibexa:sql-statement-separator
ALTER TABLE ezurlalias RENAME TO ibexa_url_alias;
-- ibexa:sql-statement-separator
DROP INDEX ezurlalias_source_md5;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_url_alias_source_md5 ON ibexa_url_alias (source_md5);
-- ibexa:sql-statement-separator
DROP INDEX ezurlalias_wcard_fwd;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_url_alias_wcard_fwd ON ibexa_url_alias (is_wildcard, forward_to_id);
-- ibexa:sql-statement-separator
DROP INDEX ezurlalias_forward_to_id;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_url_alias_forward_to_id ON ibexa_url_alias (forward_to_id);
-- ibexa:sql-statement-separator
DROP INDEX ezurlalias_imp_wcard_fwd;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_url_alias_imp_wcard_fwd ON ibexa_url_alias (is_imported, is_wildcard, forward_to_id);
-- ibexa:sql-statement-separator
DROP INDEX ezurlalias_source_url;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_url_alias_source_url ON ibexa_url_alias (source_url);
-- ibexa:sql-statement-separator
DROP INDEX ezurlalias_desturl;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_url_alias_desturl ON ibexa_url_alias (destination_url);
-- ibexa:sql-statement-separator
ALTER TABLE ezurlalias_ml RENAME TO ibexa_url_alias_ml;
-- ibexa:sql-statement-separator
DROP INDEX ezurlalias_ml_actt_org_al;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_url_alias_ml_actt_org_al ON ibexa_url_alias_ml (action_type, is_original, is_alias);
-- ibexa:sql-statement-separator
DROP INDEX ezurlalias_ml_text_lang;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_url_alias_ml_text_lang ON ibexa_url_alias_ml (text, lang_mask, parent);
-- ibexa:sql-statement-separator
DROP INDEX ezurlalias_ml_par_act_id_lnk;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_url_alias_ml_par_act_id_lnk ON ibexa_url_alias_ml ("action", id, link, parent);
-- ibexa:sql-statement-separator
DROP INDEX ezurlalias_ml_par_lnk_txt;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_url_alias_ml_par_lnk_txt ON ibexa_url_alias_ml (parent, text, link);
-- ibexa:sql-statement-separator
DROP INDEX ezurlalias_ml_act_org;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_url_alias_ml_act_org ON ibexa_url_alias_ml ("action", is_original);
-- ibexa:sql-statement-separator
DROP INDEX ezurlalias_ml_text;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_url_alias_ml_text ON ibexa_url_alias_ml (text, id, link);
-- ibexa:sql-statement-separator
DROP INDEX ezurlalias_ml_link;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_url_alias_ml_link ON ibexa_url_alias_ml (link);
-- ibexa:sql-statement-separator
DROP INDEX ezurlalias_ml_id;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_url_alias_ml_id ON ibexa_url_alias_ml (id);
-- ibexa:sql-statement-separator
ALTER TABLE ezurlalias_ml_incr RENAME TO ibexa_url_alias_ml_incr;
-- ibexa:sql-statement-separator
ALTER TABLE ezurlwildcard RENAME TO ibexa_url_wildcard;
-- ibexa:sql-statement-separator
ALTER TABLE ezuser RENAME TO ibexa_user;
-- ibexa:sql-statement-separator
DROP INDEX ezuser_login;
-- ibexa:sql-statement-separator
CREATE UNIQUE INDEX ibexa_user_login ON ibexa_user (login);
-- ibexa:sql-statement-separator
ALTER TABLE ezuser_accountkey RENAME TO ibexa_user_accountkey;
-- ibexa:sql-statement-separator
ALTER TABLE ezuser_role RENAME TO ibexa_user_role;
-- ibexa:sql-statement-separator
DROP INDEX ezuser_role_role_id;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_user_role_role_id ON ibexa_user_role (role_id);
-- ibexa:sql-statement-separator
DROP INDEX ezuser_role_contentobject_id;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_user_role_contentobject_id ON ibexa_user_role (contentobject_id);
-- ibexa:sql-statement-separator
ALTER TABLE ezuser_setting RENAME TO ibexa_user_setting;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_type_field_definition RENAME COLUMN contentclass_id TO content_type_id;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_type_group_assignment RENAME COLUMN contentclass_id TO content_type_id;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_type_name RENAME COLUMN contentclass_id TO content_type_id;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content RENAME COLUMN contentclass_id TO content_type_id;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_search_object_word_link RENAME COLUMN contentclass_id TO content_type_id;
-- ibexa:sql-statement-separator
DROP INDEX ibexa_content_type_version;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_type_status ON ibexa_content_type (version);
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_type RENAME COLUMN version TO status;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_type_field_definition RENAME COLUMN version TO status;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_type_field_definition_ml RENAME COLUMN version TO status;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_type_group_assignment RENAME COLUMN contentclass_version TO content_type_status;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_type_name RENAME COLUMN contentclass_version TO content_type_status;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_type_field_definition_ml RENAME COLUMN contentclass_attribute_id TO content_type_field_definition_id;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_field RENAME COLUMN contentclassattribute_id TO content_type_field_definition_id;
-- ibexa:sql-statement-separator
DROP INDEX ibexa_content_field_classattr_id;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_field_field_definition_id ON ibexa_content_field (content_type_field_definition_id);
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_relation RENAME COLUMN contentclassattribute_id TO content_type_field_definition_id;
-- ibexa:sql-statement-separator
DROP INDEX ibexa_content_relation_cca_id;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_content_relation_ccfd_id ON ibexa_content_relation (content_type_field_definition_id);
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_search_object_word_link RENAME COLUMN contentclass_attribute_id TO content_type_field_definition_id;
-- ibexa:sql-statement-separator
UPDATE ibexa_object_state_group SET identifier = 'ibexa_lock' WHERE identifier = 'ez_lock';
-- ibexa:sql-statement-separator
UPDATE ibexa_content_type_field_definition SET data_type_string = 'ibexa_string' WHERE data_type_string = 'ezstring';
-- ibexa:sql-statement-separator
UPDATE ibexa_content_field SET data_type_string = 'ibexa_string' WHERE data_type_string = 'ezstring';
