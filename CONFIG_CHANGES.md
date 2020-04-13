# Configuration Changes

This document describes all configuration file changes since the 2.0.0 release.
This in order to keep track of all changes that were made during the 2.x 
release cycle. 

This will help upgrades to a future 3.x release. Configuration changes during
the 2.x life cycle are NOT required. Any existing configuration file will keep
working!

## 2.2.0

- As all the firewall generation support, i.e. 
  `vpn-server-node-generate-firewall`  was removed, there is no longer a 
  `/etc/vpn-server-node/firewall.php`. This file can now be removed.

## 2.1.4

- A new configuration option `manageFirewall` that takes boolean was added to 
  `config.php` (default: `true`). Setting it to `false` will disable installing 
  firewall rules when running `vpn-server-node-generate-firewall --install`.

## 2.1.0

- A new configuration option `useVpnDaemon` that takes a boolean was added to 
  `config.php`. It will make sure the "managementIp" is *always* `127.0.0.1` as 
  the VPN daemon running on the node will listen on the public address, 
  protected using TLS. The default is `false`.

## 2.0.4

- The configuration option `profileList` in `config.php` was added which takes 
  an array containing a list of profiles to deploy on this particular node. The 
  default, when the option is missing, is to deploy _all_ profiles on this 
  node. Example: `'profileList' => ['office', 'sysadm'],`
