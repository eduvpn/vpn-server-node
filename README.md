**Summary**: Server node for Let's Connect! / eduVPN 

**Description**: Server node for Let's Connect! / eduVPN managing OpenVPN and
WireGuard.

**License**: AGPL-3.0-or-later

[![builds.sr.ht status](https://builds.sr.ht/~fkooman/vpn-server-node.svg)](https://builds.sr.ht/~fkooman/vpn-server-node)

# Introduction

This project is the VPN node of the Let's Connect! / eduVPN software. It 
manages OpenVPN and WireGuard.

# Issue Tracker

Find our issue tracker [here](https://todo.sr.ht/~eduvpn/server). You can also 
submit an issue through mail. 

Please mention the version of eduVPN / Let's Connect! server you are running 
and _only_ report issues with the _server_ here!

# Contributing

Thank you for taking the time to contribute to eduVPN / Let's Connect!. In 
order for us to be able to accept your contributions, i.e. "Pull Requests" or
"Merge Requests", we'd like you to sign our "CLA" and send it back to us. 

You can find the CLA [here](https://commonsconservancy.org/resources/). Pick
the correct one, either for "legal entities" or "individuals" and mail it to
[legaldocuments@commonsconservancy.org](mailto:legaldocuments@commonsconservancy.org) 
as stated in the document. Please add 
[eduvpn-support@lists.geant.org](mailto:eduvpn-support@lists.geant.org) to the 
CC field.

Note, that signing the CLA will not automatically guarantee your contribution 
will be included in the software!

Thanks again for wanting to contribute to eduVPN / Let's Connect!

## Code Quality / Style

If you want to contribute, make sure your code does not throw any (additional) 
warnings or errors when running [Psalm](https://psalm.dev/), 
[PHP CS Fixer](https://cs.symfony.com/) and [PHPUnit](https://phpunit.de/).

To run them (all) on your system:

```bash
$ composer update
$ make all
```
