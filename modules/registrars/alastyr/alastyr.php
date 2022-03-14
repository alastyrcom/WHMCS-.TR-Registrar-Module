<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}
use WHMCS\ClientArea;
use WHMCS\Database\Capsule;
use WHMCS\Domain\Registrar\Domain;
require_once 'inc/alastyr.php';
function alastyr_GetConfigArray() {
    $configarray = array( "Description" => array( "Type" => "System", "Value" => "Henüz Alastyr hesabınız yoksa <a href=\"https://www.alastyr.com/secure/register.php\" target=\"_blank\">www.alastyr.com</a> adresinden oluşturabilirsiniz." ), "ApiKey" => array( "Type" => "text", "Size" => "20", "Description" => "Alastyr TR domain yönetimi sayfasında bulunan API bölümünden alabilirsiniz." ), "ApiSecret" => array( "Type" => "password", "Size" => "20", "Description" => "Alastyr TR domain yönetimi sayfasında bulunan API bölümünden alabilirsiniz." ), "TestMode" => array( "Type" => "yesno" ) );
    return $configarray;
}

function alastyr_GetDomainInformation($params){
    $auth['ApiSecret'] = $params['ApiSecret'];
    $auth['ApiKey'] = $params['ApiKey'];
    $postfields = array();
    $postfields['domainName'] =  $params['original']['sld'] . "." . $params['original']['tld'];

    if($params['TestMode'] ==  "on"){
        $postfields['mode'] = "test";
    }
    $res = alastyr_getRequest("getdomaininfo", $postfields, $auth);

    $result = $res['result'];
    if($res['status'] == "error") {
        $status =  alastyr_CheckDomainStatus($postfields);
        logModuleCall('alastyr', 'getdomaininfo', $res, $status, '', '');

        if($status['detail']){
            return array("error" => $status['detail']);
        } else {
            return array("error" => alastyr_ErrorMesages($res['code']));
        }

    } else {
        if(is_array($result['nameServers'])){

            $x = 0;
            $y = 1;
            while ($x <= 4) {
                $values["ns" . $y] = $result['nameServers'][$x]['nsName'];
                ++$x;
                ++$y;
            }
        }
    }
    return (new Domain)
        ->setDomain($postfields['domainName'])
        ->setNameservers($values);
}

