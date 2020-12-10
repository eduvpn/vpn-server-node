# Changelog

## 2.2.7 (...)
- `tlsProtection` is no longer an option, it is always `tls-crypt`

## 2.2.6 (2020-11-27)
- update for `ProfileConfig` refactor
- fix IP range check
- fix a bug where `max-clients` was one higher than available IPs in the 
  OpenVPN client IP pool
- also specify `keepalive` in TCP server config to work around obscure UDP/TCP
  failover client connection bug
  
## 2.2.5 (2020-10-20)
- perform some checks on the profile configuration before writing the OpenVPN
  server configuration:
    - make sure `profileNumber` is not reused;
    - make sure the `listen` and `vpnProtoPorts` combinations do not overlap 
      between profiles
    - make sure there is no overlap in IP ranges (`range` / `range6` option) 
      assigned to VPN profiles (for now shows warning if there is a problem)
    - make sure `range` is `/29` or lower per OpenVPN process 
      (OpenVPN limitation)
    - make sure `range6` is `/112` or lower per OpenVPN process 
      (OpenVPN limitation)
- implement changes for updated `Config` API
- add support for pushing `DOMAIN-SEARCH` to VPN clients next to `DOMAIN`
- update for common HTTP client

## 2.2.4 (2020-09-08)
- add `ECDSA` certificate support for TLSv1.2, already supported on TLSv1.3

## 2.2.3 (2020-08-31)
- do not sort OpenVPN server configuration file to avoid having `DOMAIN` 
  ordering also changed in `dhcp-option`.
- no longer need to provide the `hostName` in the API call to generate a 
  certificate, vpn-server-api takes care of that
- no need to autoload anything in `bin/generate-firewall.php`

## 2.2.2 (2020-07-01)
- support adding `--up` to the server configuration file when 
  `/etc/openvpn/up` exists and is executable

## 2.2.1 (2020-05-12)
- remove `certificate-info` script as it no longer worked

## 2.2.0 (2020-04-15)
- the `vpn-server-node-generate-firewall` script is a dummy now. Current 
  firewall is kept, but in order to modify firewalls you have to do this 
  manually now or use your own tools. See 
  [documentation](https://github.com/eduvpn/documentation/blob/v2/FIREWALL.md#updating).

## 2.1.4 (2020-04-05)
- renegotiate data channel key every 10 hours instead of every hour
- allow admin to disable installing/generating firewall rules when running
  `vpn-server-node-generate-firewall --install` for systems without firewall
  or custom firewall

## 2.1.3 (2020-04-01)
- fix removal of "default gateway" push when also having routes configured

## 2.1.2 (2020-04-01)
- fix IPv6 address splitting for >16 OpenVPN processes per profile (issue #43)
- even when `defaultGateway` is `true` push the routes as configured in `route`
  (issue #44)

## 2.1.1 (2019-12-10)
- update for server API to handle per profile tls-crypt keys
- write all OpenVPN certificates / keys in the configuration file instead of 
  in a separate directory

## 2.1.0 (2019-11-21)
- support VPN daemon
- no longer specify `auth none` in server configuration

## 2.0.4 (2019-10-14)
- use tun_n_ for OpenVPN tunnel interfaces (BSD compat)
- allow up to 64 processes per VPN profile now
- allow deploying only certain profiles on the node for "multi node" 
  deployments
- implement `tlsOneThree` option for profiles to only allow TLSv1.3 
  connections

## 2.0.3 (2019-08-29)
- fix IP network/subnet calculation (issue #38)

## 2.0.2 (2019-08-13)
- support `dnsSuffix` option
- include script to generate (reverse) DNS zones for VPN client IPs

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
