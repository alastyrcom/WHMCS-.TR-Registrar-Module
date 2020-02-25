<?php

namespace WHMCS\Module\Widget;

use WHMCS\Carbon;
use WHMCS\Module\AbstractWidget;
use WHMCS\Database\Capsule;


class AlastyrInfo extends AbstractWidget
{
    protected $title = 'TR Alan Adı Bilgi';
    protected $description = 'TR alan adı bilgi.';
    protected $weight = 15;
    protected $cache = true;
    protected $cacheExpiry = 1200;
    protected $requiredPermission = '';
    
    function GetPartnerData() {
    $result = Capsule::table('tblregistrars')->where('registrar', 'alastyr')->get();
    foreach($result as $item) {
        $params[$item->setting] = decrypt($item->value);
    }
    $auth['ApiSecret'] = $params['ApiSecret'];
    $auth['ApiKey'] = $params['ApiKey'];
    $result = $this->getRequest("partnerdata", "", $auth);
    return $result['result'];
	
	}

	function getRequest($command, $postfields, $auth){
    if (!$auth['ApiSecret']) { return array( "error" => "Missing Api Secret. Please navigate to Setup > Domain Registrars to configure." ); }
    if (!$auth['ApiKey']) { return array( "error" => "Missing API Key. Please navigate to Setup > Domain Registrars to configure." ); }
    $sPost = "";
    foreach ($postfields as $field => $data) {
        if($field != 'ns'){
            $sPost .= "" . $field . "=" . rawurlencode( $data ) . "&";
        } else {
            $sPost .= $data;
        }
    }
    $sPost .= "&api_key=".$auth['ApiKey']."&api_secret=".$auth['ApiSecret']."";
    $sTarget = "https://api.alastyr.com/v1/".$command;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $sTarget);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $sPost);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type: application/x-www-form-urlencoded"));
    $ret=curl_exec($ch);
    $result = json_decode($ret, true);
    return $result;
}
    public function getData()
    {
    $waiting = Capsule::table('mod_alastyrdomain')->where('laststatus', '0')->orderby('updated_at', 'desc')->get();
    $waitingcount = count($waiting);
    $waitingdata =	json_decode(json_encode($waiting), true);
        return array(
            //'activeCredit' => (int) Client::where('status', '=', 'Active')->count(),
            'witingDocument' => (int) $waitingcount,
            'waitingDocumentList' => $waitingdata,
            'partnerData' => $this->GetPartnerData(),
        );
    }

    public function generateOutput($data)
    {
        $witingDocument = number_format((int) $data['witingDocument']);
        $usersOnline = number_format((int) $data['onlineCount']);
        $partnerCredit = "".$data['partnerData']['stats']['creditbalance']."";

        $documents = array();
        foreach ($data['waitingDocumentList'] as $domain) {
        
        $lastupdate = (empty($domain['updated_at']) || strpos($domain['updated_at'], '0000') === 0) ? "N/A" : Carbon::createFromFormat('Y-m-d H:i:s', $domain['updated_at'])->diffForHumans();
            $documents[] = '
            <div class="item">
            <div class="last-activity">' . $lastupdate . '</div>
        <a href="clientsdomains.php?id=' . $domain['domainid'] . '">' . $domain['domainname'] . '</a>
        <a href="clientsdomains.php?id=' . $domain['domainid'] . '" class="description">' . $this->ActionTypes($domain['actiontype']) . ' ('. $domain['lastdescription'] .')</a>
    </div>
            
     ';
        }
        $clientOutput = implode($documents);

        return <<<EOF
<style>
.widget-alastyrinfo .item:nth-child(2n) {
    background-color: 
    #f8f8f8;
}
.widget-alastyrinfo .alastyrinfo-list {
    margin: 0 20px 20px 20px;
    font-size: .9em;
    max-height: 240px;
    overflow: auto;
}
.widget-alastyrinfo .description {
    display: block;
    font-style: italic;
    color: 
    #63cfd2;
    font-size: .9em;
}
.widget-alastyrinfo .item {
    padding: 5px 8px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.widget-alastyrinfo .last-activity {
	float: right;
	color: #959595;
	font-size: .9em;
}
</style>
<div class="icon-stats">
    <div class="row">
        <div class="col-sm-6">
            <div class="item">
                <div class="icon-holder text-center color-orange">
                    <a href="clients.php?status=Active">
                        <i class="pe-7s-file"></i>
                    </a>
                </div>
                <div class="data">
                    <div class="note">
                        Belge Bekleyen
                    </div>
                    <div class="number">
                            <span class="color-orange">{$witingDocument}</span>
                            <span class="unit">Alan Adı</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="item">
                <div class="icon-holder text-center color-pink">
                    <i class="pe-7s-cash"></i>
                </div>
                <div class="data">
                    <div class="note">
                        Kredi Tutarınız
                    </div>
                    <div class="number">
                        <span class="color-pink">{$partnerCredit}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="alastyrinfo-list">
    {$clientOutput}
</div>
EOF;
    }


    function ActionTypes($params){
        $actiontype['1000'] = "Yeni Alan Adı Başvurusu";
        $actiontype['1001'] = "Alan Adı Başvurusu Onayı";
        $actiontype['1018'] = "Alan Adı Başvurusu Reddi";
        $actiontype['1019'] = "Alan Adı Başvurusu İptal";
        $actiontype['1020'] = "Alan Adı Geri Alma Reddi";
        $actiontype['1118'] = "IDN Alan Adı Başvurusu Reddi";
        $actiontype['3010'] = "Sahip Bilgilerini Değiştir";
        $actiontype['3022'] = "Sahip Değişikliği";
        $actiontype['7001'] = "Fatura Oluşturma";
        $actiontype['9000'] = "Belge Kabul Ediliyor";
        $actiontype['9003'] = "Belge Uyarısı";
        $actiontype['10001'] = "Belge Ulaştı";
        $actiontype['10002'] = "Alan Adı Belgeyle İlişkilendirdi";
        $actiontype['10003'] = "Belge Kapandı";
        $actiontype['10008'] = "Alan Adı Belgeyle İlişkilendirmedi";
        return $actiontype[$params];
    }
}