function alastyr_RegisterNameserver($params){
    $auth['ApiSecret'] = $params['ApiSecret'];
    $auth['ApiKey'] = $params['ApiKey'];
    $postfields = array();
    if($params['TestMode'] ==  "on"){
        $postfields['mode'] = "test";
    }

    $postfields["domainName"] = alastyr_getDomainName($params['original']["domainObj"]);

    $nameservers = str_replace('.' .$postfields["domainName"], '', explode(',' ,$params['nameserver']));
    $ipaddresses = explode(',' ,$params['ipaddress']);
    $nameserver = "";
    foreach ($nameservers as $nskey => $nsval){
        $nskey = $nskey + 1;
        $params['nameserver'.$nskey] = $nsval;
    }

    foreach ($ipaddresses as $ipkey => $ipval){
        $ipkey = $ipkey + 1;
        $params['ipaddress'.$ipkey] = $ipval;
    }


    $nameserver1 = $params['nameserver1'];
    $nameserver2 = $params['nameserver2'];
    $nameserver3 = $params['nameserver3'];
    $nameserver4 = $params['nameserver4'];
    $nameserver5 = $params['nameserver5'];
    $nslist = "ns1=" . $nameserver1 . '.' . $postfields["domainName"];
    if ($nameserver2) {
        $nslist .= "&ns2=" . $nameserver2 . '.' . $postfields["domainName"];
    }
    if ($nameserver3) {
        $nslist .= "&ns3=" . $nameserver3 . '.' . $postfields["domainName"];
    }
    if ($nameserver4) {
        $nslist .= "&ns4=" . $nameserver4 . '.' . $postfields["domainName"];
    }
    if ($nameserver5) {
        $nslist .= "&ns5=" . $nameserver5 . '.' . $postfields["domainName"];
    }
    $postfields['ns'] = "" . $nslist . '&';

    $ipaddress1 = $params['ipaddress1'];
    $ipaddress2 = $params['ipaddress2'];
    $ipaddress3 = $params['ipaddress3'];
    $ipaddress4 = $params['ipaddress4'];
    $ipaddress5 = $params['ipaddress5'];
    $iplist = "ip1=" . $ipaddress1 ;
    if ($ipaddress2) {
        $iplist .= "&ip2=" . $ipaddress2;
    }
    if ($ipaddress3) {
        $iplist .= "&ip3=" . $ipaddress3;
    }
    if ($ipaddress4) {
        $iplist .= "&ip4=" . $ipaddress4;
    }
    if ($ipaddress5) {
        $iplist .= "&ip5=" . $ipaddress5;
    }
    $postfields['ip'] = "" . $iplist;

    $res = alastyr_getRequest("setnameservers", $postfields, $auth);
    logModuleCall('alastyr', 'RegisterNameserver', $postfields, $res, '', '');
    if($res['status'] == "error") {
        return array("error" => alastyr_ErrorMesages($res['code']));
    } else {
        $values = array( "success" => "success" );
        return $values;
    }

}
function alastyr_RegisterDomain($params) {
    $auth['ApiSecret'] = $params['ApiSecret'];
    $auth['ApiKey'] = $params['ApiKey'];
    $postfields = array();
    if($params['TestMode'] ==  "on"){
        $postfields['mode'] = "test";
    }
    $directdomain = "bbs|gen|nom|name|tel|web|tv|biz|info";
    $directassign = explode('|', $directdomain);
    foreach ($directassign as $direct) {
        if($params['tld'] == $direct . ".tr"){
            $freedomain = 1;
        }
    }

    $postfields['domainName'] = $params['original']['sld'] . "." . $params['original']['tld'];
    $settings = alastyr_moduleConfig();
    $citizenfieldid = explode('|', $settings['option1']);
    $citizenfieldid = $citizenfieldid[0];
    $taxofficefieldid = explode('|', $settings['option2']);
    $taxofficefieldid = $taxofficefieldid[0];
    $taxidfieldid = explode('|', $settings['option3']);
    $taxidfieldid = $taxidfieldid[0];

    if($freedomain) { // bbs|gen|nom|name|tel|web|tv|biz|info uzantılı tr alan adları
        $postfields['name'] = "" . $params['firstname'] . " " . $params['lastname'];
        $citizenid = $params['customfields'.$citizenfieldid];

        $taxoffice = $params['customfields'.$taxofficefieldid];
        $taxid = $params['customfields'.$taxidfieldid];
        $postfields['category'] = 0;
        if(!empty($citizenid)){
            $postfields['citizenid'] = $citizenid;
        } else {
            $postfields['taxoffice'] = $taxoffice;
            $postfields['taxnumber'] = $taxid;
        }
    } else { // belge gerektiren alan adları
        if($params['additionalfields']['trdomaincategory'] == 'Kurum Adına') {
            $postfields['category'] = 0;
            $postfields['organization'] = $params['additionalfields']['trdomainorganization'];//firma adı gerekli
            $postfields['taxoffice'] = $params['additionalfields']['trdomaintaxoffice']; //vergi dairesi gerekli
            $postfields['taxnumber'] = $params['additionalfields']['trdomaintaxorcitizenid'];//vergi numarası gerekli
        } elseif(($params['additionalfields']['trdomaincategory'] == 'adsoyad.com.tr') or ($params['additionalfields']['trdomaincategory'] == 'adsoyad.net.tr')){
            $postfields['category'] = 1;
            $postfields['name'] = "" . $params['additionalfields']['trdomainname'];//gerekli
            $postfields['citizenid'] = $params['additionalfields']['trdomaintaxorcitizenid']; //gerekli
        } else {
            $postfields['category'] = 0;
            $postfields['name'] = "" . $params['additionalfields']['trdomainname'];//gerekli
            $postfields['citizenid'] = $params['additionalfields']['trdomaintaxorcitizenid']; //gerekli
        }
    }
    $postfields['email'] = $params['email'];
    $postfields['phone'] = $params['phonenumberformatted'];
    $postfields['address1'] = $params['additionalfields']['traddress1'];
    if(!empty($params['additionalfields']['traddress2'])){
        $postfields['address1'] = $params['additionalfields']['traddress2'];
    }
    $postfields['city'] = $params['additionalfields']['trdomaincity'];
    $postfields['country'] = $params['countryname'];
    $postfields['duration'] = $params['regperiod'] * 12;
    $nameserver1 = $params['ns1'];
    $nameserver2 = $params['ns2'];
    $nameserver3 = $params['ns3'];
    $nameserver4 = $params['ns4'];
    $nameserver5 = $params['ns5'];
    $nslist = "ns1=" . $nameserver1 . "&ns2=" . $nameserver2;
    if ($nameserver3) {
        $nslist .= "&ns3=" . $nameserver3;
    }
    if ($nameserver4) {
        $nslist .= "&ns4=" . $nameserver4;
    }
    if ($nameserver5) {
        $nslist .= "&ns5=" . $nameserver5;
    }
    $postfields['ns'] = "" . $nslist;

    $res = alastyr_getRequest("domainregister", $postfields, $auth);
    logModuleCall('alastyr', 'RegisterDomain', $postfields, $res, '', '');
    $result = $res['result'];
    if ($res['status'] == "error") {
        return array("error" => alastyr_ErrorMesages($res['code']));
    } else {
        if($freedomain){
            $laststate = alastyr_CheckDomainStatus($postfields);
            $logarray = array('domainid' => $params['domainid'], 'userid' => $params['userid'], 'domainname' => $postfields['domainName'], 'ticketid' => $result['ticketNumber'], 'laststatus' => $laststate['status'], 'actiontype' => $laststate['actionType'], 'actioncomment' => $laststate['actionComment'], 'lastdescription' => $laststate['detail']);
            alastyr_addDomainLog($logarray);
            $values = array("success" => "success");
            return $values;
        } else {
            $handle = file_get_contents(__DIR__ . "/mailcontent/".str_replace('.','_',$params['original']['tld']).".txt");

            $command = 'SendEmail';
            $postData = array(
                'messagename' => 'Alastyr - TR Alan Adı Basvuru',
                'id' => $params['domainid'],
                'customvars' => base64_encode(serialize(array("gereklibelge"=>$handle))),
            );
            $results = localAPI($command, $postData);
            $laststate = alastyr_CheckDomainStatus($postfields);
            $logarray = array('domainid' => $params['domainid'], 'userid' => $params['userid'], 'domainname' => $postfields['domainName'], 'ticketid' => $result['ticketNumber'], 'laststatus' => $laststate['status'], 'actiontype' => $laststate['actionType'], 'actioncomment' => $laststate['actionComment'], 'lastdescription' => $laststate['detail']);
            alastyr_addDomainLog($logarray);
            $command = 'UpdateClientDomain';
            $postData = array(
                'domainid' => $params['domainid'],
                'status' => 'Pending Registration',
            );
            $results = localAPI($command, $postData);
            $values = array("error" => "Alan adının ön başvurusu tamlanmıştır. Alan adı kaydı için belge bekleniyor.");
            return $values;
        }
    }
}
function alastyr_GetNameservers($params){
    $auth['ApiSecret'] = $params['ApiSecret'];
    $auth['ApiKey'] = $params['ApiKey'];
    $postfields = array();
    $postfields['domainName'] = $params['original']['sld'] . "." . $params['original']['tld'];

    if($params['TestMode'] ==  "on"){
        $postfields['mode'] = "test";
    }
    $res = alastyr_getRequest("getdomaininfo", $postfields, $auth);
    logModuleCall('alastyr', 'GetNameservers', $postfields, $res, '', '');
    $result = $res['result'];
    if($res['status'] == "error") {
        return array("error" => alastyr_ErrorMesages($res['code']));
    } else {
        if(is_array($result['nameServers'])){

            $x = 0;
            $y = 1;
            while ($x <= 4) {
                $values["ns" . $y] = $result['nameServers'][$x]['nsName'];
                ++$x;
                ++$y;
            }
            return $values;
        }
    }
}
function alastyr_SaveNameservers($params){
    $auth['ApiSecret'] = $params['ApiSecret'];
    $auth['ApiKey'] = $params['ApiKey'];
    $postfields = array();
    $postfields['domainName'] = $params['original']['sld'] . "." . $params['original']['tld'];
    $nameserver1 = $params['ns1'];
    $nameserver2 = $params['ns2'];
    $nameserver3 = $params['ns3'];
    $nameserver4 = $params['ns4'];
    $nameserver5 = $params['ns5'];
    $nslist = "ns1=" . $nameserver1 . "&ns2=" . $nameserver2;
    if ($nameserver3) {
        $nslist .= "&ns3=" . $nameserver3;
    }
    if ($nameserver4) {
        $nslist .= "&ns4=" . $nameserver4;
    }
    if ($nameserver5) {
        $nslist .= "&ns5=" . $nameserver5;
    }
    $postfields['ns'] = "" . $nslist;
    if($params['TestMode'] ==  "on"){
        $postfields['mode'] = "test";
    }
    $res = alastyr_getRequest("updatenameservers", $postfields, $auth);
    logModuleCall('alastyr', 'SaveNameservers', $postfields, $res, '', '');
    if($res['status'] == "error") {
        return array("error" => alastyr_ErrorMesages($res['code']));
    } else {
        $values = array( "success" => "success" );
        return $values;
    }
}
function alastyr_RenewDomain($params){
    $auth['ApiSecret'] = $params['ApiSecret'];
    $auth['ApiKey'] = $params['ApiKey'];
    $postfields = array();
    if($params['TestMode'] ==  "on"){
        $postfields['mode'] = "test";
    }
    $postfields['domainName'] = $params['original']['sld'] . "." . $params['original']['tld'];
    $postfields['duration'] = $params['regperiod'] * 12;
    $res = alastyr_getRequest("domainrenew", $postfields, $auth);
    logModuleCall('alastyr', 'RenewDomain', $postfields, $res, '', '');
    if($res['status'] == "error") {
        return array("error" => alastyr_ErrorMesages($res['code']));
    } else {
        $values = array( "success" => "success" );
        return $values;
    }
}
function alastyr_SaveContactDetails($params){
    $auth['ApiSecret'] = $params['ApiSecret'];
    $auth['ApiKey'] = $params['ApiKey'];
    $postfields = array();
    $postfields['domainName'] = $params['sld'] . "." . $params['tld'];

    if($params['TestMode'] ==  "on"){
        $postfields['mode'] = "test";
    }

    $postfields['email'] = $params['contactdetails']['Owner']['Email'];
    $postfields['address1'] = $params['contactdetails']['Owner']["Address 1"];
    $postfields['address2'] = $params['contactdetails']['Owner']["Address 2"];
    $postfields['zipCode'] = $params['contactdetails']['Owner']['Postcode'];
    $postfields['city'] = $params['contactdetails']['Owner']['City'];
    $postfields['country'] = $params['contactdetails']['Owner']['Country'];
    $postfields['phone'] = $params['contactdetails']['Owner']['Phone Number'];

    $res = alastyr_getRequest("updatedomainowner", $postfields, $auth);
    logModuleCall('alastyr', 'SaveContactDetails', $postfields, $res, '', '');
    if($res['status'] == "error") {
        return array("error" => alastyr_ErrorMesages($res['code']));
    } else {
        $values = array( "success" => "success" );
        return $values;
    }

}
function alastyr_GetContactDetails($params){
    $auth['ApiSecret'] = $params['ApiSecret'];
    $auth['ApiKey'] = $params['ApiKey'];
    $postfields = array();
    $postfields['domainName'] = $params['original']['sld'] . "." . $params['original']['tld'];
    if($params['TestMode'] ==  "on"){
        $postfields['mode'] = "test";
    }
    $res = alastyr_getRequest("getdomainowner", $postfields, $auth);
    $result = $res['result'];
    if($res['status'] == "error") {
        return array("error" => alastyr_ErrorMesages($res['code']));
    } else {
        $countryres = alastyr_getRequest("getcountries", '', $auth);
        $countries = $countryres['result'];
        $cityres = alastyr_getRequest("getcities", '', $auth);
        $cities = $cityres['result'];
        if($result['name'] or $result['organization']){
            $values['Owner'] = array( "Full Name" => $result['name'], "Email" => $result['email'], "Company Name" => $result['organization'], "Address 1" => $result['address1'], "Address 2" => $result['address2'], "City" => $cities[$result['cityId']], "Postcode" => $result['zipCode'], "Country" => $countries[$result['countryId']], "Phone Number" => "+" . $result['phone']);
        }
        return $values;
    }
}
function alastyr_CheckAvailability($params)
{
    $type = App::isInRequest("epp") ? "Transfer" : "Register";
    $auth['ApiSecret'] = $params['ApiSecret'];
    $auth['ApiKey'] = $params['ApiKey'];
    $postfields = array();
    if($params['TestMode'] ==  "on"){
        $postfields['mode'] = "test";
    }
    $postfields['domainname'] = $params['sld'] ;
    $postfields['premiumenabled'] = (bool) $params['premiumEnabled'];
    array_walk($params["tlds"], function (&$value) {
        $value = substr($value, 1);
    });
    $postfields["tlds"] = $params["tlds"];
    $res = alastyr_getRequest("domainwhois", $postfields, $auth);
    $result = $res['result'];
    $results = new WHMCS\Domains\DomainLookup\ResultsList();
    foreach ($result as $domainName => $domainData) {
        $parts = explode(".", $domainName, 2);
        $searchResult = new WHMCS\Domains\DomainLookup\SearchResult($parts[0], $parts[1]);
        if ($domainData["status"] == "available") {
            $searchResult->setStatus(WHMCS\Domains\DomainLookup\SearchResult::STATUS_NOT_REGISTERED);
        } else {
            if ($domainData["status"] == "unknown") {
                $searchResult->setStatus(WHMCS\Domains\DomainLookup\SearchResult::STATUS_TLD_NOT_SUPPORTED);
            } else {
                $searchResult->setStatus(WHMCS\Domains\DomainLookup\SearchResult::STATUS_REGISTERED);
            }
        }
        if ( $domainData['is_premium'] == 1) {
            if ($params["premiumEnabled"]) {
                $premiumPricing = array();
                if ($type == "Register" &&  $domainData["registerprice"]) {
                    $premiumPricing["register"] = $domainData["registerprice"];
                }
                if ($domainData["renewprice"]) {
                    $premiumPricing["renew"] = $domainData["renewprice"];
                }
                if ($type == "Transfer" && $domainData["transferprice"]) {
                    $premiumPricing["transfer"] = $domainData["transferprice"];
                }
                if ($premiumPricing) {
                    $searchResult->setPremiumDomain(true);
                    $premiumPricing["CurrencyCode"] = $domainData["currency"];
                    $searchResult->setPremiumCostPricing($premiumPricing);
                }
            } else {
                $searchResult->setStatus(WHMCS\Domains\DomainLookup\SearchResult::STATUS_RESERVED);
            }
        }
        $results->append($searchResult);
    }
    return $results;

}

