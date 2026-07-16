CREATE TABLE ibexa_url_wildcard (id SERIAL NOT NULL, destination_url TEXT NOT NULL, source_url TEXT NOT NULL, type INT DEFAULT 0 NOT NULL, PRIMARY KEY(id));
