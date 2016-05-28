#!/usr/bin/php

<?php

// Include domainservice
require_once('Transip/DomainService.php');
require_once('dns.inc.php');

$action = $argv[1];     // Letsencrypt.sh hook action
$domain = $argv[2];     // Full domain-name to work on
// DNS Servers to check for records (public dns preferred)
$dns_servers=array('ns0.transip.net', 'ns1.transip.nl', 'ns2.transip.eu');

$ttl = "60";            // TTL For our acme record
$sleeptime = "10";      // Time to wait between DNS checks

$pattern = '/^((.*)\.)?(mydomain\.tld)$/x';   // Split up domain / subdomain

if( preg_match($pattern, $argv[2], $matches ) )
{
//  echo "Host-part: ", $matches[1], "\n";
    $subdomain = $matches[2];
    rtrim($subdomain, ".");
//  echo "Domain-part: ", $matches[2], "\n";
    $zone = $matches[3];
}
else
{
    echo "No domain-name and/or subdomain found\n";
    exit(1);
}

// What record should we update
if ( empty($subdomain))
{
    $acmedomain = "_acme-challenge";
}
else
{
    $acmedomain = "_acme-challenge.$subdomain";
}

// Retrieve all DNS records for the zone
$dnsEntries = Transip_DomainService::getInfo($zone)->dnsEntries;

echo "Called with action: '$action', on zone '$zone' with domain '$domain', and subdomain '$subdomain'\n\n";

if( $action === "deploy_challenge" )
{
    $tokenfile = $argv[3];
    $tokenvalue = $argv[4];
    $found = 0;

    echo "DEPLOY_CHALLENGE with tokenvalue: '$tokenvalue' on zone: '$zone' and record: '$acmedomain'\n\n";

    foreach ($dnsEntries as $key => $dnsEntry)
    {
        if($dnsEntry->name == "$acmedomain")
        {
//          echo "Key = ", $key, " Name = " , $dnsEntry->name, "\n";
//          echo "Found _acme-challenge: ", $dnsEntry->content, "\n";
            $dnsEntries[$key] = new Transip_DnsEntry("$acmedomain", $ttl, Transip_DnsEntry::TYPE_TXT, $tokenvalue);
            $found++;
        }
    }

    if ( $found == 0 )
    {
        $dnsEntries[] = new Transip_DnsEntry("$acmedomain", 300, Transip_DnsEntry::TYPE_TXT, $tokenvalue);
    }

    try
    {
        // Commit the changes to the TransIP DNS servers
        Transip_DomainService::setDnsEntries($zone, $dnsEntries);
        echo "DNS updated\n\n";
    }
    catch(SoapFault $f)
    {
        echo "DNS not updated. " . $f->getMessage() , "\n\n";
        exit(1);
    }

    $continue = 0;
    while( $continue < sizeof($dns_servers) )
    {
        foreach( $dns_servers as $dns_server)
        {
            $dns_query=new DNSQuery($dns_server);
            $dns_result=$dns_query->Query("$acmedomain.$zone","TXT");

            if ( ($dns_result===false) || ($dns_query->error!=0) ) // error occured
            {
                echo $dns_query->lasterror;
                exit();
            }

            //Process Results
            $dns_result_count=$dns_result->count; // number of results returned
            if( $dns_result_count > 1)
            {
                echo "Got back multiple results, please clean up your dns\n";
            }
            elseif( $dns_result_count == 1 )
            {
                if ( $tokenvalue == $dns_result->results[0]->data )
                {
                    $continue++;
                }
            }
        }

        if ($continue < sizeof($dns_servers) )
        {
            echo "Result not ready, retrying in $sleeptime seconds\n";
            sleep($sleeptime);
            $continue = 0;
        }
    }
}
elseif( $action === "clean_challenge" )
{
    $tokenfile = $argv[3];
    $tokenvalue = $argv[4];
    $found = 0;

    foreach ($dnsEntries as $key => $dnsEntry)
    {
        if($dnsEntry->name == "$acmedomain")
        {
//          echo "Key = ", $key, " Name = " , $dnsEntry->name, "\n";
//          echo "Found _acme-challenge: ", $dnsEntry->content, "\n";
            unset($dnsEntries[$key]);
            $found++;
        }
    }

    if( $found > 0)
    {
        try
        {
            // Commit the changes to the TransIP DNS servers
            Transip_DomainService::setDnsEntries($zone, $dnsEntries);
            echo "DNS updated\n\n";
        }
        catch(SoapFault $f)
        {
            echo "DNS not updated. ", $f->getMessage() , "\n\n";
            echo "Please remove manually\n\n";
        }
    }
    else
    {
        echo "No need to update, record not found\n";
        exit(0);
    }
}
elseif( $action === "deploy_cert" )
{
}
elseif( $action === "unchanged_cert" )
{
}
else
{
    echo "Unknown action, '$action'";
}

exit(0);
?>