function alastyr_GetDomainSuggestions($params)
{
    $results = new WHMCS\Domains\DomainLookup\ResultsList();
    return $results;
}

function alastyr_DocumentUpload($params){
    $postfields['domainName'] = $params['sld'] . "." . $params['tld'];
    $params = array_merge($params, alastyr_GetConfigurationParamsData());

    $token = generate_token($type = "link");
    $auth['ApiSecret'] = $params['ApiSecret'];
    $auth['ApiKey'] = $params['ApiKey'];
    $postfields = array();
    $postfields['domainName'] = $params['sld'] . "." . $params['tld'];

    if($params['TestMode'] ==  "on"){
        $postfields['mode'] = "test";
    }
    $res = alastyr_getRequest("getdomainstatusdomain", $postfields, $auth);
    $result = $res['result'];
    $logdocuments = "";

    if ($_FILES['attachments']) {
        foreach ($_FILES['attachments']['name'] as $key => $filename) {

            if((!empty($_FILES["attachments"]["tmp_name"][$key])) or (!empty($_POST['doctype'][$key]))){
                $tmpfile = $_FILES["attachments"]["tmp_name"][$key];   		// temp filename
                $tmp = explode(".", $filename);
                $extension = strtolower(end($tmp));
                $extension = "." . $extension;
                if($extension == ".pdf"){
                    $postfields['fileType'] = 1;
                } elseif($extension == ".jpg"){
                    $postfields['fileType'] = 2;
                } elseif($extension == ".jpeg"){
                    $postfields['fileType'] = 2;
                } elseif($extension == ".tiff"){
                    $postfields['fileType'] = 4;
                } else {
                    return array("error" => "Hatalı dosya türü. Sadece PDF, JPG ve TIFF kabul edilmektedir.");
                }

                $handle = fopen($tmpfile, "r");              // Open the temp file
                $contents = fread($handle, filesize($tmpfile));  	// Read the temp file
                fclose($handle);                                 	// Close the temp file
                $postfields['documents']   = base64_encode($contents);

                $postfields['description'] = $_POST['doctype'][$key];
                $postfields['documentType'] = 1;
                $postfields['operationType'] = 1;
                $postfields['sourceFileName'] = $filename;
                $postfields['ticketNumber'] = $result['ticketNumber'];


                if($result['status'] == 0){
                    $res = alastyr_getRequest("uploaddocuments", $postfields, $auth);
                    if($res['result']['uploaded'] == "true") {
                        $logdocuments .= "(" . $res['result']['description'] . " : <a href=\"clientsdomains.php?action=domaindetails&id=".$params['domainid']."&regaction=custom&ac=GetDomainDocument&document=".$res['result']['file']."\"  target=\"_blank\">" . $res['result']['file'] . "</a>) ";
                    } else {
                        return array('error' => "Dosya yüklenirken hata oluştu");
                    }
                }
            } else {
                return array('error' => "Belge eklemediniz");
            }
        }
        if(!empty($logdocuments)) {
            $laststate['detail'] = "Alan adı için belge yüklendi " . $logdocuments;
            $logarray = array('logtype' => "action", 'domainid' => $params['domainid'], 'userid' => "", 'domainname' => $postfields['domainName'], 'ticketid' => $result['ticketNumber'], 'laststatus' => $result['status'], 'actiontype' => $result['actionType'], 'actioncomment' => $result['actionComment'], 'lastdescription' => $laststate['detail']);
            alastyr_addDomainLog($logarray);
            return array( "success" => "success" );
        }
    }
}

