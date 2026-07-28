CREATE INDEX ezco_link_cca_id ON ezcontentobject_link (contentclassattribute_id);
-- ibexa:sql-statement-separator
CREATE INDEX ezcontentclass_attr_dts ON ezcontentclass_attribute (data_type_string);
-- ibexa:sql-statement-separator
CREATE INDEX ezcontentobject_attribute_co_id_ver ON ezcontentobject_attribute (contentobject_id, version);
-- ibexa:sql-statement-separator
CREATE INDEX ezurl_ol_coa_id_cav ON ezurl_object_link (contentobject_attribute_id, contentobject_attribute_version);
