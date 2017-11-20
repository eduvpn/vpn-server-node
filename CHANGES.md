# Changelog

## 1.0.5 (...)
- support PHPUnit 6
- add `certificate-info` script to show when the OpenVPN server certificates
  will expire
- restructure server configuration file generation
- Psalm fixes
- switch to `--compress` as `--comp-lzo` is deprecated, maintain client
  configuration compatibility
- use same IPv6 default gateway routes on 2.3 clients as are used for 2.4 
  clients
- add tests for testing server configuration generation

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
