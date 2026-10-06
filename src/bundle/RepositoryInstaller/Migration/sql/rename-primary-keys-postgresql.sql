ALTER TABLE ibexa_binary_file RENAME CONSTRAINT ezbinaryfile_pkey TO ibexa_binary_file_pkey;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content RENAME CONSTRAINT ezcontentobject_pkey TO ibexa_content_pkey;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_bookmark RENAME CONSTRAINT ezcontentbrowsebookmark_pkey TO ibexa_content_bookmark_pkey;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_field RENAME CONSTRAINT ezcontentobject_attribute_pkey TO ibexa_content_field_pkey;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_language RENAME CONSTRAINT ezcontent_language_pkey TO ibexa_content_language_pkey;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_name RENAME CONSTRAINT ezcontentobject_name_pkey TO ibexa_content_name_pkey;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_relation RENAME CONSTRAINT ezcontentobject_link_pkey TO ibexa_content_relation_pkey;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_trash RENAME CONSTRAINT ezcontentobject_trash_pkey TO ibexa_content_trash_pkey;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_tree RENAME CONSTRAINT ezcontentobject_tree_pkey TO ibexa_content_tree_pkey;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_type RENAME CONSTRAINT ezcontentclass_pkey TO ibexa_content_type_pkey;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_type_field_definition
    RENAME CONSTRAINT ezcontentclass_attribute_pkey TO ibexa_content_type_field_definition_pkey;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_type_field_definition_ml
    RENAME CONSTRAINT ezcontentclass_attribute_ml_pkey TO ibexa_content_type_field_definition_ml_pkey;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_type_group RENAME CONSTRAINT ezcontentclassgroup_pkey TO ibexa_content_type_group_pkey;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_type_group_assignment
    RENAME CONSTRAINT ezcontentclass_classgroup_pkey TO ibexa_content_type_group_assignment_pkey;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_type_name RENAME CONSTRAINT ezcontentclass_name_pkey TO ibexa_content_type_name_pkey;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_content_version RENAME CONSTRAINT ezcontentobject_version_pkey TO ibexa_content_version_pkey;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_dfs_file RENAME CONSTRAINT ezdfsfile_pkey TO ibexa_dfs_file_pkey;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_image_file RENAME CONSTRAINT ezimagefile_pkey TO ibexa_image_file_pkey;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_keyword RENAME CONSTRAINT ezkeyword_pkey TO ibexa_keyword_pkey;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_keyword_field_link RENAME CONSTRAINT ezkeyword_attribute_link_pkey TO ibexa_keyword_field_link_pkey;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_map_location RENAME CONSTRAINT ezgmaplocation_pkey TO ibexa_map_location_pkey;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_media RENAME CONSTRAINT ezmedia_pkey TO ibexa_media_pkey;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_node_assignment RENAME CONSTRAINT eznode_assignment_pkey TO ibexa_node_assignment_pkey;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_notification RENAME CONSTRAINT eznotification_pkey TO ibexa_notification_pkey;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_object_state RENAME CONSTRAINT ezcobj_state_pkey TO ibexa_object_state_pkey;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_object_state_group RENAME CONSTRAINT ezcobj_state_group_pkey TO ibexa_object_state_group_pkey;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_object_state_group_language
    RENAME CONSTRAINT ezcobj_state_group_language_pkey TO ibexa_object_state_group_language_pkey;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_object_state_language
    RENAME CONSTRAINT ezcobj_state_language_pkey TO ibexa_object_state_language_pkey;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_object_state_link RENAME CONSTRAINT ezcobj_state_link_pkey TO ibexa_object_state_link_pkey;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_package RENAME CONSTRAINT ezpackage_pkey TO ibexa_package_pkey;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_policy RENAME CONSTRAINT ezpolicy_pkey TO ibexa_policy_pkey;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_policy_limitation RENAME CONSTRAINT ezpolicy_limitation_pkey TO ibexa_policy_limitation_pkey;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_policy_limitation_value
    RENAME CONSTRAINT ezpolicy_limitation_value_pkey TO ibexa_policy_limitation_value_pkey;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_role RENAME CONSTRAINT ezrole_pkey TO ibexa_role_pkey;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_search_object_word_link
    RENAME CONSTRAINT ezsearch_object_word_link_pkey TO ibexa_search_object_word_link_pkey;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_search_word RENAME CONSTRAINT ezsearch_word_pkey TO ibexa_search_word_pkey;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_section RENAME CONSTRAINT ezsection_pkey TO ibexa_section_pkey;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_site_data RENAME CONSTRAINT ezsite_data_pkey TO ibexa_site_data_pkey;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_url RENAME CONSTRAINT ezurl_pkey TO ibexa_url_pkey;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_url_alias RENAME CONSTRAINT ezurlalias_pkey TO ibexa_url_alias_pkey;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_url_alias_ml RENAME CONSTRAINT ezurlalias_ml_pkey TO ibexa_url_alias_ml_pkey;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_url_alias_ml_incr RENAME CONSTRAINT ezurlalias_ml_incr_pkey TO ibexa_url_alias_ml_incr_pkey;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_url_wildcard RENAME CONSTRAINT ezurlwildcard_pkey TO ibexa_url_wildcard_pkey;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_user RENAME CONSTRAINT ezuser_pkey TO ibexa_user_pkey;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_user_accountkey RENAME CONSTRAINT ezuser_accountkey_pkey TO ibexa_user_accountkey_pkey;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_user_preference RENAME CONSTRAINT ezpreferences_pkey TO ibexa_user_preference_pkey;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_user_role RENAME CONSTRAINT ezuser_role_pkey TO ibexa_user_role_pkey;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_user_setting RENAME CONSTRAINT ezuser_setting_pkey TO ibexa_user_setting_pkey;
