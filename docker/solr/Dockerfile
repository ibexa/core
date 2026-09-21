ARG SOLR_VERSION=10.0.0

FROM alpine:3.22 as builder
ARG SOLR_VERSION
ARG SOLR_BUNDLE_BRANCH=6.0
RUN apk add --no-cache --upgrade bash git curl
RUN adduser --disabled-password user
USER user
WORKDIR /home/user
RUN git clone --depth=1 --branch "${SOLR_BUNDLE_BRANCH}" https://github.com/ibexa/solr.git solr
RUN ./solr/bin/generate-solr-config.sh --destination-dir=config --solr-version="${SOLR_VERSION}"

FROM solr:${SOLR_VERSION}
LABEL org.opencontainers.image.source=https://github.com/ibexa/core/blob/6.0/docker/solr/Dockerfile
LABEL org.opencontainers.image.description="Configured Ibexa Solr Bundle image, created by 'Build and publish Solr Docker image' GHA workflow"
LABEL org.opencontainers.image.licenses=GPL-2.0-only
USER root
RUN rm -rf server/solr/configsets/_default/conf/*
USER solr
COPY --from=builder /home/user/config server/solr/configsets/_default/conf
CMD ["solr-precreate", "collection1"]
