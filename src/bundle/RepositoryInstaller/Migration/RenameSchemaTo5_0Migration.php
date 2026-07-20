<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\RepositoryInstaller\Migration;

use DateTimeImmutable;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Ibexa\Contracts\DoctrineMigrations\Migrations\IbexaMigrationInterface;

/**
 * Renames the legacy eZ Publish-style core database schema (ez*) to the Ibexa naming
 * scheme (ibexa_*) introduced in 5.0. This is the incremental diff on top of
 * InstallSchemaMigration's 4.6.0 baseline; a fresh 6.0 install runs both in sequence,
 * while an existing 4.6 install only needs this one to reach the 5.0/6.0 shape.
 */
final class RenameSchemaTo5_0Migration extends AbstractMigration implements IbexaMigrationInterface
{
    public function getDescription(): string
    {
        return 'Renames the legacy core database schema to the Ibexa naming scheme (introduced in 5.0)';
    }

    public static function getTargetVersion(): string
    {
        return '5.0.0';
    }

    public static function getCreationDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-07-20 00:00:00');
    }

    public function up(Schema $schema): void
    {
        if ($this->platform instanceof AbstractMySQLPlatform) {
            $this->addSql(<<<'SQL'
            ALTER TABLE ezbinaryfile RENAME TO ibexa_binary_file
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcobj_state RENAME TO ibexa_object_state
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_object_state RENAME INDEX ezcobj_state_priority TO ibexa_object_state_priority
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_object_state RENAME INDEX ezcobj_state_lmask TO ibexa_object_state_lmask
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_object_state RENAME INDEX ezcobj_state_identifier TO ibexa_object_state_identifier
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcobj_state_group RENAME TO ibexa_object_state_group
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_object_state_group RENAME INDEX ezcobj_state_group_lmask TO ibexa_object_state_group_lmask
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_object_state_group RENAME INDEX ezcobj_state_group_identifier TO ibexa_object_state_group_identifier
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcobj_state_group_language RENAME TO ibexa_object_state_group_language
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcobj_state_language RENAME TO ibexa_object_state_language
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcobj_state_link RENAME TO ibexa_object_state_link
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcontent_language RENAME TO ibexa_content_language
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_language RENAME INDEX ezcontent_language_name TO ibexa_content_language_name
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcontentbrowsebookmark RENAME TO ibexa_content_bookmark
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_bookmark RENAME INDEX ezcontentbrowsebookmark_location TO ibexa_content_bookmark_location
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_bookmark RENAME INDEX ezcontentbrowsebookmark_user TO ibexa_content_bookmark_user
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_bookmark RENAME INDEX ezcontentbrowsebookmark_user_location TO ibexa_content_bookmark_user_location
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcontentclass RENAME TO ibexa_content_type
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_type RENAME INDEX ezcontentclass_version TO ibexa_content_type_version
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_type RENAME INDEX ezcontentclass_identifier TO ibexa_content_type_identifier
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcontentclass_attribute RENAME TO ibexa_content_type_field_definition
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_type_field_definition RENAME INDEX ezcontentclass_attr_ccid TO ibexa_content_type_field_definition_ctid
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_type_field_definition RENAME INDEX ezcontentclass_attr_dts TO ibexa_content_type_field_definition_dts
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcontentclass_attribute_ml RENAME TO ibexa_content_type_field_definition_ml
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_type_field_definition_ml DROP FOREIGN KEY ezcontentclass_attribute_ml_lang_fk
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_type_field_definition_ml ADD CONSTRAINT ibexa_content_type_field_definition_ml_lang_fk FOREIGN KEY (language_id) REFERENCES ibexa_content_language(id) ON DELETE CASCADE ON UPDATE CASCADE
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcontentclass_classgroup RENAME TO ibexa_content_type_group_assignment
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcontentclass_name RENAME TO ibexa_content_type_name
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcontentclassgroup RENAME TO ibexa_content_type_group
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcontentobject_tree RENAME TO ibexa_content_tree
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_tree RENAME INDEX ezcontentobject_tree_p_node_id TO ibexa_content_tree_p_node_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_tree RENAME INDEX ezcontentobject_tree_path_ident TO ibexa_content_tree_path_ident
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_tree RENAME INDEX ezcontentobject_tree_contentobject_id_path_string TO ibexa_content_tree_contentobject_id_path_string
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_tree RENAME INDEX ezcontentobject_tree_co_id TO ibexa_content_tree_co_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_tree RENAME INDEX ezcontentobject_tree_depth TO ibexa_content_tree_depth
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_tree RENAME INDEX ezcontentobject_tree_path TO ibexa_content_tree_path
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_tree RENAME INDEX modified_subnode TO ibexa_content_modified_subnode
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_tree RENAME INDEX ezcontentobject_tree_remote_id TO ibexa_content_tree_remote_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_bookmark DROP FOREIGN KEY ezcontentbrowsebookmark_location_fk
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_bookmark ADD CONSTRAINT ibexa_content_bookmark_location_fk FOREIGN KEY (node_id) REFERENCES ibexa_content_tree(node_id) ON DELETE CASCADE
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcontentobject RENAME TO ibexa_content
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content RENAME INDEX ezcontentobject_classid TO ibexa_content_type_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content RENAME INDEX ezcontentobject_lmask TO ibexa_content_lmask
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content RENAME INDEX ezcontentobject_pub TO ibexa_content_pub
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content RENAME INDEX ezcontentobject_section TO ibexa_content_section
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content RENAME INDEX ezcontentobject_currentversion TO ibexa_content_currentversion
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content RENAME INDEX ezcontentobject_owner TO ibexa_content_owner
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content RENAME INDEX ezcontentobject_status TO ibexa_content_status
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content RENAME INDEX ezcontentobject_remote_id TO ibexa_content_remote_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcontentobject_attribute RENAME TO ibexa_content_field
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_field RENAME INDEX ezcontentobject_attribute_co_id_ver_lang_code TO ibexa_content_field_co_id_ver_lang_code
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_field RENAME INDEX ezcontentobject_classattr_id TO ibexa_content_field_classattr_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_field RENAME INDEX ezcontentobject_attribute_language_code TO ibexa_content_field_language_code
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_field RENAME INDEX ezcontentobject_attribute_co_id_ver TO ibexa_content_field_co_id_ver
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcontentobject_link RENAME TO ibexa_content_relation
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_relation RENAME INDEX ezco_link_to_co_id TO ibexa_content_relation_to_co_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_relation RENAME INDEX ezco_link_from TO ibexa_content_relation_from
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_relation RENAME INDEX ezco_link_cca_id TO ibexa_content_relation_cca_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcontentobject_name RENAME TO ibexa_content_name
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_name RENAME INDEX ezcontentobject_name_lang_id TO ibexa_content_name_lang_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_name RENAME INDEX ezcontentobject_name_cov_id TO ibexa_content_name_cov_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_name RENAME INDEX ezcontentobject_name_name TO ibexa_content_name_name
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcontentobject_trash RENAME TO ibexa_content_trash
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_trash RENAME INDEX ezcobj_trash_depth TO ibexa_content_trash_depth
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_trash RENAME INDEX ezcobj_trash_p_node_id TO ibexa_content_trash_p_node_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_trash RENAME INDEX ezcobj_trash_path_ident TO ibexa_content_trash_path_ident
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_trash RENAME INDEX ezcobj_trash_co_id TO ibexa_content_trash_co_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_trash RENAME INDEX ezcobj_trash_modified_subnode TO ibexa_content_trash_modified_subnode
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_trash RENAME INDEX ezcobj_trash_path TO ibexa_content_trash_path
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcontentobject_version RENAME TO ibexa_content_version
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_version RENAME INDEX ezcobj_version_status TO ibexa_content_version_status
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_version RENAME INDEX idx_object_version_objver TO ibexa_content_version_idx_ver
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_version RENAME INDEX ezcontobj_version_obj_status TO ibexa_content_version_idx_status
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_version RENAME INDEX ezcobj_version_creator_id TO ibexa_content_version_creator_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezdfsfile RENAME TO ibexa_dfs_file
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_dfs_file RENAME INDEX ezdfsfile_name_trunk TO ibexa_dfs_file_name_trunk
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_dfs_file RENAME INDEX ezdfsfile_expired_name TO ibexa_dfs_file_expired_name
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_dfs_file RENAME INDEX ezdfsfile_name TO ibexa_dfs_file_name
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_dfs_file RENAME INDEX ezdfsfile_mtime TO ibexa_dfs_file_mtime
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezgmaplocation RENAME TO ibexa_map_location
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_map_location RENAME INDEX latitude_longitude_key TO ibexa_map_location_latitude_longitude_key
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezimagefile RENAME TO ibexa_image_file
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_image_file RENAME INDEX ezimagefile_file TO ibexa_image_file_file
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_image_file RENAME INDEX ezimagefile_coid TO ibexa_image_file_coid
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezkeyword RENAME TO ibexa_keyword
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_keyword RENAME INDEX ezkeyword_keyword TO ibexa_keyword_keyword
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezkeyword_attribute_link RENAME TO ibexa_keyword_field_link
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_keyword_field_link RENAME INDEX ezkeyword_attr_link_oaid TO ibexa_keyword_field_link_oaid
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_keyword_field_link RENAME INDEX ezkeyword_attr_link_kid_oaid TO ibexa_keyword_field_link_kid_oaid
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_keyword_field_link RENAME INDEX ezkeyword_attr_link_oaid_ver TO ibexa_keyword_field_link_oaid_ver
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezmedia RENAME TO ibexa_media
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE eznode_assignment RENAME TO ibexa_node_assignment
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_node_assignment RENAME INDEX eznode_assignment_is_main TO ibexa_node_assignment_is_main
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_node_assignment RENAME INDEX eznode_assignment_coid_cov TO ibexa_node_assignment_coid_cov
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_node_assignment RENAME INDEX eznode_assignment_parent_node TO ibexa_node_assignment_parent_node
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_node_assignment RENAME INDEX eznode_assignment_co_version TO ibexa_node_assignment_co_version
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE eznotification RENAME TO ibexa_notification
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_notification RENAME INDEX eznotification_owner_is_pending TO ibexa_notification_owner_is_pending
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_notification RENAME INDEX eznotification_owner TO ibexa_notification_owner
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezpackage RENAME TO ibexa_package
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezpolicy RENAME TO ibexa_policy
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_policy RENAME INDEX ezpolicy_role_id TO ibexa_policy_role_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_policy RENAME INDEX ezpolicy_original_id TO ibexa_policy_original_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezpolicy_limitation RENAME TO ibexa_policy_limitation
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_policy_limitation RENAME INDEX policy_id TO ibexa_policy_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezpolicy_limitation_value RENAME TO ibexa_policy_limitation_value
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_policy_limitation_value RENAME INDEX ezpolicy_limit_value_limit_id TO ibexa_policy_limit_value_limit_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_policy_limitation_value RENAME INDEX ezpolicy_limitation_value_val TO ibexa_policy_limitation_value_val
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezpreferences RENAME TO ibexa_user_preference
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_user_preference RENAME INDEX ezpreferences_user_id_idx TO ibexa_user_preference_user_id_idx
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_user_preference RENAME INDEX ezpreferences_name TO ibexa_user_preference_name
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezrole RENAME TO ibexa_role
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezsearch_object_word_link RENAME TO ibexa_search_object_word_link
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_search_object_word_link RENAME INDEX ezsearch_object_word_link_object TO ibexa_search_object_word_link_object
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_search_object_word_link RENAME INDEX ezsearch_object_word_link_identifier TO ibexa_search_object_word_link_identifier
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_search_object_word_link RENAME INDEX ezsearch_object_word_link_integer_value TO ibexa_search_object_word_link_integer_value
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_search_object_word_link RENAME INDEX ezsearch_object_word_link_word TO ibexa_search_object_word_link_word
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_search_object_word_link RENAME INDEX ezsearch_object_word_link_frequency TO ibexa_search_object_word_link_frequency
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezsearch_word RENAME TO ibexa_search_word
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_search_word RENAME INDEX ezsearch_word_word_i TO ibexa_search_word_word_i
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_search_word RENAME INDEX ezsearch_word_obj_count TO ibexa_search_word_obj_count
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezsection RENAME TO ibexa_section
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezsite_data RENAME TO ibexa_site_data
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezurl RENAME TO ibexa_url
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_url RENAME INDEX ezurl_url TO ibexa_url_url
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezurl_object_link RENAME TO ibexa_url_content_link
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_url_content_link RENAME INDEX ezurl_ol_coa_id TO ibexa_url_ol_coa_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_url_content_link RENAME INDEX ezurl_ol_url_id TO ibexa_url_ol_url_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_url_content_link RENAME INDEX ezurl_ol_coa_version TO ibexa_url_ol_coa_version
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_url_content_link RENAME INDEX ezurl_ol_coa_id_cav TO ibexa_url_ol_coa_id_cav
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezurlalias RENAME TO ibexa_url_alias
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_url_alias RENAME INDEX ezurlalias_source_md5 TO ibexa_url_alias_source_md5
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_url_alias RENAME INDEX ezurlalias_wcard_fwd TO ibexa_url_alias_wcard_fwd
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_url_alias RENAME INDEX ezurlalias_forward_to_id TO ibexa_url_alias_forward_to_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_url_alias RENAME INDEX ezurlalias_imp_wcard_fwd TO ibexa_url_alias_imp_wcard_fwd
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_url_alias RENAME INDEX ezurlalias_source_url TO ibexa_url_alias_source_url
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_url_alias RENAME INDEX ezurlalias_desturl TO ibexa_url_alias_desturl
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezurlalias_ml RENAME TO ibexa_url_alias_ml
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_url_alias_ml RENAME INDEX ezurlalias_ml_actt_org_al TO ibexa_url_alias_ml_actt_org_al
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_url_alias_ml RENAME INDEX ezurlalias_ml_text_lang TO ibexa_url_alias_ml_text_lang
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_url_alias_ml RENAME INDEX ezurlalias_ml_par_act_id_lnk TO ibexa_url_alias_ml_par_act_id_lnk
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_url_alias_ml RENAME INDEX ezurlalias_ml_par_lnk_txt TO ibexa_url_alias_ml_par_lnk_txt
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_url_alias_ml RENAME INDEX ezurlalias_ml_act_org TO ibexa_url_alias_ml_act_org
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_url_alias_ml RENAME INDEX ezurlalias_ml_text TO ibexa_url_alias_ml_text
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_url_alias_ml RENAME INDEX ezurlalias_ml_link TO ibexa_url_alias_ml_link
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_url_alias_ml RENAME INDEX ezurlalias_ml_id TO ibexa_url_alias_ml_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezurlalias_ml_incr RENAME TO ibexa_url_alias_ml_incr
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezurlwildcard RENAME TO ibexa_url_wildcard
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezuser RENAME TO ibexa_user
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_user RENAME INDEX ezuser_login TO ibexa_user_login
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezuser_accountkey RENAME TO ibexa_user_accountkey
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezuser_role RENAME TO ibexa_user_role
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_user_role RENAME INDEX ezuser_role_role_id TO ibexa_user_role_role_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_user_role RENAME INDEX ezuser_role_contentobject_id TO ibexa_user_role_contentobject_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezuser_setting RENAME TO ibexa_user_setting
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_bookmark DROP FOREIGN KEY ezcontentbrowsebookmark_user_fk
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_bookmark ADD CONSTRAINT ibexa_content_bookmark_user_fk FOREIGN KEY (user_id) REFERENCES ibexa_user(contentobject_id) ON DELETE CASCADE
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_type_field_definition RENAME COLUMN contentclass_id TO content_type_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_type_group_assignment RENAME COLUMN contentclass_id TO content_type_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_type_name RENAME COLUMN contentclass_id TO content_type_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content RENAME COLUMN contentclass_id TO content_type_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_search_object_word_link RENAME COLUMN contentclass_id TO content_type_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_type RENAME INDEX ibexa_content_type_version TO ibexa_content_type_status
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_type RENAME COLUMN version TO status
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_type_field_definition RENAME COLUMN version TO status
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_type_field_definition_ml RENAME COLUMN version TO status
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_type_group_assignment RENAME COLUMN contentclass_version TO content_type_status
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_type_name RENAME COLUMN contentclass_version TO content_type_status
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_type_field_definition_ml RENAME COLUMN contentclass_attribute_id TO content_type_field_definition_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_field RENAME COLUMN contentclassattribute_id TO content_type_field_definition_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_field RENAME INDEX ibexa_content_field_classattr_id TO ibexa_content_field_field_definition_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_relation RENAME COLUMN contentclassattribute_id TO content_type_field_definition_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_relation RENAME INDEX ibexa_content_relation_cca_id TO ibexa_content_relation_ccfd_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_search_object_word_link RENAME COLUMN contentclass_attribute_id TO content_type_field_definition_id
            SQL);

            $this->addSql(<<<'SQL'
            UPDATE ibexa_object_state_group SET identifier = 'ibexa_lock' WHERE identifier = 'ez_lock'
            SQL);
            $this->addSql(<<<'SQL'
            UPDATE ibexa_content_type_field_definition SET data_type_string = 'ibexa_string' WHERE data_type_string = 'ezstring'
            SQL);
            $this->addSql(<<<'SQL'
            UPDATE ibexa_content_field SET data_type_string = 'ibexa_string' WHERE data_type_string = 'ezstring'
            SQL);
        } elseif ($this->platform instanceof PostgreSQLPlatform) {
            $this->addSql(<<<'SQL'
            ALTER TABLE ezbinaryfile RENAME TO ibexa_binary_file
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcobj_state RENAME TO ibexa_object_state
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezcobj_state_priority RENAME TO ibexa_object_state_priority
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezcobj_state_lmask RENAME TO ibexa_object_state_lmask
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezcobj_state_identifier RENAME TO ibexa_object_state_identifier
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcobj_state_group RENAME TO ibexa_object_state_group
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezcobj_state_group_lmask RENAME TO ibexa_object_state_group_lmask
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezcobj_state_group_identifier RENAME TO ibexa_object_state_group_identifier
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcobj_state_group_language RENAME TO ibexa_object_state_group_language
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcobj_state_language RENAME TO ibexa_object_state_language
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcobj_state_link RENAME TO ibexa_object_state_link
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcontent_language RENAME TO ibexa_content_language
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezcontent_language_name RENAME TO ibexa_content_language_name
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcontentbrowsebookmark RENAME TO ibexa_content_bookmark
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezcontentbrowsebookmark_location RENAME TO ibexa_content_bookmark_location
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezcontentbrowsebookmark_user RENAME TO ibexa_content_bookmark_user
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezcontentbrowsebookmark_user_location RENAME TO ibexa_content_bookmark_user_location
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcontentclass RENAME TO ibexa_content_type
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezcontentclass_version RENAME TO ibexa_content_type_version
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezcontentclass_identifier RENAME TO ibexa_content_type_identifier
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcontentclass_attribute RENAME TO ibexa_content_type_field_definition
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezcontentclass_attr_ccid RENAME TO ibexa_content_type_field_definition_ctid
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezcontentclass_attr_dts RENAME TO ibexa_content_type_field_definition_dts
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcontentclass_attribute_ml RENAME TO ibexa_content_type_field_definition_ml
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_type_field_definition_ml DROP CONSTRAINT ezcontentclass_attribute_ml_lang_fk
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_type_field_definition_ml ADD CONSTRAINT ibexa_content_type_field_definition_ml_lang_fk FOREIGN KEY (language_id) REFERENCES ibexa_content_language(id) ON DELETE CASCADE ON UPDATE CASCADE
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcontentclass_classgroup RENAME TO ibexa_content_type_group_assignment
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcontentclass_name RENAME TO ibexa_content_type_name
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcontentclassgroup RENAME TO ibexa_content_type_group
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcontentobject_tree RENAME TO ibexa_content_tree
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezcontentobject_tree_p_node_id RENAME TO ibexa_content_tree_p_node_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezcontentobject_tree_path_ident RENAME TO ibexa_content_tree_path_ident
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezcontentobject_tree_contentobject_id_path_string RENAME TO ibexa_content_tree_contentobject_id_path_string
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezcontentobject_tree_co_id RENAME TO ibexa_content_tree_co_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezcontentobject_tree_depth RENAME TO ibexa_content_tree_depth
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezcontentobject_tree_path RENAME TO ibexa_content_tree_path
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX modified_subnode RENAME TO ibexa_content_modified_subnode
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezcontentobject_tree_remote_id RENAME TO ibexa_content_tree_remote_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_bookmark DROP CONSTRAINT ezcontentbrowsebookmark_location_fk
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_bookmark ADD CONSTRAINT ibexa_content_bookmark_location_fk FOREIGN KEY (node_id) REFERENCES ibexa_content_tree(node_id) ON DELETE CASCADE
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcontentobject RENAME TO ibexa_content
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezcontentobject_classid RENAME TO ibexa_content_type_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezcontentobject_lmask RENAME TO ibexa_content_lmask
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezcontentobject_pub RENAME TO ibexa_content_pub
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezcontentobject_section RENAME TO ibexa_content_section
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezcontentobject_currentversion RENAME TO ibexa_content_currentversion
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezcontentobject_owner RENAME TO ibexa_content_owner
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezcontentobject_status RENAME TO ibexa_content_status
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezcontentobject_remote_id RENAME TO ibexa_content_remote_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcontentobject_attribute RENAME TO ibexa_content_field
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezcontentobject_attribute_co_id_ver_lang_code RENAME TO ibexa_content_field_co_id_ver_lang_code
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezcontentobject_classattr_id RENAME TO ibexa_content_field_classattr_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezcontentobject_attribute_language_code RENAME TO ibexa_content_field_language_code
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezcontentobject_attribute_co_id_ver RENAME TO ibexa_content_field_co_id_ver
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcontentobject_link RENAME TO ibexa_content_relation
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezco_link_to_co_id RENAME TO ibexa_content_relation_to_co_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezco_link_from RENAME TO ibexa_content_relation_from
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezco_link_cca_id RENAME TO ibexa_content_relation_cca_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcontentobject_name RENAME TO ibexa_content_name
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezcontentobject_name_lang_id RENAME TO ibexa_content_name_lang_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezcontentobject_name_cov_id RENAME TO ibexa_content_name_cov_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezcontentobject_name_name RENAME TO ibexa_content_name_name
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcontentobject_trash RENAME TO ibexa_content_trash
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezcobj_trash_depth RENAME TO ibexa_content_trash_depth
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezcobj_trash_p_node_id RENAME TO ibexa_content_trash_p_node_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezcobj_trash_path_ident RENAME TO ibexa_content_trash_path_ident
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezcobj_trash_co_id RENAME TO ibexa_content_trash_co_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezcobj_trash_modified_subnode RENAME TO ibexa_content_trash_modified_subnode
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezcobj_trash_path RENAME TO ibexa_content_trash_path
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcontentobject_version RENAME TO ibexa_content_version
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezcobj_version_status RENAME TO ibexa_content_version_status
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX idx_object_version_objver RENAME TO ibexa_content_version_idx_ver
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezcontobj_version_obj_status RENAME TO ibexa_content_version_idx_status
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezcobj_version_creator_id RENAME TO ibexa_content_version_creator_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezdfsfile RENAME TO ibexa_dfs_file
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezdfsfile_name_trunk RENAME TO ibexa_dfs_file_name_trunk
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezdfsfile_expired_name RENAME TO ibexa_dfs_file_expired_name
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezdfsfile_name RENAME TO ibexa_dfs_file_name
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezdfsfile_mtime RENAME TO ibexa_dfs_file_mtime
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezgmaplocation RENAME TO ibexa_map_location
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX latitude_longitude_key RENAME TO ibexa_map_location_latitude_longitude_key
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezimagefile RENAME TO ibexa_image_file
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezimagefile_file RENAME TO ibexa_image_file_file
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezimagefile_coid RENAME TO ibexa_image_file_coid
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezkeyword RENAME TO ibexa_keyword
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezkeyword_keyword RENAME TO ibexa_keyword_keyword
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezkeyword_attribute_link RENAME TO ibexa_keyword_field_link
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezkeyword_attr_link_oaid RENAME TO ibexa_keyword_field_link_oaid
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezkeyword_attr_link_kid_oaid RENAME TO ibexa_keyword_field_link_kid_oaid
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezkeyword_attr_link_oaid_ver RENAME TO ibexa_keyword_field_link_oaid_ver
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezmedia RENAME TO ibexa_media
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE eznode_assignment RENAME TO ibexa_node_assignment
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX eznode_assignment_is_main RENAME TO ibexa_node_assignment_is_main
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX eznode_assignment_coid_cov RENAME TO ibexa_node_assignment_coid_cov
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX eznode_assignment_parent_node RENAME TO ibexa_node_assignment_parent_node
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX eznode_assignment_co_version RENAME TO ibexa_node_assignment_co_version
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE eznotification RENAME TO ibexa_notification
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX eznotification_owner_is_pending RENAME TO ibexa_notification_owner_is_pending
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX eznotification_owner RENAME TO ibexa_notification_owner
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezpackage RENAME TO ibexa_package
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezpolicy RENAME TO ibexa_policy
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezpolicy_role_id RENAME TO ibexa_policy_role_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezpolicy_original_id RENAME TO ibexa_policy_original_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezpolicy_limitation RENAME TO ibexa_policy_limitation
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX policy_id RENAME TO ibexa_policy_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezpolicy_limitation_value RENAME TO ibexa_policy_limitation_value
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezpolicy_limit_value_limit_id RENAME TO ibexa_policy_limit_value_limit_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezpolicy_limitation_value_val RENAME TO ibexa_policy_limitation_value_val
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezpreferences RENAME TO ibexa_user_preference
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezpreferences_user_id_idx RENAME TO ibexa_user_preference_user_id_idx
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezpreferences_name RENAME TO ibexa_user_preference_name
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezrole RENAME TO ibexa_role
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezsearch_object_word_link RENAME TO ibexa_search_object_word_link
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezsearch_object_word_link_object RENAME TO ibexa_search_object_word_link_object
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezsearch_object_word_link_identifier RENAME TO ibexa_search_object_word_link_identifier
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezsearch_object_word_link_integer_value RENAME TO ibexa_search_object_word_link_integer_value
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezsearch_object_word_link_word RENAME TO ibexa_search_object_word_link_word
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezsearch_object_word_link_frequency RENAME TO ibexa_search_object_word_link_frequency
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezsearch_word RENAME TO ibexa_search_word
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezsearch_word_word_i RENAME TO ibexa_search_word_word_i
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezsearch_word_obj_count RENAME TO ibexa_search_word_obj_count
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezsection RENAME TO ibexa_section
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezsite_data RENAME TO ibexa_site_data
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezurl RENAME TO ibexa_url
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezurl_url RENAME TO ibexa_url_url
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezurl_object_link RENAME TO ibexa_url_content_link
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezurl_ol_coa_id RENAME TO ibexa_url_ol_coa_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezurl_ol_url_id RENAME TO ibexa_url_ol_url_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezurl_ol_coa_version RENAME TO ibexa_url_ol_coa_version
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezurl_ol_coa_id_cav RENAME TO ibexa_url_ol_coa_id_cav
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezurlalias RENAME TO ibexa_url_alias
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezurlalias_source_md5 RENAME TO ibexa_url_alias_source_md5
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezurlalias_wcard_fwd RENAME TO ibexa_url_alias_wcard_fwd
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezurlalias_forward_to_id RENAME TO ibexa_url_alias_forward_to_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezurlalias_imp_wcard_fwd RENAME TO ibexa_url_alias_imp_wcard_fwd
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezurlalias_source_url RENAME TO ibexa_url_alias_source_url
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezurlalias_desturl RENAME TO ibexa_url_alias_desturl
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezurlalias_ml RENAME TO ibexa_url_alias_ml
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezurlalias_ml_actt_org_al RENAME TO ibexa_url_alias_ml_actt_org_al
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezurlalias_ml_text_lang RENAME TO ibexa_url_alias_ml_text_lang
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezurlalias_ml_par_act_id_lnk RENAME TO ibexa_url_alias_ml_par_act_id_lnk
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezurlalias_ml_par_lnk_txt RENAME TO ibexa_url_alias_ml_par_lnk_txt
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezurlalias_ml_act_org RENAME TO ibexa_url_alias_ml_act_org
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezurlalias_ml_text RENAME TO ibexa_url_alias_ml_text
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezurlalias_ml_link RENAME TO ibexa_url_alias_ml_link
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezurlalias_ml_id RENAME TO ibexa_url_alias_ml_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezurlalias_ml_incr RENAME TO ibexa_url_alias_ml_incr
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezurlwildcard RENAME TO ibexa_url_wildcard
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezuser RENAME TO ibexa_user
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezuser_login RENAME TO ibexa_user_login
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezuser_accountkey RENAME TO ibexa_user_accountkey
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezuser_role RENAME TO ibexa_user_role
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezuser_role_role_id RENAME TO ibexa_user_role_role_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ezuser_role_contentobject_id RENAME TO ibexa_user_role_contentobject_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezuser_setting RENAME TO ibexa_user_setting
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_bookmark DROP CONSTRAINT ezcontentbrowsebookmark_user_fk
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_bookmark ADD CONSTRAINT ibexa_content_bookmark_user_fk FOREIGN KEY (user_id) REFERENCES ibexa_user(contentobject_id) ON DELETE CASCADE
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_type_field_definition RENAME COLUMN contentclass_id TO content_type_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_type_group_assignment RENAME COLUMN contentclass_id TO content_type_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_type_name RENAME COLUMN contentclass_id TO content_type_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content RENAME COLUMN contentclass_id TO content_type_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_search_object_word_link RENAME COLUMN contentclass_id TO content_type_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ibexa_content_type_version RENAME TO ibexa_content_type_status
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_type RENAME COLUMN version TO status
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_type_field_definition RENAME COLUMN version TO status
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_type_field_definition_ml RENAME COLUMN version TO status
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_type_group_assignment RENAME COLUMN contentclass_version TO content_type_status
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_type_name RENAME COLUMN contentclass_version TO content_type_status
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_type_field_definition_ml RENAME COLUMN contentclass_attribute_id TO content_type_field_definition_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_field RENAME COLUMN contentclassattribute_id TO content_type_field_definition_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ibexa_content_field_classattr_id RENAME TO ibexa_content_field_field_definition_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_relation RENAME COLUMN contentclassattribute_id TO content_type_field_definition_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER INDEX ibexa_content_relation_cca_id RENAME TO ibexa_content_relation_ccfd_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_search_object_word_link RENAME COLUMN contentclass_attribute_id TO content_type_field_definition_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER SEQUENCE ezcobj_state_group_id_seq RENAME TO ibexa_object_state_group_id_seq
            SQL);
            $this->addSql(<<<'SQL'
            ALTER SEQUENCE ezcobj_state_id_seq RENAME TO ibexa_object_state_id_seq
            SQL);
            $this->addSql(<<<'SQL'
            ALTER SEQUENCE ezcontentbrowsebookmark_id_seq RENAME TO ibexa_content_bookmark_id_seq
            SQL);
            $this->addSql(<<<'SQL'
            ALTER SEQUENCE ezcontentclass_attribute_id_seq RENAME TO ibexa_content_type_field_definition_id_seq
            SQL);
            $this->addSql(<<<'SQL'
            ALTER SEQUENCE ezcontentclass_id_seq RENAME TO ibexa_content_type_id_seq
            SQL);
            $this->addSql(<<<'SQL'
            ALTER SEQUENCE ezcontentclassgroup_id_seq RENAME TO ibexa_content_type_group_id_seq
            SQL);
            $this->addSql(<<<'SQL'
            ALTER SEQUENCE ezcontentobject_attribute_id_seq RENAME TO ibexa_content_field_id_seq
            SQL);
            $this->addSql(<<<'SQL'
            ALTER SEQUENCE ezcontentobject_id_seq RENAME TO ibexa_content_id_seq
            SQL);
            $this->addSql(<<<'SQL'
            ALTER SEQUENCE ezcontentobject_link_id_seq RENAME TO ibexa_content_relation_id_seq
            SQL);
            $this->addSql(<<<'SQL'
            ALTER SEQUENCE ezcontentobject_tree_node_id_seq RENAME TO ibexa_content_tree_node_id_seq
            SQL);
            $this->addSql(<<<'SQL'
            ALTER SEQUENCE ezcontentobject_version_id_seq RENAME TO ibexa_content_version_id_seq
            SQL);
            $this->addSql(<<<'SQL'
            ALTER SEQUENCE ezimagefile_id_seq RENAME TO ibexa_image_file_id_seq
            SQL);
            $this->addSql(<<<'SQL'
            ALTER SEQUENCE ezkeyword_attribute_link_id_seq RENAME TO ibexa_keyword_field_link_id_seq
            SQL);
            $this->addSql(<<<'SQL'
            ALTER SEQUENCE ezkeyword_id_seq RENAME TO ibexa_keyword_id_seq
            SQL);
            $this->addSql(<<<'SQL'
            ALTER SEQUENCE eznode_assignment_id_seq RENAME TO ibexa_node_assignment_id_seq
            SQL);
            $this->addSql(<<<'SQL'
            ALTER SEQUENCE eznotification_id_seq RENAME TO ibexa_notification_id_seq
            SQL);
            $this->addSql(<<<'SQL'
            ALTER SEQUENCE ezpackage_id_seq RENAME TO ibexa_package_id_seq
            SQL);
            $this->addSql(<<<'SQL'
            ALTER SEQUENCE ezpolicy_id_seq RENAME TO ibexa_policy_id_seq
            SQL);
            $this->addSql(<<<'SQL'
            ALTER SEQUENCE ezpolicy_limitation_id_seq RENAME TO ibexa_policy_limitation_id_seq
            SQL);
            $this->addSql(<<<'SQL'
            ALTER SEQUENCE ezpolicy_limitation_value_id_seq RENAME TO ibexa_policy_limitation_value_id_seq
            SQL);
            $this->addSql(<<<'SQL'
            ALTER SEQUENCE ezpreferences_id_seq RENAME TO ibexa_user_preference_id_seq
            SQL);
            $this->addSql(<<<'SQL'
            ALTER SEQUENCE ezrole_id_seq RENAME TO ibexa_role_id_seq
            SQL);
            $this->addSql(<<<'SQL'
            ALTER SEQUENCE ezsearch_object_word_link_id_seq RENAME TO ibexa_search_object_word_link_id_seq
            SQL);
            $this->addSql(<<<'SQL'
            ALTER SEQUENCE ezsearch_word_id_seq RENAME TO ibexa_search_word_id_seq
            SQL);
            $this->addSql(<<<'SQL'
            ALTER SEQUENCE ezsection_id_seq RENAME TO ibexa_section_id_seq
            SQL);
            $this->addSql(<<<'SQL'
            ALTER SEQUENCE ezurl_id_seq RENAME TO ibexa_url_id_seq
            SQL);
            $this->addSql(<<<'SQL'
            ALTER SEQUENCE ezurlalias_id_seq RENAME TO ibexa_url_alias_id_seq
            SQL);
            $this->addSql(<<<'SQL'
            ALTER SEQUENCE ezurlalias_ml_incr_id_seq RENAME TO ibexa_url_alias_ml_incr_id_seq
            SQL);
            $this->addSql(<<<'SQL'
            ALTER SEQUENCE ezurlwildcard_id_seq RENAME TO ibexa_url_wildcard_id_seq
            SQL);
            $this->addSql(<<<'SQL'
            ALTER SEQUENCE ezuser_accountkey_id_seq RENAME TO ibexa_user_accountkey_id_seq
            SQL);
            $this->addSql(<<<'SQL'
            ALTER SEQUENCE ezuser_role_id_seq RENAME TO ibexa_user_role_id_seq
            SQL);

            $this->addSql(<<<'SQL'
            UPDATE ibexa_object_state_group SET identifier = 'ibexa_lock' WHERE identifier = 'ez_lock'
            SQL);
            $this->addSql(<<<'SQL'
            UPDATE ibexa_content_type_field_definition SET data_type_string = 'ibexa_string' WHERE data_type_string = 'ezstring'
            SQL);
            $this->addSql(<<<'SQL'
            UPDATE ibexa_content_field SET data_type_string = 'ibexa_string' WHERE data_type_string = 'ezstring'
            SQL);
        } elseif ($this->platform instanceof SqlitePlatform) {
            $this->addSql(<<<'SQL'
            ALTER TABLE ezbinaryfile RENAME TO ibexa_binary_file
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcobj_state RENAME TO ibexa_object_state
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezcobj_state_priority
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_object_state_priority ON ibexa_object_state (priority)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezcobj_state_lmask
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_object_state_lmask ON ibexa_object_state (language_mask)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezcobj_state_identifier
            SQL);
            $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX ibexa_object_state_identifier ON ibexa_object_state (group_id, identifier)
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcobj_state_group RENAME TO ibexa_object_state_group
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezcobj_state_group_lmask
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_object_state_group_lmask ON ibexa_object_state_group (language_mask)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezcobj_state_group_identifier
            SQL);
            $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX ibexa_object_state_group_identifier ON ibexa_object_state_group (identifier)
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcobj_state_group_language RENAME TO ibexa_object_state_group_language
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcobj_state_language RENAME TO ibexa_object_state_language
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcobj_state_link RENAME TO ibexa_object_state_link
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcontent_language RENAME TO ibexa_content_language
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezcontent_language_name
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_content_language_name ON ibexa_content_language (name)
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcontentbrowsebookmark RENAME TO ibexa_content_bookmark
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezcontentbrowsebookmark_location
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_content_bookmark_location ON ibexa_content_bookmark (node_id)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezcontentbrowsebookmark_user
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_content_bookmark_user ON ibexa_content_bookmark (user_id)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezcontentbrowsebookmark_user_location
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_content_bookmark_user_location ON ibexa_content_bookmark (user_id, node_id)
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcontentclass RENAME TO ibexa_content_type
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezcontentclass_version
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_content_type_version ON ibexa_content_type (version)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezcontentclass_identifier
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_content_type_identifier ON ibexa_content_type (identifier, version)
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcontentclass_attribute RENAME TO ibexa_content_type_field_definition
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezcontentclass_attr_ccid
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_content_type_field_definition_ctid ON ibexa_content_type_field_definition (contentclass_id)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezcontentclass_attr_dts
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_content_type_field_definition_dts ON ibexa_content_type_field_definition (data_type_string)
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcontentclass_attribute_ml RENAME TO ibexa_content_type_field_definition_ml
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezcontentclass_attribute_ml_lang_fk
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_content_type_field_definition_ml_lang_fk ON ibexa_content_type_field_definition_ml (language_id)
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcontentclass_classgroup RENAME TO ibexa_content_type_group_assignment
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcontentclass_name RENAME TO ibexa_content_type_name
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcontentclassgroup RENAME TO ibexa_content_type_group
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcontentobject_tree RENAME TO ibexa_content_tree
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezcontentobject_tree_p_node_id
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_content_tree_p_node_id ON ibexa_content_tree (parent_node_id)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezcontentobject_tree_path_ident
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_content_tree_path_ident ON ibexa_content_tree (path_identification_string)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezcontentobject_tree_contentobject_id_path_string
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_content_tree_contentobject_id_path_string ON ibexa_content_tree (path_string, contentobject_id)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezcontentobject_tree_co_id
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_content_tree_co_id ON ibexa_content_tree (contentobject_id)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezcontentobject_tree_depth
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_content_tree_depth ON ibexa_content_tree (depth)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezcontentobject_tree_path
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_content_tree_path ON ibexa_content_tree (path_string)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX modified_subnode
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_content_modified_subnode ON ibexa_content_tree (modified_subnode)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezcontentobject_tree_remote_id
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_content_tree_remote_id ON ibexa_content_tree (remote_id)
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcontentobject RENAME TO ibexa_content
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezcontentobject_classid
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_content_type_id ON ibexa_content (contentclass_id)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezcontentobject_lmask
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_content_lmask ON ibexa_content (language_mask)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezcontentobject_pub
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_content_pub ON ibexa_content (published)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezcontentobject_section
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_content_section ON ibexa_content (section_id)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezcontentobject_currentversion
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_content_currentversion ON ibexa_content (current_version)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezcontentobject_owner
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_content_owner ON ibexa_content (owner_id)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezcontentobject_status
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_content_status ON ibexa_content (status)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezcontentobject_remote_id
            SQL);
            $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX ibexa_content_remote_id ON ibexa_content (remote_id)
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcontentobject_attribute RENAME TO ibexa_content_field
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezcontentobject_attribute_co_id_ver_lang_code
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_content_field_co_id_ver_lang_code ON ibexa_content_field (contentobject_id, version, language_code)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezcontentobject_classattr_id
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_content_field_classattr_id ON ibexa_content_field (contentclassattribute_id)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezcontentobject_attribute_language_code
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_content_field_language_code ON ibexa_content_field (language_code)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezcontentobject_attribute_co_id_ver
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_content_field_co_id_ver ON ibexa_content_field (contentobject_id, version)
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcontentobject_link RENAME TO ibexa_content_relation
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezco_link_to_co_id
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_content_relation_to_co_id ON ibexa_content_relation (to_contentobject_id)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezco_link_from
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_content_relation_from ON ibexa_content_relation (from_contentobject_id, from_contentobject_version, contentclassattribute_id)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezco_link_cca_id
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_content_relation_cca_id ON ibexa_content_relation (contentclassattribute_id)
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcontentobject_name RENAME TO ibexa_content_name
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezcontentobject_name_lang_id
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_content_name_lang_id ON ibexa_content_name (language_id)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezcontentobject_name_cov_id
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_content_name_cov_id ON ibexa_content_name (content_version)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezcontentobject_name_name
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_content_name_name ON ibexa_content_name (name)
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcontentobject_trash RENAME TO ibexa_content_trash
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezcobj_trash_depth
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_content_trash_depth ON ibexa_content_trash (depth)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezcobj_trash_p_node_id
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_content_trash_p_node_id ON ibexa_content_trash (parent_node_id)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezcobj_trash_path_ident
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_content_trash_path_ident ON ibexa_content_trash (path_identification_string)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezcobj_trash_co_id
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_content_trash_co_id ON ibexa_content_trash (contentobject_id)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezcobj_trash_modified_subnode
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_content_trash_modified_subnode ON ibexa_content_trash (modified_subnode)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezcobj_trash_path
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_content_trash_path ON ibexa_content_trash (path_string)
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezcontentobject_version RENAME TO ibexa_content_version
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezcobj_version_status
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_content_version_status ON ibexa_content_version (status)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX idx_object_version_objver
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_content_version_idx_ver ON ibexa_content_version (contentobject_id, version)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezcontobj_version_obj_status
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_content_version_idx_status ON ibexa_content_version (contentobject_id, status)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezcobj_version_creator_id
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_content_version_creator_id ON ibexa_content_version (creator_id)
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezdfsfile RENAME TO ibexa_dfs_file
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezdfsfile_name_trunk
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_dfs_file_name_trunk ON ibexa_dfs_file (name_trunk)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezdfsfile_expired_name
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_dfs_file_expired_name ON ibexa_dfs_file (expired, name)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezdfsfile_name
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_dfs_file_name ON ibexa_dfs_file (name)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezdfsfile_mtime
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_dfs_file_mtime ON ibexa_dfs_file (mtime)
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezgmaplocation RENAME TO ibexa_map_location
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX latitude_longitude_key
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_map_location_latitude_longitude_key ON ibexa_map_location (latitude, longitude)
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezimagefile RENAME TO ibexa_image_file
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezimagefile_file
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_image_file_file ON ibexa_image_file (filepath)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezimagefile_coid
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_image_file_coid ON ibexa_image_file (contentobject_attribute_id)
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezkeyword RENAME TO ibexa_keyword
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezkeyword_keyword
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_keyword_keyword ON ibexa_keyword (keyword)
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezkeyword_attribute_link RENAME TO ibexa_keyword_field_link
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezkeyword_attr_link_oaid
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_keyword_field_link_oaid ON ibexa_keyword_field_link (objectattribute_id)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezkeyword_attr_link_kid_oaid
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_keyword_field_link_kid_oaid ON ibexa_keyword_field_link (keyword_id, objectattribute_id)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezkeyword_attr_link_oaid_ver
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_keyword_field_link_oaid_ver ON ibexa_keyword_field_link (objectattribute_id, version)
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezmedia RENAME TO ibexa_media
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE eznode_assignment RENAME TO ibexa_node_assignment
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX eznode_assignment_is_main
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_node_assignment_is_main ON ibexa_node_assignment (is_main)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX eznode_assignment_coid_cov
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_node_assignment_coid_cov ON ibexa_node_assignment (contentobject_id, contentobject_version)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX eznode_assignment_parent_node
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_node_assignment_parent_node ON ibexa_node_assignment (parent_node)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX eznode_assignment_co_version
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_node_assignment_co_version ON ibexa_node_assignment (contentobject_version)
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE eznotification RENAME TO ibexa_notification
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX eznotification_owner_is_pending
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_notification_owner_is_pending ON ibexa_notification (owner_id, is_pending)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX eznotification_owner
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_notification_owner ON ibexa_notification (owner_id)
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezpackage RENAME TO ibexa_package
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezpolicy RENAME TO ibexa_policy
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezpolicy_role_id
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_policy_role_id ON ibexa_policy (role_id)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezpolicy_original_id
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_policy_original_id ON ibexa_policy (original_id)
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezpolicy_limitation RENAME TO ibexa_policy_limitation
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX policy_id
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_policy_id ON ibexa_policy_limitation (policy_id)
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezpolicy_limitation_value RENAME TO ibexa_policy_limitation_value
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezpolicy_limit_value_limit_id
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_policy_limit_value_limit_id ON ibexa_policy_limitation_value (limitation_id)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezpolicy_limitation_value_val
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_policy_limitation_value_val ON ibexa_policy_limitation_value (value)
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezpreferences RENAME TO ibexa_user_preference
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezpreferences_user_id_idx
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_user_preference_user_id_idx ON ibexa_user_preference (user_id, name)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezpreferences_name
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_user_preference_name ON ibexa_user_preference (name)
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezrole RENAME TO ibexa_role
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezsearch_object_word_link RENAME TO ibexa_search_object_word_link
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezsearch_object_word_link_object
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_search_object_word_link_object ON ibexa_search_object_word_link (contentobject_id)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezsearch_object_word_link_identifier
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_search_object_word_link_identifier ON ibexa_search_object_word_link (identifier)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezsearch_object_word_link_integer_value
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_search_object_word_link_integer_value ON ibexa_search_object_word_link (integer_value)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezsearch_object_word_link_word
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_search_object_word_link_word ON ibexa_search_object_word_link (word_id)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezsearch_object_word_link_frequency
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_search_object_word_link_frequency ON ibexa_search_object_word_link (frequency)
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezsearch_word RENAME TO ibexa_search_word
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezsearch_word_word_i
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_search_word_word_i ON ibexa_search_word (word)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezsearch_word_obj_count
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_search_word_obj_count ON ibexa_search_word (object_count)
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezsection RENAME TO ibexa_section
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezsite_data RENAME TO ibexa_site_data
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezurl RENAME TO ibexa_url
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezurl_url
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_url_url ON ibexa_url (url)
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezurl_object_link RENAME TO ibexa_url_content_link
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezurl_ol_coa_id
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_url_ol_coa_id ON ibexa_url_content_link (contentobject_attribute_id)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezurl_ol_url_id
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_url_ol_url_id ON ibexa_url_content_link (url_id)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezurl_ol_coa_version
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_url_ol_coa_version ON ibexa_url_content_link (contentobject_attribute_version)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezurl_ol_coa_id_cav
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_url_ol_coa_id_cav ON ibexa_url_content_link (contentobject_attribute_id, contentobject_attribute_version)
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezurlalias RENAME TO ibexa_url_alias
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezurlalias_source_md5
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_url_alias_source_md5 ON ibexa_url_alias (source_md5)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezurlalias_wcard_fwd
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_url_alias_wcard_fwd ON ibexa_url_alias (is_wildcard, forward_to_id)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezurlalias_forward_to_id
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_url_alias_forward_to_id ON ibexa_url_alias (forward_to_id)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezurlalias_imp_wcard_fwd
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_url_alias_imp_wcard_fwd ON ibexa_url_alias (is_imported, is_wildcard, forward_to_id)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezurlalias_source_url
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_url_alias_source_url ON ibexa_url_alias (source_url)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezurlalias_desturl
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_url_alias_desturl ON ibexa_url_alias (destination_url)
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezurlalias_ml RENAME TO ibexa_url_alias_ml
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezurlalias_ml_actt_org_al
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_url_alias_ml_actt_org_al ON ibexa_url_alias_ml (action_type, is_original, is_alias)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezurlalias_ml_text_lang
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_url_alias_ml_text_lang ON ibexa_url_alias_ml (text, lang_mask, parent)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezurlalias_ml_par_act_id_lnk
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_url_alias_ml_par_act_id_lnk ON ibexa_url_alias_ml ("action", id, link, parent)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezurlalias_ml_par_lnk_txt
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_url_alias_ml_par_lnk_txt ON ibexa_url_alias_ml (parent, text, link)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezurlalias_ml_act_org
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_url_alias_ml_act_org ON ibexa_url_alias_ml ("action", is_original)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezurlalias_ml_text
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_url_alias_ml_text ON ibexa_url_alias_ml (text, id, link)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezurlalias_ml_link
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_url_alias_ml_link ON ibexa_url_alias_ml (link)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezurlalias_ml_id
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_url_alias_ml_id ON ibexa_url_alias_ml (id)
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezurlalias_ml_incr RENAME TO ibexa_url_alias_ml_incr
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezurlwildcard RENAME TO ibexa_url_wildcard
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezuser RENAME TO ibexa_user
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezuser_login
            SQL);
            $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX ibexa_user_login ON ibexa_user (login)
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezuser_accountkey RENAME TO ibexa_user_accountkey
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezuser_role RENAME TO ibexa_user_role
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezuser_role_role_id
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_user_role_role_id ON ibexa_user_role (role_id)
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ezuser_role_contentobject_id
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_user_role_contentobject_id ON ibexa_user_role (contentobject_id)
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ezuser_setting RENAME TO ibexa_user_setting
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_type_field_definition RENAME COLUMN contentclass_id TO content_type_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_type_group_assignment RENAME COLUMN contentclass_id TO content_type_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_type_name RENAME COLUMN contentclass_id TO content_type_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content RENAME COLUMN contentclass_id TO content_type_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_search_object_word_link RENAME COLUMN contentclass_id TO content_type_id
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ibexa_content_type_version
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_content_type_status ON ibexa_content_type (version)
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_type RENAME COLUMN version TO status
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_type_field_definition RENAME COLUMN version TO status
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_type_field_definition_ml RENAME COLUMN version TO status
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_type_group_assignment RENAME COLUMN contentclass_version TO content_type_status
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_type_name RENAME COLUMN contentclass_version TO content_type_status
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_type_field_definition_ml RENAME COLUMN contentclass_attribute_id TO content_type_field_definition_id
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_field RENAME COLUMN contentclassattribute_id TO content_type_field_definition_id
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ibexa_content_field_classattr_id
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_content_field_field_definition_id ON ibexa_content_field (content_type_field_definition_id)
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_content_relation RENAME COLUMN contentclassattribute_id TO content_type_field_definition_id
            SQL);
            $this->addSql(<<<'SQL'
            DROP INDEX ibexa_content_relation_cca_id
            SQL);
            $this->addSql(<<<'SQL'
            CREATE INDEX ibexa_content_relation_ccfd_id ON ibexa_content_relation (content_type_field_definition_id)
            SQL);
            $this->addSql(<<<'SQL'
            ALTER TABLE ibexa_search_object_word_link RENAME COLUMN contentclass_attribute_id TO content_type_field_definition_id
            SQL);

            $this->addSql(<<<'SQL'
            UPDATE ibexa_object_state_group SET identifier = 'ibexa_lock' WHERE identifier = 'ez_lock'
            SQL);
            $this->addSql(<<<'SQL'
            UPDATE ibexa_content_type_field_definition SET data_type_string = 'ibexa_string' WHERE data_type_string = 'ezstring'
            SQL);
            $this->addSql(<<<'SQL'
            UPDATE ibexa_content_field SET data_type_string = 'ibexa_string' WHERE data_type_string = 'ezstring'
            SQL);
        }
    }
}
