# Let's Connect! / eduVPN Firewall
*nat
:PREROUTING ACCEPT [0:0]
:INPUT ACCEPT [0:0]
:OUTPUT ACCEPT [0:0]
:POSTROUTING ACCEPT [0:0]
<?php foreach ($forwardFilterList as $forwardFilter): ?>
<?php if ($forwardFilter['enableNat'] && $ipFamily === $forwardFilter['ipRange']->getFamily()): ?>
<?php if (null === $forwardFilter['outInterface']): ?>
-A POSTROUTING --source <?=$forwardFilter['ipRange']; ?> --jump MASQUERADE
<?php else: ?>
-A POSTROUTING --source <?=$forwardFilter['ipRange']; ?> --out-interface <?=$forwardFilter['outInterface']; ?> --jump MASQUERADE
<?php endif; ?>
<?php endif; ?>
<?php endforeach; ?>
COMMIT
*filter
:INPUT ACCEPT [0:0]
:FORWARD ACCEPT [0:0]
:OUTPUT ACCEPT [0:0]
<?php if ($enableInputRules): ?>
-A INPUT --match conntrack --ctstate RELATED,ESTABLISHED --jump ACCEPT
-A INPUT --in-interface lo --jump ACCEPT
<?php if (4 === $ipFamily): ?>
-A INPUT --protocol icmp --jump ACCEPT
<?php else: ?>
-A INPUT --protocol ipv6-icmp --jump ACCEPT
<?php endif; ?>
<?php foreach ($inputFilterList as $inputFilter): ?>
<?php if (null === $inputFilter->getSrcNet()): ?>
-A INPUT --protocol <?=$inputFilter->getProto(); ?> --match <?=$inputFilter->getProto(); ?> --dport <?=$inputFilter->getDstPort(); ?> --match conntrack --ctstate NEW,UNTRACKED --jump ACCEPT
<?php else: ?>
<?php if ($ipFamily === $inputFilter->getSrcNet()->getFamily()): ?>
-A INPUT --protocol <?=$inputFilter->getProto(); ?> --match <?=$inputFilter->getProto(); ?> --source <?=$inputFilter->getSrcNet(); ?> --dport <?=$inputFilter->getDstPort(); ?> --match conntrack --ctstate NEW,UNTRACKED --jump ACCEPT
<?php endif; ?>
<?php endif; ?>
<?php endforeach; ?>
-A INPUT --match conntrack --ctstate INVALID --jump DROP
<?php if (4 === $ipFamily): ?>
-A INPUT --jump REJECT --reject-with icmp-host-prohibited
<?php else: ?>
-A INPUT --jump REJECT --reject-with icmp6-adm-prohibited
<?php endif; ?>
<?php endif; ?>
-A FORWARD --in-interface tun+ ! --out-interface tun+ --jump ACCEPT
-A FORWARD ! --in-interface tun+ --out-interface tun+ --jump ACCEPT
<?php if (4 === $ipFamily): ?>
-A FORWARD --jump REJECT --reject-with icmp-host-prohibited
<?php else: ?>
-A FORWARD --jump REJECT --reject-with icmp6-adm-prohibited
<?php endif; ?>
COMMIT
