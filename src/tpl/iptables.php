# ******************************************
# * Let's Connect! / eduVPN Firewall       *
# *                                        *
# * THIS FILE IS GENERATED, DO NOT MODIFY! *
# **********************************&*******
<?php if (0 !== count($natSrcNetList)): ?>
*nat
:PREROUTING ACCEPT [0:0]
:INPUT ACCEPT [0:0]
:OUTPUT ACCEPT [0:0]
:POSTROUTING ACCEPT [0:0]
<?php foreach ($natSrcNetList as $natSrcNet): ?>
<?php if ($ipFamily === $natSrcNet->getFamily()): ?>
-A POSTROUTING --source <?=$natSrcNet; ?> -j MASQUERADE
<?php endif; ?>
<?php endforeach; ?>
COMMIT
<?php endif; ?>
*filter
:INPUT ACCEPT [0:0]
:FORWARD ACCEPT [0:0]
:OUTPUT ACCEPT [0:0]
-A INPUT -m conntrack --ctstate RELATED,ESTABLISHED -j ACCEPT
-A INPUT -i lo -j ACCEPT
<?php if (4 === $ipFamily): ?>
-A INPUT -p icmp -j ACCEPT
<?php else: ?>
-A INPUT -p ipv6-icmp -j ACCEPT
<?php endif; ?>
<?php foreach ($inputFilterList as $inputFilter): ?>
<?php if (null === $inputFilter->getSrcNet()): ?>
-A INPUT -p <?=$inputFilter->getProto(); ?> -m <?=$inputFilter->getProto(); ?> --dport <?=$inputFilter->getDstPort(); ?> -m conntrack --ctstate NEW,UNTRACKED -j ACCEPT
<?php else: ?>
<?php if ($ipFamily === $inputFilter->getSrcNet()->getFamily()): ?>
-A INPUT -p <?=$inputFilter->getProto(); ?> -m <?=$inputFilter->getProto(); ?> --source <?=$inputFilter->getSrcNet(); ?> --dport <?=$inputFilter->getDstPort(); ?> -m conntrack --ctstate NEW,UNTRACKED -j ACCEPT
<?php endif; ?>
<?php endif; ?>
<?php endforeach; ?>
-A INPUT -m conntrack --ctstate INVALID -j DROP
<?php if (4 === $ipFamily): ?>
-A INPUT -j REJECT --reject-with icmp-host-prohibited
<?php else: ?>
-A INPUT -j REJECT --reject-with icmp6-adm-prohibited
<?php endif; ?>
COMMIT
