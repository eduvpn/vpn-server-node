# Changelog

## 1.0.11 (2018-03-29)
- increase `keepalive` for UDP, remove it for TCP

## 1.0.10 (2018-03-15)
- firewall config template change, a port is an integer, not a string

## 1.0.9 (2018-02-25)
- remove hacks for supporting 2.3 clients when `tlsCrypt` is enabled

## 1.0.8 (2018-01-17)
- autodetect RHEL/CentOS/Fedora or Debian/Ubuntu, no longer need the `--debian` 
  flag for `vpn-server-node-generate-firewall`

## 1.0.7 (2017-12-17)
- cleanup autoloading

## 1.0.6 (2017-12-15)
- push `comp-lzo no` to client when compression is enabled to disable 
  "adaptive compression" in the client
- update `eduvpn/common`

## 1.0.5 (2017-11-20)
- support PHPUnit 6
- add `certificate-info` script to show when the OpenVPN server certificates
  will expire
- restructure server configuration file generation
- Psalm fixes
- no longer push `comp-lzo no`, not needed as we don't actually use compression
- use same IPv6 default gateway routes on 2.3 clients as are used for 2.4 
  clients
- add tests for testing server configuration generation
- support disabling compression

## 1.0.4 (2017-10-25)
- remove `--profile` option for generating server configuration, generate for
  all profiles by default

## 1.0.3 (2017-10-20)
- only push `explicit-exit-notify` when using UDP
- support for "auth-script-openvpn" plugin for more efficient 2FA integration

## 1.0.2 (2017-09-29)
- expire 2FA connections after 8 hours, i.e. require new OTP code (#15)

## 1.0.1 (2017-07-28)
- allow specifying source IP range(s) for INPUT packet filter (#13)

## 1.0.0 (2017-07-13)
- initial release