function alastyr_SendDocument($params){
    $postfields['domainName'] = $params['sld'] . "." . $params['tld'];
    $params = array_merge($params, alastyr_GetConfigurationParamsData());


    $auth['ApiSecret'] = $params['ApiSecret'];
    $auth['ApiKey'] = $params['ApiKey'];
    $postfields = array();
    $postfields['domainName'] = $params['sld'] . "." . $params['tld'];

    if($params['TestMode'] ==  "on"){
        $postfields['mode'] = "test";
    }

    $domain_data = alastyr_GetDomainData($postfields['domainName']);
    $token = generate_token($type = "link");
    $res = alastyr_getRequest("getdomainstatusdomain", $postfields, $auth);
    $result = $res['result'];
    $logdocuments = "";
    logModuleCall('alastyr', 'file1', $res, $_FILES['attachments']['name'], '', '');
    if ($_FILES['attachments']) {
        foreach ($_FILES['attachments']['name'] as $key => $filename) {

            if((!empty($_FILES["attachments"]["tmp_name"][$key])) or (!empty($_POST['doctype'][$key]))){
                $tmpfile = $_FILES["attachments"]["tmp_name"][$key];   		// temp filename

                $tmp = explode(".", $filename);
                $extension = strtolower(end($tmp));
                $extension = "." . $extension;

                if($extension == ".pdf"){
                    $postfields['fileType'] = 1;
                } elseif($extension == ".jpg"){
                    $postfields['fileType'] = 2;
                }  elseif($extension == ".jpeg"){
                    $postfields['fileType'] = 2;
                }
                elseif($extension == ".tiff"){
                    $postfields['fileType'] = 4;
                } else {
                    return 'error';
                }

                $handle = fopen($tmpfile, "r");              // Open the temp file
                $contents = fread($handle, filesize($tmpfile));  	// Read the temp file
                fclose($handle);                                 	// Close the temp file
                $postfields['documents']   = base64_encode($contents);


                if( ($domain_data['status'] == 'Pending Transfer')){
                    $postfields['documentType'] = 3;
                    $postfields['description'] = $_POST['doctype'][$key];
                    $postfields['operationType'] = 1;
                    $postfields['sourceFileName'] = $filename;
                    // $postfields['ticketNumber'] = $result['ticketNumber'];
                } else {
                    $postfields['documentType'] = 1;
                    $postfields['description'] = $_POST['doctype'][$key];
                    $postfields['operationType'] = 1;
                    $postfields['sourceFileName'] = $filename;
                    $postfields['ticketNumber'] = $result['ticketNumber'];
                }



                if($result['status'] == 0){
                    $res = alastyr_getRequest("uploaddocuments", $postfields, $auth);
                    if($res['result']['uploaded'] == "true") {
                        $logdocuments .= "(" . $res['result']['description'] . " : <a href=\"clientsdomains.php?action=domaindetails&id=".$params['domainid']."&regaction=custom&ac=GetDomainDocument&document=".$res['result']['file']."\"  target=\"_blank\">" . $res['result']['file'] . "</a>) ";
                    } else {
                        return "error";
                    }
                }
            } else {
                return "error";
            }
        }
        if(!empty($logdocuments)) {
            $laststate['detail'] = "Alan adı için belge yüklendi " . $logdocuments;
            $logarray = array('logtype' => "action", 'domainid' => $params['domainid'], 'userid' => "", 'domainname' => $postfields['domainName'], 'ticketid' => $result['ticketNumber'], 'laststatus' => $result['status'], 'actiontype' => $result['actionType'], 'actioncomment' => $result['actionComment'], 'lastdescription' => $laststate['detail']);
            alastyr_addDomainLog($logarray);
            return "success";
        }
    }
}


