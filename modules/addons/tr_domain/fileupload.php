<?php
use WHMCS\ClientArea;
use WHMCS\Database\Capsule;
use WHMCS\User\Alert;
use WHMCS\View\Menu\Item;

$screen = new SmartyBC();
$screen->force_compile = true;
$screen->caching = false;

$screen->setTemplateDir("../modules/addons/alastyr/views/");
$templatefile = "dashboard.tpl";
$client = Menu::context( "client" );
$clientid = intval( $client->id );

$command = 'GetClientsDomains';
$postData = array(
    'clientid' => $clientid,
    'stats' => true,
);

$domainresults = localAPI($command, $postData);

foreach($domainresults['domains']['domain'] as $domaindata){
    if(($domaindata['registrar'] == 'alastyr') and ($domaindata['status'] == 'Pending Registration')){
        $domainstatus = Capsule::table('mod_alastyrdomain')->where('domainname', '=', $domaindata['domainname'])->first();

        if($domainstatus->laststatus == "0"){
            $waitingdocuments = 1;
            $waitingdomains[] = array('domainid' => $domainstatus->domainid,'domainname' => $domainstatus->domainname, 'laststate' => $domainstatus->lastdescription, 'actioncomment' => $domainstatus->actioncomment);

        }
    }

}

$screen->assign("domains", $waitingdomains);
echo $screen->fetch($templatefile);