# Changelog

## 2.0.1 (2019-04-26)
- update tests to deal with updates internal API error messages 
  (vpn-lib-common)

## 2.0.0 (2019-04-01)
- better error when number of vpnProtoPorts is not 1,2,4,8 or 16
- remove 2FA verification through OpenVPN
- when using "split tunnel" configuration, also set a static route to the VPN 
  server through the client's normal gateway to avoid problems when pushing 
  routes that contain the VPN server's public IP address
- remove compression framing support
- remove tls-auth support
- remove "multi instance" support
- update firewalling

## 1.1.2 (2018-11-09)
- add support for `blockLan` to block traffic to local LAN network when 
  connected to the VPN
- deal with `blockSmb` not necessarily being available as a configuration 
  option

## 1.1.1 (2018-10-21)
- support also 32 bit Fedora/CentOS for determining OpenVPN auth plugin path

## 1.1.0 (2018-10-15)
- drop OpenVPN 2.3 client support, requires >= 2.4 now
  - IPv6 default gateway routing fixes no longer pushed to clients
  - `AES-256-GCM` is required cipher now
- only use `auth SHA256` with `tls-auth`, not needed with `AES-256-GCM` and 
  `tls-crypt`

## 1.0.22 (2018-10-10)
- when DNS servers are set, but VPN is not used as default gateway _do_ send 
  the DNS addresses
- empty `dns` field, i.e. `[]` does no longer send any DNS servers, before it 
  would send the IPv4 and IPv6 gateway addresses to the VPN clients
- introduce `@GW4@` and `@GW6@` macros that can be used in `dns` that will be
  replaced by the IPv4 and IPv6 gateway addresses

## 1.0.21 (2018-10-05)
- implement detector for `auth-script-openvpn` plugin

## 1.0.20 (2018-09-19)
- when setting `tlsProtection` to `false`, no longer allow `AES-256-CBC` cipher 
  and do not push IPv6 route fix

## 1.0.19 (2018-09-10)
- cleanup autoloader so Psalm will be able to verify the scripts in and bin and
  libexec folder
- additional Psalm fixes
- automatically provide IPv4 / IPv6 gateway address as DNS servers when none
  specified, i.e. `dns` is `[]` and `defaultGateway` is `true`
- no longer use `DNS6`, use `DNS` also for IPv6 DNS servers
- always generate new server certificates when running `server-config` script

## 1.0.18 (2018-08-05)
- many `vimeo/psalm` fixes

## 1.0.17 (2018-07-02)
- now always issue an `/112` to OpenVPN processes no matter who many IP space
  is available (issue #23)

## 1.0.16 (2018-06-12)
- fix IPv6 prefix when there is only one VPN process (issue #22)

## 1.0.15 (2018-06-06)
- change the default `--auth-gen-token` lifetime to 12 hours when 2FA is used
- support `tlsProtection`, allows disabling `tls-auth` and `tls-crypt`

## 1.0.14 (2018-04-17)
- update default config to use `enableNat4` and `enableNat6` instead of 
  `useNat` to allow separate configuration of NAT for IPv4 and IPv6

## 1.0.13 (2018-04-12)
- no longer push `bypass-dhcp` to clients
- set `keepalive` to 25 seconds

## 1.0.12 (2018-04-05)
- autodetect authPlugin instead of requiring configuration option. If plugin is
  installed, use it

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