function alastyr_RequestDelete($params){
    $auth['ApiSecret'] = $params['ApiSecret'];
    $auth['ApiKey'] = $params['ApiKey'];
    $postfields = array();
    $postfields['domainName'] = $params['original']['sld'] . "." . $params['original']['tld'];

    if($params['TestMode'] ==  "on"){
        $postfields['mode'] = "test";
    }
    $res = alastyr_getRequest("canceldomainapp", $postfields, $auth);
    $result = $res['result'];
    if($res['status'] == 'error'){
        logModuleCall('alastyr', 'delete', $res, $postfields, '', '');
        return array('error' => "Hata oluştu!");
    }
    if ($result['cancelapp'] == true){
        $newres = alastyr_getRequest("getdomainstatusdomain", $postfields, $auth);
        $newresult = $newres['result'];
    }
    logModuleCall('alastyr', 'delete', $result['ticketNumber'], $postfields, '', '');
    $logarray = array('domainid' => $params['domainid'], 'userid' => "", 'domainname' => $postfields['domainName'], 'ticketid' => $newresult['ticketNumber'], 'laststatus' => $newresult['status'], 'actiontype' => $newresult['actionType'], 'actioncomment' => $newresult['actionComment'], 'lastdescription' => $newresult['detail']);
    alastyr_addDomainLog($logarray);
    return $result;
}




