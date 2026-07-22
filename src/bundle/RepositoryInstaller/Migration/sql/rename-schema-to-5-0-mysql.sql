ALTER TABLE ezbinaryfile RENAME TO ibexa_binary_file;
-- ibexa:sql-statement-separator
ALTER TABLE ezcobj_state RENAME TO ibexa_object_state;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_object_state RENAME INDEX ezcobj_state_priority TO ibexa_object_state_priority;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_object_state RENAME INDEX ezcobj_state_lmask TO ibexa_object_state_lmask;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_object_state RENAME INDEX ezcobj_state_identifier TO ibexa_object_state_identifier;
-- ibexa:sql-statement-separator
ALTER TABLE ezcobj_state_group RENAME TO ibexa_object_state_group;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_object_state_group RENAME INDEX ezcobj_state_group_lmask TO ibexa_object_state_group_lmask;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_object_state_group RENAME INDEX ezcobj_state_group_identifier TO ibexa_object_state_group_identifier;
-- ibexa:sql-statement-separator
ALTER TABLE ezcobj_state_group_language RENAME TO ibexa_object_state_group_language;
-- ibexa:sql-statement-separator
ALTER TABLE ezcobj_state_language RENAME TO ibexa_object_state_language;
-- ibexa:sql-statement-separator
ALTER TABLE ezcobj_state_link RENAME TO ibexa_object_state_link;
-- ibexa:sql-statement-separator
ALTER TABLE ezcontent_language RENAME TO ibexa_content_language;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_language RENAME INDEX ezcontent_language_name TO ibexa_content_language_name;
-- ibexa:sql-statement-separator
ALTER TABLE ezcontentbrowsebookmark RENAME TO ibexa_content_bookmark;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_bookmark RENAME INDEX ezcontentbrowsebookmark_location TO ibexa_content_bookmark_location;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_bookmark RENAME INDEX ezcontentbrowsebookmark_user TO ibexa_content_bookmark_user;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_bookmark RENAME INDEX ezcontentbrowsebookmark_user_location TO ibexa_content_bookmark_user_location;
-- ibexa:sql-statement-separator
ALTER TABLE ezcontentclass RENAME TO ibexa_content_type;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_type RENAME INDEX ezcontentclass_version TO ibexa_content_type_version;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_type RENAME INDEX ezcontentclass_identifier TO ibexa_content_type_identifier;
-- ibexa:sql-statement-separator
ALTER TABLE ezcontentclass_attribute RENAME TO ibexa_content_type_field_definition;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_type_field_definition RENAME INDEX ezcontentclass_attr_ccid TO ibexa_content_type_field_definition_ctid;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_type_field_definition RENAME INDEX ezcontentclass_attr_dts TO ibexa_content_type_field_definition_dts;
-- ibexa:sql-statement-separator
ALTER TABLE ezcontentclass_attribute_ml RENAME TO ibexa_content_type_field_definition_ml;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_type_field_definition_ml DROP FOREIGN KEY ezcontentclass_attribute_ml_lang_fk;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_type_field_definition_ml ADD CONSTRAINT ibexa_content_type_field_definition_ml_lang_fk FOREIGN KEY (language_id) REFERENCES ibexa_content_language(id) ON DELETE CASCADE ON UPDATE CASCADE;
-- ibexa:sql-statement-separator
ALTER TABLE ezcontentclass_classgroup RENAME TO ibexa_content_type_group_assignment;
-- ibexa:sql-statement-separator
ALTER TABLE ezcontentclass_name RENAME TO ibexa_content_type_name;
-- ibexa:sql-statement-separator
ALTER TABLE ezcontentclassgroup RENAME TO ibexa_content_type_group;
-- ibexa:sql-statement-separator
ALTER TABLE ezcontentobject_tree RENAME TO ibexa_content_tree;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_tree RENAME INDEX ezcontentobject_tree_p_node_id TO ibexa_content_tree_p_node_id;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_tree RENAME INDEX ezcontentobject_tree_path_ident TO ibexa_content_tree_path_ident;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_tree RENAME INDEX ezcontentobject_tree_contentobject_id_path_string TO ibexa_content_tree_contentobject_id_path_string;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_tree RENAME INDEX ezcontentobject_tree_co_id TO ibexa_content_tree_co_id;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_tree RENAME INDEX ezcontentobject_tree_depth TO ibexa_content_tree_depth;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_tree RENAME INDEX ezcontentobject_tree_path TO ibexa_content_tree_path;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_tree RENAME INDEX modified_subnode TO ibexa_content_modified_subnode;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_tree RENAME INDEX ezcontentobject_tree_remote_id TO ibexa_content_tree_remote_id;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_bookmark DROP FOREIGN KEY ezcontentbrowsebookmark_location_fk;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_bookmark ADD CONSTRAINT ibexa_content_bookmark_location_fk FOREIGN KEY (node_id) REFERENCES ibexa_content_tree(node_id) ON DELETE CASCADE;
-- ibexa:sql-statement-separator
ALTER TABLE ezcontentobject RENAME TO ibexa_content;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content RENAME INDEX ezcontentobject_classid TO ibexa_content_type_id;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content RENAME INDEX ezcontentobject_lmask TO ibexa_content_lmask;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content RENAME INDEX ezcontentobject_pub TO ibexa_content_pub;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content RENAME INDEX ezcontentobject_section TO ibexa_content_section;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content RENAME INDEX ezcontentobject_currentversion TO ibexa_content_currentversion;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content RENAME INDEX ezcontentobject_owner TO ibexa_content_owner;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content RENAME INDEX ezcontentobject_status TO ibexa_content_status;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content RENAME INDEX ezcontentobject_remote_id TO ibexa_content_remote_id;
-- ibexa:sql-statement-separator
ALTER TABLE ezcontentobject_attribute RENAME TO ibexa_content_field;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_field RENAME INDEX ezcontentobject_attribute_co_id_ver_lang_code TO ibexa_content_field_co_id_ver_lang_code;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_field RENAME INDEX ezcontentobject_classattr_id TO ibexa_content_field_classattr_id;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_field RENAME INDEX ezcontentobject_attribute_language_code TO ibexa_content_field_language_code;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_field RENAME INDEX ezcontentobject_attribute_co_id_ver TO ibexa_content_field_co_id_ver;
-- ibexa:sql-statement-separator
ALTER TABLE ezcontentobject_link RENAME TO ibexa_content_relation;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_relation RENAME INDEX ezco_link_to_co_id TO ibexa_content_relation_to_co_id;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_relation RENAME INDEX ezco_link_from TO ibexa_content_relation_from;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_relation RENAME INDEX ezco_link_cca_id TO ibexa_content_relation_cca_id;
-- ibexa:sql-statement-separator
ALTER TABLE ezcontentobject_name RENAME TO ibexa_content_name;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_name RENAME INDEX ezcontentobject_name_lang_id TO ibexa_content_name_lang_id;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_name RENAME INDEX ezcontentobject_name_cov_id TO ibexa_content_name_cov_id;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_name RENAME INDEX ezcontentobject_name_name TO ibexa_content_name_name;
-- ibexa:sql-statement-separator
ALTER TABLE ezcontentobject_trash RENAME TO ibexa_content_trash;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_trash RENAME INDEX ezcobj_trash_depth TO ibexa_content_trash_depth;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_trash RENAME INDEX ezcobj_trash_p_node_id TO ibexa_content_trash_p_node_id;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_trash RENAME INDEX ezcobj_trash_path_ident TO ibexa_content_trash_path_ident;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_trash RENAME INDEX ezcobj_trash_co_id TO ibexa_content_trash_co_id;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_trash RENAME INDEX ezcobj_trash_modified_subnode TO ibexa_content_trash_modified_subnode;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_trash RENAME INDEX ezcobj_trash_path TO ibexa_content_trash_path;
-- ibexa:sql-statement-separator
ALTER TABLE ezcontentobject_version RENAME TO ibexa_content_version;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_version RENAME INDEX ezcobj_version_status TO ibexa_content_version_status;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_version RENAME INDEX idx_object_version_objver TO ibexa_content_version_idx_ver;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_version RENAME INDEX ezcontobj_version_obj_status TO ibexa_content_version_idx_status;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_version RENAME INDEX ezcobj_version_creator_id TO ibexa_content_version_creator_id;
-- ibexa:sql-statement-separator
ALTER TABLE ezdfsfile RENAME TO ibexa_dfs_file;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_dfs_file RENAME INDEX ezdfsfile_name_trunk TO ibexa_dfs_file_name_trunk;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_dfs_file RENAME INDEX ezdfsfile_expired_name TO ibexa_dfs_file_expired_name;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_dfs_file RENAME INDEX ezdfsfile_name TO ibexa_dfs_file_name;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_dfs_file RENAME INDEX ezdfsfile_mtime TO ibexa_dfs_file_mtime;
-- ibexa:sql-statement-separator
ALTER TABLE ezgmaplocation RENAME TO ibexa_map_location;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_map_location RENAME INDEX latitude_longitude_key TO ibexa_map_location_latitude_longitude_key;
-- ibexa:sql-statement-separator
ALTER TABLE ezimagefile RENAME TO ibexa_image_file;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_image_file RENAME INDEX ezimagefile_file TO ibexa_image_file_file;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_image_file RENAME INDEX ezimagefile_coid TO ibexa_image_file_coid;
-- ibexa:sql-statement-separator
ALTER TABLE ezkeyword RENAME TO ibexa_keyword;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_keyword RENAME INDEX ezkeyword_keyword TO ibexa_keyword_keyword;
-- ibexa:sql-statement-separator
ALTER TABLE ezkeyword_attribute_link RENAME TO ibexa_keyword_field_link;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_keyword_field_link RENAME INDEX ezkeyword_attr_link_oaid TO ibexa_keyword_field_link_oaid;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_keyword_field_link RENAME INDEX ezkeyword_attr_link_kid_oaid TO ibexa_keyword_field_link_kid_oaid;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_keyword_field_link RENAME INDEX ezkeyword_attr_link_oaid_ver TO ibexa_keyword_field_link_oaid_ver;
-- ibexa:sql-statement-separator
ALTER TABLE ezmedia RENAME TO ibexa_media;
-- ibexa:sql-statement-separator
ALTER TABLE eznode_assignment RENAME TO ibexa_node_assignment;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_node_assignment RENAME INDEX eznode_assignment_is_main TO ibexa_node_assignment_is_main;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_node_assignment RENAME INDEX eznode_assignment_coid_cov TO ibexa_node_assignment_coid_cov;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_node_assignment RENAME INDEX eznode_assignment_parent_node TO ibexa_node_assignment_parent_node;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_node_assignment RENAME INDEX eznode_assignment_co_version TO ibexa_node_assignment_co_version;
-- ibexa:sql-statement-separator
ALTER TABLE eznotification RENAME TO ibexa_notification;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_notification RENAME INDEX eznotification_owner_is_pending TO ibexa_notification_owner_is_pending;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_notification RENAME INDEX eznotification_owner TO ibexa_notification_owner;
-- ibexa:sql-statement-separator
ALTER TABLE ezpackage RENAME TO ibexa_package;
-- ibexa:sql-statement-separator
ALTER TABLE ezpolicy RENAME TO ibexa_policy;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_policy RENAME INDEX ezpolicy_role_id TO ibexa_policy_role_id;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_policy RENAME INDEX ezpolicy_original_id TO ibexa_policy_original_id;
-- ibexa:sql-statement-separator
ALTER TABLE ezpolicy_limitation RENAME TO ibexa_policy_limitation;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_policy_limitation RENAME INDEX policy_id TO ibexa_policy_id;
-- ibexa:sql-statement-separator
ALTER TABLE ezpolicy_limitation_value RENAME TO ibexa_policy_limitation_value;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_policy_limitation_value RENAME INDEX ezpolicy_limit_value_limit_id TO ibexa_policy_limit_value_limit_id;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_policy_limitation_value RENAME INDEX ezpolicy_limitation_value_val TO ibexa_policy_limitation_value_val;
-- ibexa:sql-statement-separator
ALTER TABLE ezpreferences RENAME TO ibexa_user_preference;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_user_preference RENAME INDEX ezpreferences_user_id_idx TO ibexa_user_preference_user_id_idx;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_user_preference RENAME INDEX ezpreferences_name TO ibexa_user_preference_name;
-- ibexa:sql-statement-separator
ALTER TABLE ezrole RENAME TO ibexa_role;
-- ibexa:sql-statement-separator
ALTER TABLE ezsearch_object_word_link RENAME TO ibexa_search_object_word_link;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_search_object_word_link RENAME INDEX ezsearch_object_word_link_object TO ibexa_search_object_word_link_object;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_search_object_word_link RENAME INDEX ezsearch_object_word_link_identifier TO ibexa_search_object_word_link_identifier;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_search_object_word_link RENAME INDEX ezsearch_object_word_link_integer_value TO ibexa_search_object_word_link_integer_value;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_search_object_word_link RENAME INDEX ezsearch_object_word_link_word TO ibexa_search_object_word_link_word;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_search_object_word_link RENAME INDEX ezsearch_object_word_link_frequency TO ibexa_search_object_word_link_frequency;
-- ibexa:sql-statement-separator
ALTER TABLE ezsearch_word RENAME TO ibexa_search_word;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_search_word RENAME INDEX ezsearch_word_word_i TO ibexa_search_word_word_i;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_search_word RENAME INDEX ezsearch_word_obj_count TO ibexa_search_word_obj_count;
-- ibexa:sql-statement-separator
ALTER TABLE ezsection RENAME TO ibexa_section;
-- ibexa:sql-statement-separator
ALTER TABLE ezsite_data RENAME TO ibexa_site_data;
-- ibexa:sql-statement-separator
ALTER TABLE ezurl RENAME TO ibexa_url;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_url RENAME INDEX ezurl_url TO ibexa_url_url;
-- ibexa:sql-statement-separator
ALTER TABLE ezurl_object_link RENAME TO ibexa_url_content_link;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_url_content_link RENAME INDEX ezurl_ol_coa_id TO ibexa_url_ol_coa_id;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_url_content_link RENAME INDEX ezurl_ol_url_id TO ibexa_url_ol_url_id;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_url_content_link RENAME INDEX ezurl_ol_coa_version TO ibexa_url_ol_coa_version;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_url_content_link RENAME INDEX ezurl_ol_coa_id_cav TO ibexa_url_ol_coa_id_cav;
-- ibexa:sql-statement-separator
ALTER TABLE ezurlalias RENAME TO ibexa_url_alias;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_url_alias RENAME INDEX ezurlalias_source_md5 TO ibexa_url_alias_source_md5;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_url_alias RENAME INDEX ezurlalias_wcard_fwd TO ibexa_url_alias_wcard_fwd;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_url_alias RENAME INDEX ezurlalias_forward_to_id TO ibexa_url_alias_forward_to_id;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_url_alias RENAME INDEX ezurlalias_imp_wcard_fwd TO ibexa_url_alias_imp_wcard_fwd;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_url_alias RENAME INDEX ezurlalias_source_url TO ibexa_url_alias_source_url;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_url_alias RENAME INDEX ezurlalias_desturl TO ibexa_url_alias_desturl;
-- ibexa:sql-statement-separator
ALTER TABLE ezurlalias_ml RENAME TO ibexa_url_alias_ml;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_url_alias_ml RENAME INDEX ezurlalias_ml_actt_org_al TO ibexa_url_alias_ml_actt_org_al;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_url_alias_ml RENAME INDEX ezurlalias_ml_text_lang TO ibexa_url_alias_ml_text_lang;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_url_alias_ml RENAME INDEX ezurlalias_ml_par_act_id_lnk TO ibexa_url_alias_ml_par_act_id_lnk;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_url_alias_ml RENAME INDEX ezurlalias_ml_par_lnk_txt TO ibexa_url_alias_ml_par_lnk_txt;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_url_alias_ml RENAME INDEX ezurlalias_ml_act_org TO ibexa_url_alias_ml_act_org;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_url_alias_ml RENAME INDEX ezurlalias_ml_text TO ibexa_url_alias_ml_text;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_url_alias_ml RENAME INDEX ezurlalias_ml_link TO ibexa_url_alias_ml_link;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_url_alias_ml RENAME INDEX ezurlalias_ml_id TO ibexa_url_alias_ml_id;
-- ibexa:sql-statement-separator
ALTER TABLE ezurlalias_ml_incr RENAME TO ibexa_url_alias_ml_incr;
-- ibexa:sql-statement-separator
ALTER TABLE ezurlwildcard RENAME TO ibexa_url_wildcard;
-- ibexa:sql-statement-separator
ALTER TABLE ezuser RENAME TO ibexa_user;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_user RENAME INDEX ezuser_login TO ibexa_user_login;
-- ibexa:sql-statement-separator
ALTER TABLE ezuser_accountkey RENAME TO ibexa_user_accountkey;
-- ibexa:sql-statement-separator
ALTER TABLE ezuser_role RENAME TO ibexa_user_role;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_user_role RENAME INDEX ezuser_role_role_id TO ibexa_user_role_role_id;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_user_role RENAME INDEX ezuser_role_contentobject_id TO ibexa_user_role_contentobject_id;
-- ibexa:sql-statement-separator
ALTER TABLE ezuser_setting RENAME TO ibexa_user_setting;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_bookmark DROP FOREIGN KEY ezcontentbrowsebookmark_user_fk;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_bookmark ADD CONSTRAINT ibexa_content_bookmark_user_fk FOREIGN KEY (user_id) REFERENCES ibexa_user(contentobject_id) ON DELETE CASCADE;
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
ALTER TABLE ibexa_content_type RENAME INDEX ibexa_content_type_version TO ibexa_content_type_status;
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
ALTER TABLE ibexa_content_field RENAME INDEX ibexa_content_field_classattr_id TO ibexa_content_field_field_definition_id;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_relation RENAME COLUMN contentclassattribute_id TO content_type_field_definition_id;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_relation RENAME INDEX ibexa_content_relation_cca_id TO ibexa_content_relation_ccfd_id;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_search_object_word_link RENAME COLUMN contentclass_attribute_id TO content_type_field_definition_id;
-- ibexa:sql-statement-separator
UPDATE ibexa_object_state_group SET identifier = 'ibexa_lock' WHERE identifier = 'ez_lock';
-- ibexa:sql-statement-separator
UPDATE ibexa_content_type_field_definition SET data_type_string = 'ibexa_string' WHERE data_type_string = 'ezstring';
-- ibexa:sql-statement-separator
UPDATE ibexa_content_field SET data_type_string = 'ibexa_string' WHERE data_type_string = 'ezstring';
