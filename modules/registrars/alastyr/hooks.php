<?php
use WHMCS\ClientArea;
use WHMCS\Database\Capsule;
use WHMCS\User\Alert;
use WHMCS\View\Menu\Item;

add_hook('ClientAreaHomepagePanels', 1, function (Item $homePagePanels)
{

    $client = Menu::context( "client" );
    $clientid = intval( $client->id );

    $command = 'GetClientsDomains';
    $postData = array(
        'clientid' => $clientid,
        'stats' => false,
    );

    $domainresults = localAPI($command, $postData);

if(isset($domainresults['domains']['domain']) && is_array($domainresults['domains']['domain'])) {
    foreach($domainresults['domains']['domain'] as $domaindata){
        if($domaindata['registrar'] == 'alastyr'){
            $domainstatus = Capsule::table('mod_alastyrdomain')->where('domainname', '=', $domaindata['domainname'])->first();

            if($domainstatus->laststatus == "0"){
                $waitingdocuments = 1;
                $waitingdomains[] = array('domainid' => $domainstatus->domainid,'domainname' => $domainstatus->domainname, 'laststate' => $domainstatus->lastdescription);
            }
        }

    }
}

    if($waitingdocuments == 1){
    $waitingdocuments = $homePagePanels->addChild('waitingdocuments', array(
        'label' => 'Belge Bekleyen Alan Adlarınız Var!',
        'icon' => 'fa-info-circle',
        'order' => 20,
        'bodyHtml' => '',
        'footerHtml' => '4 gün içerisinde belge gönderilmesi gerekmektedir!',
    ));

    }

    foreach($waitingdomains as $k => $domains){
        $waitingdocuments->addChild($k, array(
            'label' => '<a href="clientarea.php?action=domaindetails&id='.$domains['domainid'].'">' . $domains['domainname'] . '</a>',
            'icon' => 'fa-angle-double-right',
            'badge' => '<a href="clientarea.php?action=domaindetails&id='.$domains['domainid'].'">' .$domains['laststate'] . '</a>',
            //'order' => $i,
        ));
        $waitingdocuments->moveToFront();
    }

});

add_hook('ClientAreaHomepage', 1, function($vars) {

    require_once ('alastyr.php');
    $client = Menu::context( "client" );
    $clientid = intval( $client->id );

    $command = 'GetClientsDomains';
    $postData = array(
        'clientid' => $clientid,
        'stats' => true,
    );

    $domainresults = localAPI($command, $postData);

if(isset($domainresults['domains']['domain']) && is_array($domainresults['domains']['domain'])) {
    foreach($domainresults['domains']['domain'] as $domaindata){
        if(($domaindata['registrar'] == 'alastyr') and ($domaindata['status'] == 'Pending Registration')){
            $domainstatus = Capsule::table('mod_alastyrdomain')->where('domainname', '=', $domaindata['domainname'])->first();

            if($domainstatus->laststatus == "0"){
                $waitingdocuments = 1;
                $waitingdomains[] = array('domainid' => $domainstatus->domainid,'domainname' => $domainstatus->domainname, 'laststate' => $domainstatus->lastdescription, 'actioncomment' => $domainstatus->actioncomment);
                $domains[] = $domaindata['domainname'];
            }
        }

    }
}
    if($waitingdocuments == 1) {
        $upload_modal = alastyr_ViewClientMultiUploadForm($domains);
        $domainhtml = $upload_modal . "<style>.new-tlds-home-banner {   margin: 5px 0 20px 0;   padding: 14px;   background:#0064CD;   background:-moz-linear-gradient(top, #0064CD 0%, #207ce5 100%);   background:-webkit-linear-gradient(top, #0064CD 0%,#207ce5 100%);   background:-ms-linear-gradient(top, #0064CD 0%,#207ce5 100%);   background:linear-gradient(to bottom, #0064CD 0%,#207ce5 100%);  font-size:1.2em;   color: #fff;   border-radius:5px;   zoom:1;}.new-tlds-home-banner a {   color:#FFD20A;\n}\n</style><div class=\"new-tlds-home-banner\"><b><i class=\"fas fa-info-circle\"></i> Belge Bekleyen .TR Alan Adlarınız Var</b><br />
<div class=\"row\"><div class=\"col-sm-4\">Alan Adı</div><div class=\"col-sm-5\">Durum</div><div class=\"col-sm-3\"></div></div>
";
        foreach ($waitingdomains as $k => $domains) {
                $domainhtml .= "<div class=\"row\"><div class=\"col-sm-4\">" .$domains['domainname'] . "</div><div class=\"col-sm-5\">" . $domains["laststate"] ."" . $domains['actioncomment'] . "</div><div class=\"col-sm-3\" style=\"text-align: right\"><a href=\"index.php?m=tr_domain&id=".$domains['domainid']."&a=ApplicationForm\">Tahsis formu indir</a></div><div class='clearfix'></div></div>";
        }

        $domainhtml .= "<br /><div style='text-align: center'><button type=\"button\" class=\"btn btn-danger\"  data-toggle=\"modal\" data-target=\"#upload-form\">Belge Gönder</button></div></div>";



       return $domainhtml;
    }



});
