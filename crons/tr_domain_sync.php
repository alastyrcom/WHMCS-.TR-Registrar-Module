<?php
use WHMCS\Database\Capsule;
$whmcspath = "";
if (file_exists(dirname(__FILE__) . "/config.php"))
    require_once dirname(__FILE__) . "/config.php";
if (!empty($whmcspath))
    require $whmcspath . "/init.php";
else
    require("../init.php");

if (file_exists(dirname(__DIR__) . '/modules/registrars/alastyr/inc/alastyr.php'))
    require_once (dirname(__DIR__) . '/modules/registrars/alastyr/inc/alastyr.php');
else
    logActivity('Alastyr Domain Cron Error, File (/modules/registrars/alastyr/inc/alastyr.php) not found');

$params = alastyr_GetConfigurationParamsData();
$postfields['ApiKey'] = $params['ApiKey'];
$postfields['ApiSecret'] = $params['ApiSecret'];
$waitingdomains = Capsule::table('mod_alastyrdomain')->where('laststatus', '!=', '7')->orderby('updated_at', 'asc')->get();
$updatecount = 0;

foreach($waitingdomains as $domain){

    $postfields['domainName'] = $domain->domainname;


    $laststate = alastyr_CheckDomainStatus($postfields);

    if($laststate['status'] == "7"){
        $domaininfo =  Capsule::table('tbldomains')->where('domain', $postfields['domainName'])->first();
        $order =  Capsule::table('tblorders')->where('id', $domaininfo->orderid)->first();
        $nameservers = explode(',', $order->nameservers);

        $command = 'DomainUpdateNameservers';
        $postData = array(
            'domainid' => $domaininfo->id,
            'ns1' => ''.$nameservers[0].'',
            'ns2' => ''.$nameservers[1].'',
            'ns3' => ''.$nameservers[2].'',
            'ns4' => ''.$nameservers[3].'',
            'ns5' => ''.$nameservers[4].'',
        );
        $results = localAPI($command, $postData);

        $command = 'SendEmail';
        $postDatamail = array(
            'messagename' => 'TR Alan Adı Basvuru Onayi',
            'id' => $domaininfo->id,
        );
        $results = localAPI($command, $postDatamail);

        $laststate = alastyr_DomainInfo($postfields);
        $expdate = date("Y-m-d", substr($laststate['expirationDate'], 0, -3 ));
        $command = 'UpdateClientDomain';
        $postData = array(
            'domainid' => $domaininfo->id,
            'status' => 'Active',
            'nextduedate' => $expdate,
            'expirydate' => $expdate
        );
        $results = localAPI($command, $postDatamail);
    }

    $logarray = array('domainid' => $domain->domainid, 'userid' => $domain->userid, 'domainname' => $domain->domainname, 'ticketid' => $laststate['ticketNumber'], 'laststatus' => $laststate['status'], 'actiontype' => $laststate['actionType'], 'actioncomment' => $laststate['actionComment'], 'lastdescription' => $laststate['detail']);
    alastyr_addDomainLog($logarray);
    ++$updatecount;

}

echo $updatecount . ' alan adı durumu kontrol edildi.';