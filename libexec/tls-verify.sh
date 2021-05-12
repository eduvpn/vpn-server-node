#!/bin/sh

# OpenVPN --tls-verify script
#
# OpenVPN executes this script multiple times for each certificate in the 
# chain. We are only interested in the client certificate (depth 0)

if [ "${1}" -eq 0 ]; then
    if [ -z "${PROFILE_ID}" ] || [ -z "${X509_0_OU}" ]; then
        /usr/bin/logger -s -p user.warning "${0}: PROFILE_ID or X509_0_OU environment variable not set"
        exit 1
    fi

    if [ "${PROFILE_ID}" != "${X509_0_OU}" ]; then
        /usr/bin/logger -s -p user.warning "${0}: PROFILE_ID '${PROFILE_ID}' does not match client certificate OU '${X509_0_OU}'"
        exit 1
    fi
fi
