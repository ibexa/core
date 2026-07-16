ALTER TABLE ibexa_token ADD CONSTRAINT ibexa_token_type_id_fk FOREIGN KEY (type_id) REFERENCES ibexa_token_type (id) ON DELETE CASCADE;