function alastyr_AdminDomainsTabFields($params) {
    $postfields = array();
    $postfields['domainName'] = $params['sld'] . "." . $params['tld'];
    $params = array_merge($params, alastyr_GetConfigurationParamsData());
    $auth['ApiSecret'] = $params['ApiSecret'];
    $auth['ApiKey'] = $params['ApiKey'];
    if($params['TestMode'] ==  "on"){
        $postfields['mode'] = "test";
    }
    $domain_data = alastyr_GetDomainData($postfields['domainName']);

    $result = alastyr_getRequest("getdomainstatusdomain", $postfields, $auth);

    if(($result['status'] != 'error') or ($domain_data['status'] == 'Pending Transfer')){
        if(($result['result']['status'] == 0) or ($domain_data['status'] == 'Pending Transfer')){
            $token = generate_token($type = "link");
            $notes_button = '
    <div>
        <button type="button" class="btn btn-danger"  data-toggle="modal" data-target="#upload-form">Belge Gönder</button>
        <a href="clientsdomains.php?action=domaindetails&id='.$params['domainid'].'&regaction=custom&ac=ApplicationForm'.$token.'" class="btn btn-success" target="_blank">Alan Adı Tahsis Formu İndir</a>
        <button type="button" class="btn btn-primary"  data-toggle="modal" data-target="#view_notes">İşlem Logları</button>
    </div>';
            if( ($domain_data['status'] == 'Pending Transfer')){
                $params['ordertype'] = "transfer";
            }
            $upload_modal = alastyr_ViewUploadForm($params);
            $notes_modal = alastyr_ViewDomainLogs($params);

            if( ($domain_data['status'] == 'Pending Transfer')){
                $note = "Transfer için belge bekleniyor";
            } else {
                if ($result['result']['actionComment']) {
                    $note = alastyr_ActionTypes($result['result']['actionType']) . ' - ' . trim(str_replace('[Lutfen Seciniz]', '', $result['result']['actionComment']));
                } else {
                    $note = alastyr_ActionTypes($result['result']['actionType']) . ' - ' . $result['result']['detail'];
                }
            }
            return array(
                'Alan Adı Son Durumu' => '<b style="color: #f00;">'. $note .'</b>',
                'İşlemler' => $notes_button.$upload_modal.$notes_modal,
            );
        }
        else {
            $notes_button = '
    <div>
        <button type="button" class="btn btn-primary"  data-toggle="modal" data-target="#view_notes">İşlem Logları</button>
    </div>';

            $notes_modal = alastyr_ViewDomainLogs($params);
            return array(
                'Alan Adı Son Durumu' => '<b style="color: #f00;">'.$result['result']['detail'].'</b>',
                'İşlemler' => $notes_button.$notes_modal,
            );
        }
    } else {
        $notes_button = '
    <div>
        <button type="button" class="btn btn-primary"  data-toggle="modal" data-target="#view_notes">İşlem Logları</button>
    </div>';

        $notes_modal = alastyr_ViewDomainLogs($params);
        return array(
            'İşlemler' => $notes_button.$notes_modal,
        );
    }

}

