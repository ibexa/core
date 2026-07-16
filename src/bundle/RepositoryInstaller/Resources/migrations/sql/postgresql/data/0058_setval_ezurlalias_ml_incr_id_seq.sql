SELECT SETVAL('ezurlalias_ml_incr_id_seq', COALESCE(MAX(id), 1) ) FROM ezurlalias_ml_incr;
