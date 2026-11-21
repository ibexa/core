CREATE TABLE ibexa_search_object_word_link_new (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    content_type_field_definition_id INTEGER DEFAULT 0 NOT NULL,
    content_type_id INTEGER DEFAULT 0 NOT NULL,
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
    language_id BIGINT DEFAULT 0 NOT NULL,
    is_main_and_always_available BOOLEAN DEFAULT '0' NOT NULL,
    CONSTRAINT ibexa_search_object_word_link_language_fk FOREIGN KEY (language_id)
        REFERENCES ibexa_content_language (id) ON DELETE RESTRICT ON UPDATE CASCADE
);
-- ibexa:sql-statement-separator
INSERT INTO ibexa_search_object_word_link_new (id, content_type_field_definition_id, content_type_id, contentobject_id, frequency, identifier, integer_value, next_word_id, placement, prev_word_id, published, section_id, word_id, language_id, is_main_and_always_available)
SELECT id, content_type_field_definition_id, content_type_id, contentobject_id, frequency, identifier, integer_value, next_word_id, placement, prev_word_id, published, section_id, word_id, language_id, is_main_and_always_available FROM ibexa_search_object_word_link;
-- ibexa:sql-statement-separator
DROP TABLE ibexa_search_object_word_link;
-- ibexa:sql-statement-separator
ALTER TABLE ibexa_search_object_word_link_new RENAME TO ibexa_search_object_word_link;
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_search_object_word_link_object ON ibexa_search_object_word_link (contentobject_id);
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_search_object_word_link_identifier ON ibexa_search_object_word_link (identifier);
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_search_object_word_link_integer_value ON ibexa_search_object_word_link (integer_value);
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_search_object_word_link_word ON ibexa_search_object_word_link (word_id);
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_search_object_word_link_frequency ON ibexa_search_object_word_link (frequency);
-- ibexa:sql-statement-separator
CREATE INDEX ibexa_search_object_word_link_language ON ibexa_search_object_word_link (language_id);