function alastyr_ClientAreaCustomButtonArray($params){
    $postfields = array();
    $postfields['domainName'] =  $params['original']['sld'] . "." . $params['original']['tld'];
    $params = array_merge($params, alastyr_GetConfigurationParamsData());
    $auth['ApiSecret'] = $params['ApiSecret'];
    $auth['ApiKey'] = $params['ApiKey'];
    if($params['TestMode'] ==  "on"){
        $postfields['mode'] = "test";
    }
    $result = alastyr_getRequest("getdomainstatusdomain", $postfields, $auth);
    if($result['status'] != 'error') {
        if ($result['result']['status'] == 0) {
            return array(
                'Alan Adı Tahsis Formu İndir' => 'ApplicationForm',
                'Belge Gönder' => 'SendDocument',
            );
        }
    }
}
function alastyr_ClientAreaAllowedFunctions($params)
{
    return array(
        'Alan Adı Tahsis Formu İndir' => 'ApplicationForm',
        'Belge Gönder' => 'SendDocument',
    );
}
function alastyr_ClientArea($params)
{
    $postfields = array();
    $postfields['domainName'] =  $params['original']['sld'] . "." . $params['original']['tld'];
    $params = array_merge($params, alastyr_GetConfigurationParamsData());
    $auth['ApiSecret'] = $params['ApiSecret'];
    $auth['ApiKey'] = $params['ApiKey'];
    if($params['TestMode'] ==  "on"){
        $postfields['mode'] = "test";
    }
    $result = alastyr_getRequest("getdomainstatusdomain", $postfields, $auth);
    if($result['status'] != 'error') {
        if ($result['result']['status'] == 0) {

            $upload_button = '
    <div>
        <button type="button" class="btn btn-danger"  data-toggle="modal" data-target="#upload-form">Belge Gönder</button>
    </div>';
            $upload_modal = alastyr_ViewClientUploadForm($params);

            $screen = new SmartyBC();

            $template = "clientareaz.tpl";
            $templatefile = dirname(__FILE__) . "/" . $template .

                $screen->assign('upload_modal', $upload_modal);
            $screen->assign('upload_button', $upload_button);

            return array(

                'vars' => array(
                    'upload_modal' => $upload_modal,
                    'upload_button' => $upload_button,
                ),
            );

        }
    }

}
function alastyr_GetDomainDocument($params){
    $params = array_merge($params, alastyr_GetConfigurationParamsData());
    $auth['ApiSecret'] = $params['ApiSecret'];
    $auth['ApiKey'] = $params['ApiKey'];
    $postdatas['domainName'] = $params['original']['sld'] . "." . $params['original']['tld'];
    $postdatas['document'] = $_REQUEST['document'];
    $res = alastyr_getRequest("getdocument", $postdatas, $auth);
    logModuleCall('alastyr', 'doc', $res, $params, '', '');


    $filedata = base64_decode($res['result']);
    header('Content-Description: File Transfer');
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename='.$postdatas['document']);
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . strlen($filedata));

    echo $filedata;

    exit;
}
function alastyr_ApplicationForm($params){
    $params = array_merge($params, alastyr_GetConfigurationParamsData());
    $auth['ApiSecret'] = $params['ApiSecret'];
    $auth['ApiKey'] = $params['ApiKey'];
    $params['domainName'] = $params['original']['sld'] . "." . $params['original']['tld'];

    $user = alastyr_GetDomainUserData($params['domainName']);
    $command = 'GetClientsDetails';
    $postData = array(
        'clientid' => $user['id'],
        'stats' => false,
    );
    $userresult = localAPI($command, $postData);




    $domaininfo = alastyr_CheckDomainStatus($params);


    $custom['domainname'] = $params['original']['sld'] . "." . $params['original']['tld'];
    $custom['ticketnumber'] = $domaininfo['ticketNumber'];
    $custom['phone'] = $userresult['client']['phonenumberformatted'];
    $custom['address'] = $userresult['client']['address1'] . ' ' .$userresult['client']['address2'] . ' ' .$userresult['client']['city'] . ' ' .$userresult['client']['countryname'];
    $custom['email'] = $userresult['client']['email'];

    $pdffile = str_replace(".", "", $custom['domainname']);
    $domain_data = alastyr_GetDomainData($params['domainName']);
    if($domain_data['status'] ==  'Pending Transfer'){
        $res = alastyr_getRequest("ownerform", $custom, $auth);
        $pdffile = $pdffile.'sorumlu.pdf';
    } else {
        $res = alastyr_getRequest("applicationform", $custom, $auth);
        $pdffile = $pdffile.'alanaditahsis.pdf';
    }

    $filedata = base64_decode($res['result']);
    header('Content-Description: File Transfer');
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename='.$pdffile);
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . strlen($filedata));

    echo $filedata;
    exit;

}

function alastyr_getDomainName(WHMCS\Domains\Domain $domain, $skipFilter = false)
{
    $domainName = $domain->getDomain();
    if ($skipFilter) {
        return $domainName;
    }
    if (function_exists("mb_strtolower")) {
        return mb_strtolower($domainName);
    }
    if (preg_replace("/[^a-z0-9-.]/i", "", $domainName) == $domainName) {
        return strtolower($domainName);
    }
    return $domainName;
}