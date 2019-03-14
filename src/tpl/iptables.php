# Let's Connect! / eduVPN Firewall
*nat
:PREROUTING ACCEPT [0:0]
:INPUT ACCEPT [0:0]
:OUTPUT ACCEPT [0:0]
:POSTROUTING ACCEPT [0:0]
<?php foreach ($srcList as $srcItem): ?>
<?php if ($srcItem['enableNat'] && $ipFamily === $srcItem['ipRange']->getFamily()): ?>
<?php if (null === $srcItem['outInterface']): ?>
-A POSTROUTING --source <?=$srcItem['ipRange']; ?> --jump MASQUERADE
<?php else: ?>
-A POSTROUTING --source <?=$srcItem['ipRange']; ?> --out-interface <?=$srcItem['outInterface']; ?> --jump MASQUERADE
<?php endif; ?>
<?php endif; ?>
<?php endforeach; ?>
COMMIT
*filter
:INPUT ACCEPT [0:0]
:FORWARD ACCEPT [0:0]
:OUTPUT ACCEPT [0:0]
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
-A FORWARD --match conntrack --ctstate RELATED,ESTABLISHED --jump ACCEPT
<?php foreach ($srcList as $srcItem): ?>
<?php if ($ipFamily === $srcItem['ipRange']->getFamily()): ?>
<?php if (null === $srcItem['outInterface']): ?>
-A FORWARD --in-interface tun+ --source <?=$srcItem['ipRange']; ?> --jump ACCEPT
<?php else: ?>
-A FORWARD --in-interface tun+ --source <?=$srcItem['ipRange']; ?> --out-interface <?=$srcItem['outInterface']; ?> --jump ACCEPT
<?php endif; ?>
<?php endif; ?>
<?php endforeach; ?>
-A FORWARD --match conntrack --ctstate INVALID --jump DROP
<?php if (4 === $ipFamily): ?>
-A FORWARD --jump REJECT --reject-with icmp-host-prohibited
<?php else: ?>
-A FORWARD --jump REJECT --reject-with icmp6-adm-prohibited
<?php endif; ?>
COMMIT
