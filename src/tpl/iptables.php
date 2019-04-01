# Let's Connect! / eduVPN Firewall
*nat
:PREROUTING ACCEPT [0:0]
:INPUT ACCEPT [0:0]
:OUTPUT ACCEPT [0:0]
:POSTROUTING ACCEPT [0:0]
<?php foreach ($natRulesList as $natRule): ?>
<?php if ($natRule['enableNat'] && $ipFamily === $natRule['ipRange']->getFamily()): ?>
-A POSTROUTING --source <?=$natRule['ipRange']; ?> --jump MASQUERADE
<?php endif; ?>
<?php endforeach; ?>
COMMIT
*filter
:INPUT ACCEPT [0:0]
:FORWARD ACCEPT [0:0]
:OUTPUT ACCEPT [0:0]
-A INPUT --match conntrack --ctstate RELATED,ESTABLISHED --jump ACCEPT
-A INPUT --in-interface lo --jump ACCEPT
-A INPUT --protocol <?php if (4 === $ipFamily): ?>icmp<?php else: ?>ipv6-icmp<?php endif; ?> --jump ACCEPT
<?php foreach ($inputRulesList as $inputRule): ?>
<?php if (null === $inputRule->getSrcNet()): ?>
-A INPUT --protocol <?=$inputRule->getProto(); ?> --match <?=$inputRule->getProto(); ?> --dport <?=$inputRule->getDstPort(); ?> --match conntrack --ctstate NEW,UNTRACKED --jump ACCEPT
<?php else: ?>
<?php if ($ipFamily === $inputRule->getSrcNet()->getFamily()): ?>
-A INPUT --protocol <?=$inputRule->getProto(); ?> --match <?=$inputRule->getProto(); ?> --source <?=$inputRule->getSrcNet(); ?> --dport <?=$inputRule->getDstPort(); ?> --match conntrack --ctstate NEW,UNTRACKED --jump ACCEPT
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
-A FORWARD --in-interface tun+ ! --out-interface tun+ --jump ACCEPT
-A FORWARD --match conntrack --ctstate INVALID --jump DROP
<?php if (4 === $ipFamily): ?>
-A FORWARD --jump REJECT --reject-with icmp-host-prohibited
<?php else: ?>
-A FORWARD --jump REJECT --reject-with icmp6-adm-prohibited
<?php endif; ?>
COMMIT
