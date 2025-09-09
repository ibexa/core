# Start from the official PostgreSQL 18 RC1 image
FROM postgres:18rc1

# Override the default CMD so that io_method=sync is always enabled
CMD ["postgres", "-c", "io_method=sync"]
