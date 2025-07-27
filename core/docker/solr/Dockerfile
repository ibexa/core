FROM alpine:3.18.5 as builder
RUN apk add --no-cache --upgrade bash git curl
RUN adduser --disabled-password user
USER user
WORKDIR /home/user
RUN git clone --depth=1 https://github.com/ibexa/solr.git solr
RUN ./solr/bin/generate-solr-config.sh --destination-dir=config --solr-version=8.6.3

FROM solr:8.6.3
LABEL org.opencontainers.image.source=https://github.com/ibexa/core/blob/main/docker/solr/Dockerfile
LABEL org.opencontainers.image.description="Configured Ibexa Solr Bundle image, created by 'Build and publish Solr Docker image' GHA workflow"
LABEL org.opencontainers.image.licenses=GPL-2.0-only
USER root
RUN rm -rf server/solr/configsets/_default/conf/*
USER solr
COPY --from=builder /home/user/config server/solr/configsets/_default/conf
CMD ["solr-precreate", "collection1"]
