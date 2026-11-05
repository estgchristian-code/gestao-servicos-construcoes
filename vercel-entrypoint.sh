#!/bin/sh
set -e

if [ -n "${CA_CERT_BASE64:-}" ]; then
    mkdir -p /app/storage/ssl
    printf '%s' "$CA_CERT_BASE64" | base64 -d > /app/storage/ssl/ca.pem
    chmod 600 /app/storage/ssl/ca.pem
    MYSQL_ATTR_SSL_CA="${MYSQL_ATTR_SSL_CA:-/app/storage/ssl/ca.pem}"
    export MYSQL_ATTR_SSL_CA
    echo "Certificado CA gravado em /app/storage/ssl/ca.pem"
fi

exec frankenphp run --config /etc/frankenphp/Caddyfile