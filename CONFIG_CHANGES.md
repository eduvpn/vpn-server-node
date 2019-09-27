# Configuration Changes

This document describes all configuration file changes since the 2.0.0 release.
This in order to keep track of all changes that were made during the 2.x 
release cycle. 

This will help upgrades to a future 3.x release. Configuration changes during
the 2.x life cycle are NOT required. Any existing configuration file will keep
working!

## 2.0.4

- The configuration option `profileList` in `config.php` was added which takes 
  an array containing a list of profiles to deploy on this particular node. The 
  default, when the option is missing, is to deploy _all_ profiles on this 
  node. Example: `'profileList' => ['office', 'sysadm'],`
