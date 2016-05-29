<?php

$my_domains = array(
    "hurrdurr.nl",
    "oppai.nl",
    "nekoconeko.nl"
);

// DNS Servers to check for records (public dns preferred)
$dns_servers = array('ns0.transip.net', 'ns1.transip.nl', 'ns2.transip.eu');

$ttl = "60";            // TTL For our acme record
$sleeptime = "30";      // Time to wait between DNS checks

?>
