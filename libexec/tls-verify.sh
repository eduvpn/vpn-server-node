#!/bin/sh

# OpenVPN script to be used by --tls-verify option.
# OpenVPN executes script for each certificate in the client's certificate-chain
# We are only interested in the client certificate (depth 0)
if [ "${1}" -eq 0 ]; then
    if [ -z "${PROFILE_ID}" ] || [ -z "${X509_0_OU}" ]; then
        logger -s -p user.warning "${0}: PROFILE_ID OR X509_0_OU not set"
        exit 1
    fi

    if [ "${PROFILE_ID}" != "${X509_0_OU}" ]; then
        logger -s -p user.warning "${0}: PROFILE_ID '${PROFILE_ID}' does not match client's certificate OU '${X509_0_OU}'"
        exit 1
    fi
fi