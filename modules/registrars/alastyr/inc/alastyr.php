<?php
use WHMCS\ClientArea;
use WHMCS\Database\Capsule;
function alastyr_getRequest($command, $postfields, $auth){
    if (!$auth['ApiSecret']) { return array( "error" => "Missing Api Secret. Please navigate to Setup > Domain Registrars to configure." ); }
    if (!$auth['ApiKey']) { return array( "error" => "Missing API Key. Please navigate to Setup > Domain Registrars to configure." ); }
    $sPost = "";
    foreach ($postfields as $field => $data) {
        if (is_array($data)) {
            foreach ($data as $subData) {
                $sPost .= build_query_string(array($field.'[]' => $subData), PHP_QUERY_RFC3986). "&";
            }
        } else {
            if (($field == 'ns') or ($field == 'ip')) {
                $sPost .= $data;
            } else {
                $sPost .= "" . $field . "=" . rawurlencode($data) . "&";
            }
        }
    }
    $sPost = str_replace('&&', '&', $sPost);
    $sPost .= "&api_key=".$auth['ApiKey']."&api_secret=".$auth['ApiSecret']."";


    $sTarget = "https://api.alastyr.com/v2/".$command;
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
function alastyr_CheckDomainStatus($params){
    $params = array_merge($params, alastyr_GetConfigurationParamsData());
    $auth['ApiSecret'] = $params['ApiSecret'];
    $auth['ApiKey'] = $params['ApiKey'];
    $postfields = array();
    $postfields['domainName'] = $params['domainName'];

    if($params['TestMode'] ==  "on"){
        $postfields['mode'] = "test";
    }
    if($params['mode'] ==  "test"){
        $postfields['mode'] = "test";
    }

    $res = alastyr_getRequest("getdomainstatusdomain", $postfields, $auth);
    $result = $res['result'];

    return $result;
}
function alastyr_DomainInfo($params){
    $params = array_merge($params, alastyr_GetConfigurationParamsData());
    $auth['ApiSecret'] = $params['ApiSecret'];
    $auth['ApiKey'] = $params['ApiKey'];
    $postfields = array();
    $postfields['domainName'] = $params['domainName'];

    if($params['TestMode'] ==  "on"){
        $postfields['mode'] = "test";
    }
    if($params['mode'] ==  "test"){
        $postfields['mode'] = "test";
    }

    $res = alastyr_getRequest("getdomaininfo", $postfields, $auth);
    $result = $res['result'];

    return $result;
}
function alastyr_GetConfigurationParamsrData() {
    $result = Capsule::table('tblregistrars')->where('registrar', 'resellerclub')->get();
    foreach($result as $item) {
        $params[$item->setting] = decrypt($item->value);
    }
    return $params;
}
function alastyr_GetConfigurationParamsData() {
    $result = Capsule::table('tblregistrars')->where('registrar', 'alastyr')->get();
    foreach($result as $item) {
        $params[$item->setting] = decrypt($item->value);
    }
    return $params;
}
function alastyr_GetDomainData($domain) {
    $domain = Capsule::table('tbldomains')->where('domain', $domain)->first();
    $return = json_decode(json_encode($domain), true);
    return $return;
}
function alastyr_GetDomainUserData($domain) {
    $user = Capsule::table('tbldomains')->where('domain', $domain)->first();
    $result = Capsule::table('tblclients')->where('id', $user->userid)->first();
    $return = json_decode(json_encode($result), true);
    return $return;
}
function alastyr_moduleConfig() {
    $result = Capsule::table('tbladdonmodules')->where('module', 'tr_domain')->get();
    foreach($result as $item) {
        $params[$item->setting] = $item->value;
    }
    return $params;
}
function alastyr_ErrorMesages($params){

    if(is_array($params)){
        $params = $params[0];
    }

    if($params == 'ERR_DOMAIN_NOT_FOUND'){
        $return = "Domain aktif değil.";
    }
    if($params == 'ERR_DOMAIN_ALREADY_APPLIED'){
        $return = "Domain başvurusu yapılmış.";
    }
    if($params == 'ERR_DOMAIN_IS_ACTIVE'){
        $return = "Domain aktif.";
    }
    if($params == 'ERR_MAX_DURATION_REACHED'){
        $return = "Maksimum kayıt süresi aşıldı.";
    }
    if($params == 'ERR_INVALID_CITIZENID'){
        $return = "Hatalı TC Kimlik no.";
    }
    if($params == 'REQUIRE_CITIZEN_ID'){
        $return = "TC Kimlik no veya Vergi Dairesi ile Vergi Numarası gerekli";
    }
    if($params == 'EMPTY_NAME'){
        $return = "Ad Soyad veya Firma ünvanı gerekli";
    }
    if($params == 'ERR_INVALID_DOCUMENT_TYPE'){
        $return = "Hatalı döküman türü.";
    }
    if($params == 'ERR_INVALID_FILE_TYPE'){
        $return = "Hatalı dosya türü.";
    }
    if($params == 'ERR_INVALID_NAME_SERVER'){
        $return = "Hatalı nameserver adresi.";
    }
    if($params == 'EMPTY_INPUT'){
        $return = "Gerekli Form verilerinde eksik var.";
    }
    if($params == 'INSUFFICCENT_FUNDS'){
        $return = "Yetersiz bakiye.";
    }


    if(empty($return)){
        return $params;
    } else {
        return $return;
    }

}

function alastyr_ActionTypes($params){
    $actiontype['1000'] = "Yeni Alan Adı Başvurusu";
    $actiontype['1001'] = "Alan Adı Başvurusu Onayı";
    $actiontype['1018'] = "Alan Adı Başvurusu Reddi";
    $actiontype['1019'] = "Alan Adı Başvurusu İptal";
    $actiontype['1020'] = "Alan Adı Geri Alma Reddi";
    $actiontype['1118'] = "IDN Alan Adı Başvurusu Reddi";
    $actiontype['3010'] = "Sahip Bilgilerini Değiştir";
    $actiontype['3022'] = "Sahip Değişikliği";
    $actiontype['7001'] = "Fatura Oluşturma";
    $actiontype['9000'] = "Belge Kabul Edildi";
    $actiontype['9003'] = "Belge Uyarısı, yeniden gönderin";
    $actiontype['10001'] = "Belge Ulaştı";
    $actiontype['10002'] = "Alan Adı Belgeyle İlişkilendirdi";
    $actiontype['10003'] = "Belge Kapandı";
    $actiontype['10008'] = "Alan Adı Belgeyle İlişkilendirmedi";
    return $actiontype[$params];
}

function alastyr_addDomainLog($array){

    $result = Capsule::table('mod_alastyrdomain')->where('domainname', $array['domainname'])->first();
    $date = date("Y-m-d H:i:s");

    if((empty($array['userid'])) or (empty($array['domainid']))){
        $domainresult = Capsule::table('tbldomains')->where('domain', $array['domainname'])->first();
        $array['domainid'] = $domainresult->id;
        $array['userid'] = $domainresult->userid;
    }
    logModuleCall('alastyr', 'addDomainLog', $array, $_FILES['attachments']['name'], '', '');
    $array['actioncomment'] = trim(str_replace('[Lutfen Seciniz]', '', $array['actioncomment']));
    if((!$result->id)){
        $insert = Capsule::table('mod_alastyrdomain')->insertGetId(
            [
                'domainid' => $array['domainid'],
                'userid' => $array['userid'],
                'domainname' => $array['domainname'],
                'ticketid' => $array['ticketid'],
                'laststatus' => $array['laststatus'],
                'actiontype' => $array['actiontype'],
                'lastdescription' => $array['lastdescription'],
                'updated_at' => $date,
                'created_at' => $date,
            ]
        );

        $result = Capsule::table('mod_alastyrdomainlog')->insert(
            [
                'domainid' => $insert,
                'userid' => $array['userid'],
                'ticketid' => $array['ticketid'],
                'status' => $array['laststatus'],
                'actiontype' => $array['actiontype'],
                'actioncomment' => $array['actioncomment'],
                'description' => $array['lastdescription'],
                'created_at' => $date,
            ]
        );
    } else {

        if($array['logtype'] == 'action'){

            $insert = Capsule::table('mod_alastyrdomainlog')->insert(
                [
                    'domainid' => $result->id,
                    'userid' => $result->userid,
                    'ticketid' => $array['ticketid'],
                    'status' => $result->laststatus,
                    'actiontype' => $array['actiontype'],
                    'actioncomment' => $array['actioncomment'],
                    'description' => $array['lastdescription'],
                    'created_at' => $date,
                ]
            );

        } else {
            if (!empty($array['ticketid'])) {
                if (($result->lastdescription != $array['lastdescription']) or ($result->actiontype != $array['actiontype'])) {
                    $update = Capsule::table('mod_alastyrdomain')->where('id', $result->id)->update(
                        [
                            'ticketid' => $array['ticketid'],
                            'laststatus' => $array['laststatus'],
                            'actiontype' => $array['actiontype'],
                            'lastdescription' => $array['lastdescription'],
                            'updated_at' => $date,
                        ]
                    );

                    $insert = Capsule::table('mod_alastyrdomainlog')->insert(
                        [
                            'domainid' => $result->id,
                            'userid' => $result->userid,
                            'ticketid' => $array['ticketid'],
                            'status' => $array['laststatus'],
                            'actiontype' => $array['actiontype'],
                            'actioncomment' => $array['actioncomment'],
                            'description' => $array['lastdescription'],
                            'created_at' => $date,
                        ]
                    );
                }
            }
        }
    }
}
function alastyr_ViewDomainLogs($params){
    $domain = $params['sld'].'.'.$params['tld'];
    //$params = array_merge($params,alastyr_GetConfigurationParamsData());
    $domaindata = Capsule::table('mod_alastyrdomain')->where('domainname', $domain)->first();

    $result = Capsule::table('mod_alastyrdomainlog')->where('domainid', $domaindata->id)->orderby('id', 'asc')->get();
    $return = json_decode(json_encode($result), true);
    $table = '<!--Table-->
    <table cellspacing="1" cellpadding="3" width="100%" border="0" class="datatable">
        <thead>
        <tr>
            <th>Açıklama</th>
            <th>Tarih</th>
        </tr>
        </thead>        
        <tbody>        
    ';
    $token = generate_token($type = "link");
    foreach($return as $notesData) {
        $action = "";
        $action = alastyr_ActionTypes($notesData['actiontype']). ' - ';
        if(!empty($notesData['actioncomment'])){
            $action .= $notesData['actioncomment'] . ' - ';
        }
        $table .= '            
        <tr>
            <td>'.$action.str_replace('.jpg"', '.jpg' . $token. '"',str_replace('.jpeg"', '.jpeg' . $token. '"',str_replace('.tiff"', '.tiff' . $token. '"',str_replace('.pdf"', '.pdf' . $token. '"', $notesData['description'])))).'</td>
            <td>'.$notesData['created_at'].'</td>
        </tr>
        ';
    }

    $table .= '
        </tbody>
    </table>
    ';

    $output = '<!-- Modal -->
    <div class="modal fade" id="view_notes" tabindex="-1" role="dialog" aria-labelledby="view-notes" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-body"> '. $table .' </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    ';

    # Return domain notes
    return $output;
}
function alastyr_ViewUploadForm($params){
    $domain = $params['sld'].'.'.$params['tld'];
    $params = array_merge($params,alastyr_GetConfigurationParamsData());
    $token = generate_token($type = "link");
    if($params['ordertype'] == 'transfer'){
        $firstitem = '<option value="İdari Sorumlu Değişiklik Formu">İdari Sorumlu Değişiklik Formu</option>';
    } else {
        $firstitem = '<option value="Alan Adı Tahsis Formu">Alan Adı Tahsis Formu</option>';
    }

    $table = '<!--Table-->
    <table cellspacing="1" cellpadding="3" width="100%" border="0" class="datatable">
        <thead>
        <tr>
            <th><div class="col-sm-5">Belge Türü</div><div class="col-sm-7">Belge Seçimi</div></th>
        </tr>
        </thead>        
        <tbody>        
    ';
    $table .= '            
        <tr>
            <td>
            <div id="formtable" class="inner-container"  style="display: none;">
<div class="row">
<div class="col-sm-5">
<select class="form-control" name="doctype[]" id="doctype"><option value="">Seçiniz</option>'. $firstitem .'<option value="Marka tescil belgesi">Marka tescil belgesi</option><option value="Ticaret Sicili gazetesi">Ticaret Sicili gazetesi</option><option value="Ticari Sicil Tasdiknamesi">Ticari Sicil Tasdiknamesi</option><option value="Faaliyet belgesi">Faaliyet belgesi</option><option value="Oda sicil kayit sureti">Oda sicil kayit sureti</option><option value="Marka basvurusu belgesi">Marka basvurusu belgesi</option><option value="Sicil tasdiknamesi (ESO)">Sicil tasdiknamesi (ESO)</option><option value="Mesleki faaliyet belgesi (ESO)">Mesleki faaliyet belgesi (ESO)</option><option value="Mesleki faaliyet belgesi (Odalar)<">Mesleki faaliyet belgesi (Odalar)</option><option value="TBB den onay yazisi">TBB den onay yazisi</option><option value="Nufus cuzdani fotokopisi">Nufus cuzdani fotokopisi</option><option value="T.C kimlik nosu ciktisi">T.C kimlik nosu ciktisi</option><option value="TTB den onay yazisi<">TTB den onay yazisi</option><option value="Distribütörlük yetki yazisi<">Distribütörlük yetki yazisi</option><option value="Turizm isletme belgesi">Turizm isletme belgesi</option><option value="Seyahat acentesi isletme belgesi">Seyahat acentesi isletme belgesi</option><option value="Ozel Ogretim Kurumlari Ruhsatnamesi">Ozel Ogretim Kurumlari Ruhsatnamesi</option><option value="Özel Hastane Ruhsati">Özel Hastane Ruhsati</option><option value="Film adlari için izin belgesi">Film adlari için izin belgesi</option><option value="Sanatçi odalarindan üye kayit belgesi">Sanatçi odalarindan üye kayit belgesi</option><option value="Televizyon Gösterim Belgesi">Televizyon Gösterim Belgesi</option><option value="RTÜK frekans tahsis bildirim yazısi">RTÜK frekans tahsis bildirim yazısi</option><option value="Mevkute Beyannamesi">Mevkute Beyannamesi</option><option value="Tıbbi Müs. Belgesi">Tıbbi Müs. Belgesi</option><option value="İrtibat Bürosu Açilim Belgesi">İrtibat Bürosu Açilim Belgesi</option><option value="T.C.Gemi Tasdiknamesi">T.C.Gemi Tasdiknamesi</option><option value="Fuar Izni Yazısı">Fuar Izni Yazısı</option><option value="Fuar Takvimi Tebliği">Fuar Takvimi Tebliği</option><option value="Özel Servis Numarasi Tahsis Belgesi">Özel Servis Numarasi Tahsis Belgesi</option><option value="TID onay yazisi">TID onay yazisi</option><option value="ISS Genel Izin Belgesi">ISS Genel Izin Belgesi</option><option value="Vakif senedi">Vakif senedi</option><option value="Dernek tuzugu">Dernek tuzugu</option><option value="Sivil toplum orgutlerinden destek yazisi">Sivil toplum orgutlerinden destek yazisi</option><option value="Ziraat odasi kayit belgesi">Ziraat odasi kayit belgesi</option><option value="Kurulus kanunu">Kurulus kanunu</option><option value="Kitabın aslı">Kitabın aslı</option><option value="Gazetenin aslı">Gazetenin aslı</option><option value="Derginin aslı">Derginin aslı</option><option value="Diger (ilk kez iletilecek olanlar)">Diger (ilk kez iletilecek olanlar)</option></select>
</div>
                    <div class="col-sm-5">
                        <input type="file" name="attachments[]" class="form-control">
                    </div>
                    <div class="col-sm-2">
                        <a href="#" id="add-file-upload" class="btn btn-success btn-block add-file-upload" data-more-id="fileuploads"><i class="fas fa-plus"></i> Daha </a>
                    </div>
                </div>
            </div>
            </td>
        </tr>
        ';
    $table .= '
        </tbody>
    </table>
    ';

    $output = '
    <div class="modal fade" id="upload-form" tabindex="-1" role="dialog" aria-labelledby="upload-form" aria-hidden="false">
    
        <div class="modal-dialog modal-lg" role="form">
            <div class="modal-content">
                <div class="modal-body" id="addform"> '. $table .' </form></div>
                <div class="modal-footer">
                <button  form="file-upload-form"  type="button" onclick="$(\'#file-upload-form\').submit();return false" class="btn btn-primary">Gönder</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Kapat</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    $(\'#addform\').append("<form enctype=\"multipart/form-data\" name=\"file-upload-form\" id=\"file-upload-form\" method=\"post\" action=\"clientsdomains.php?action=domaindetails&id='.$params['domainid'].'&regaction=custom&ac=DocumentUpload'.$token.'\">" + $(\'#formtable\').html() + "<div id=\"fileuploads\"></div>");
     $(\'.add-file-upload\').click(function () {
        var moreId = $(this).data(\'more-id\');
        $(\'#\' + moreId).append("<div class=\"inner-container\"  style=\"display: block;\"><div class=\"row\"><div class=\"col-sm-5\"><select class=\"form-control\" name=\"doctype[]\" id=\"doctype\"><option value=\"\">Seçiniz</option><option value=\"Alan Adı Tahsis Formu\">Alan Adı Tahsis Formu</option><option value=\"Marka tescil belgesi\">Marka tescil belgesi</option><option value=\"Ticaret Sicili gazetesi\">Ticaret Sicili gazetesi</option><option value=\"Ticari Sicil Tasdiknamesi\">Ticari Sicil Tasdiknamesi</option><option value=\"Faaliyet belgesi\">Faaliyet belgesi</option><option value=\"Oda sicil kayit sureti\">Oda sicil kayit sureti</option><option value=\"Marka basvurusu belgesi\">Marka basvurusu belgesi</option><option value=\"Sicil tasdiknamesi (ESO)\">Sicil tasdiknamesi (ESO)</option><option value=\"Mesleki faaliyet belgesi (ESO)\">Mesleki faaliyet belgesi (ESO)</option><option value=\"Mesleki faaliyet belgesi (Odalar)<\">Mesleki faaliyet belgesi (Odalar)</option><option value=\"TBB den onay yazisi\">TBB den onay yazisi</option><option value=\"Nufus cuzdani fotokopisi\">Nufus cuzdani fotokopisi</option><option value=\"T.C kimlik nosu ciktisi\">T.C kimlik nosu ciktisi</option><option value=\"TTB den onay yazisi<\">TTB den onay yazisi</option><option value=\"Distribütörlük yetki yazisi<\">Distribütörlük yetki yazisi</option><option value=\"Turizm isletme belgesi\">Turizm isletme belgesi</option><option value=\"Seyahat acentesi isletme belgesi\">Seyahat acentesi isletme belgesi</option><option value=\"Ozel Ogretim Kurumlari Ruhsatnamesi\">Ozel Ogretim Kurumlari Ruhsatnamesi</option><option value=\"Özel Hastane Ruhsati\">Özel Hastane Ruhsati</option><option value=\"Film adlari için izin belgesi\">Film adlari için izin belgesi</option><option value=\"Sanatçi odalarindan üye kayit belgesi\">Sanatçi odalarindan üye kayit belgesi</option><option value=\"Televizyon Gösterim Belgesi\">Televizyon Gösterim Belgesi</option><option value=\"RTÜK frekans tahsis bildirim yazısi\">RTÜK frekans tahsis bildirim yazısi</option><option value=\"Mevkute Beyannamesi\">Mevkute Beyannamesi</option><option value=\"Tıbbi Müs. Belgesi\">Tıbbi Müs. Belgesi</option><option value=\"İrtibat Bürosu Açilim Belgesi\">İrtibat Bürosu Açilim Belgesi</option><option value=\"T.C.Gemi Tasdiknamesi\">T.C.Gemi Tasdiknamesi</option><option value=\"Fuar Izni Yazısı\">Fuar Izni Yazısı</option><option value=\"Fuar Takvimi Tebliği\">Fuar Takvimi Tebliği</option><option value=\"Özel Servis Numarasi Tahsis Belgesi\">Özel Servis Numarasi Tahsis Belgesi</option><option value=\"TID onay yazisi\">TID onay yazisi</option><option value=\"ISS Genel Izin Belgesi\">ISS Genel Izin Belgesi</option><option value=\"Vakif senedi\">Vakif senedi</option><option value=\"Dernek tuzugu\">Dernek tuzugu</option><option value=\"Sivil toplum orgutlerinden destek yazisi\">Sivil toplum orgutlerinden destek yazisi</option><option value=\"Ziraat odasi kayit belgesi\">Ziraat odasi kayit belgesi</option><option value=\"Kurulus kanunu\">Kurulus kanunu</option><option value=\"Kitabın aslı\">Kitabın aslı</option><option value=\"Gazetenin aslı\">Gazetenin aslı</option><option value=\"Derginin aslı\">Derginin aslı</option><option value=\"Diger (ilk kez iletilecek olanlar)\">Diger (ilk kez iletilecek olanlar)</option></select></div><div class=\"col-sm-5\"><input type=\"file\" name=\"attachments[]\" class=\"form-control\"></div></div></div>");
        return false;
    });
</script>
    ';
    return $output;
}
function alastyr_ViewClientUploadForm($params){
    $domain = $params['sld'].'.'.$params['tld'];
    $params = array_merge($params,alastyr_GetConfigurationParamsData());

    if($params['ordertype'] == 'transfer'){
        $firstitem = '<option value="İdari Sorumlu Değişiklik Formu">İdari Sorumlu Değişiklik Formu</option>';
    } else {
        $firstitem = '<option value="Alan Adı Tahsis Formu">Alan Adı Tahsis Formu</option>';
    }

    $table = '<!--Table-->
    <table cellspacing="1" cellpadding="3" width="100%" border="0" class="datatable">
        <thead>
        <tr>
            <th><div class="col-sm-5">Belge Türü</div><div class="col-sm-7">Belge Seçimi</div></th>
        </tr>
        </thead>        
        <tbody>        
    ';
    $table .= '            
        <tr>
            <td>
            <div id="formtable" class="inner-container"  style="display: none;">
<div class="row">
<div class="col-sm-5">
<select class="form-control" name="doctype[]" id="doctype"><option value="">Seçiniz</option>'. $firstitem .'<option value="Marka tescil belgesi">Marka tescil belgesi</option><option value="Ticaret Sicili gazetesi">Ticaret Sicili gazetesi</option><option value="Ticari Sicil Tasdiknamesi">Ticari Sicil Tasdiknamesi</option><option value="Faaliyet belgesi">Faaliyet belgesi</option><option value="Oda sicil kayit sureti">Oda sicil kayit sureti</option><option value="Marka basvurusu belgesi">Marka basvurusu belgesi</option><option value="Sicil tasdiknamesi (ESO)">Sicil tasdiknamesi (ESO)</option><option value="Mesleki faaliyet belgesi (ESO)">Mesleki faaliyet belgesi (ESO)</option><option value="Mesleki faaliyet belgesi (Odalar)<">Mesleki faaliyet belgesi (Odalar)</option><option value="TBB den onay yazisi">TBB den onay yazisi</option><option value="Nufus cuzdani fotokopisi">Nufus cuzdani fotokopisi</option><option value="T.C kimlik nosu ciktisi">T.C kimlik nosu ciktisi</option><option value="TTB den onay yazisi<">TTB den onay yazisi</option><option value="Distribütörlük yetki yazisi<">Distribütörlük yetki yazisi</option><option value="Turizm isletme belgesi">Turizm isletme belgesi</option><option value="Seyahat acentesi isletme belgesi">Seyahat acentesi isletme belgesi</option><option value="Ozel Ogretim Kurumlari Ruhsatnamesi">Ozel Ogretim Kurumlari Ruhsatnamesi</option><option value="Özel Hastane Ruhsati">Özel Hastane Ruhsati</option><option value="Film adlari için izin belgesi">Film adlari için izin belgesi</option><option value="Sanatçi odalarindan üye kayit belgesi">Sanatçi odalarindan üye kayit belgesi</option><option value="Televizyon Gösterim Belgesi">Televizyon Gösterim Belgesi</option><option value="RTÜK frekans tahsis bildirim yazısi">RTÜK frekans tahsis bildirim yazısi</option><option value="Mevkute Beyannamesi">Mevkute Beyannamesi</option><option value="Tıbbi Müs. Belgesi">Tıbbi Müs. Belgesi</option><option value="İrtibat Bürosu Açilim Belgesi">İrtibat Bürosu Açilim Belgesi</option><option value="T.C.Gemi Tasdiknamesi">T.C.Gemi Tasdiknamesi</option><option value="Fuar Izni Yazısı">Fuar Izni Yazısı</option><option value="Fuar Takvimi Tebliği">Fuar Takvimi Tebliği</option><option value="Özel Servis Numarasi Tahsis Belgesi">Özel Servis Numarasi Tahsis Belgesi</option><option value="TID onay yazisi">TID onay yazisi</option><option value="ISS Genel Izin Belgesi">ISS Genel Izin Belgesi</option><option value="Vakif senedi">Vakif senedi</option><option value="Dernek tuzugu">Dernek tuzugu</option><option value="Sivil toplum orgutlerinden destek yazisi">Sivil toplum orgutlerinden destek yazisi</option><option value="Ziraat odasi kayit belgesi">Ziraat odasi kayit belgesi</option><option value="Kurulus kanunu">Kurulus kanunu</option><option value="Kitabın aslı">Kitabın aslı</option><option value="Gazetenin aslı">Gazetenin aslı</option><option value="Derginin aslı">Derginin aslı</option><option value="Diger (ilk kez iletilecek olanlar)">Diger (ilk kez iletilecek olanlar)</option></select>
</div>
                    <div class="col-sm-5">
                        <input type="file" name="attachments[]" class="form-control">
                    </div>
                    <div class="col-sm-2">
                        <a href="#" id="add-file-upload" class="btn btn-success btn-block add-file-upload" data-more-id="fileuploads"><i class="fas fa-plus"></i> Daha </a>
                    </div>
                </div>
            </div>
            </td>
        </tr>
        ';
    $table .= '
        </tbody>
    </table>
    ';

    $output = '
    <div class="modal fade" id="upload-form" tabindex="-1" role="dialog" aria-labelledby="upload-form" aria-hidden="false">
    
        <div class="modal-dialog modal-lg" role="form">
            <div class="modal-content">
            <div class="modal-header"><h2 class="title-box">TR Alan Adı Belge Yükleme</h2></div>
                <div class="modal-body" id="addform"> 
                <div class="filetypeerror"></div>
                '. $table .'
                 </form></div>
                <div class="modal-footer">
                <button id="fileuploadbutton" form="file-upload-form"  type="button" onclick="$(\'#file-upload-form\').submit();return false" class="btn btn-primary">Gönder</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Kapat</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    $(\'#Primary_Sidebar-Domain_Details_Management-Belge_Gönder\').on("click", function (e) {e.preventDefault(); $(\'#upload-form\').modal(\'show\'); });
   
    $(\'#addform\').append("<form enctype=\"multipart/form-data\" name=\"file-upload-form\" id=\"file-upload-form\" method=\"post\" action=\"clientarea.php?action=domaindetails&id='.$params['domainid'].'&modop=custom&a=SendDocument\">" + $(\'#formtable\').html() + "<div id=\"fileuploads\"></div>");
     $(\'.add-file-upload\').click(function () {
        var moreId = $(this).data(\'more-id\');
        $(\'#\' + moreId).append("<div class=\"inner-container\"  style=\"display: block;\"><div class=\"row\"><div class=\"col-sm-5\"><select class=\"form-control\" name=\"doctype[]\" id=\"doctype\"><option value=\"\">Seçiniz</option><option value=\"Alan Adı Tahsis Formu\">Alan Adı Tahsis Formu</option><option value=\"Marka tescil belgesi\">Marka tescil belgesi</option><option value=\"Ticaret Sicili gazetesi\">Ticaret Sicili gazetesi</option><option value=\"Ticari Sicil Tasdiknamesi\">Ticari Sicil Tasdiknamesi</option><option value=\"Faaliyet belgesi\">Faaliyet belgesi</option><option value=\"Oda sicil kayit sureti\">Oda sicil kayit sureti</option><option value=\"Marka basvurusu belgesi\">Marka basvurusu belgesi</option><option value=\"Sicil tasdiknamesi (ESO)\">Sicil tasdiknamesi (ESO)</option><option value=\"Mesleki faaliyet belgesi (ESO)\">Mesleki faaliyet belgesi (ESO)</option><option value=\"Mesleki faaliyet belgesi (Odalar)<\">Mesleki faaliyet belgesi (Odalar)</option><option value=\"TBB den onay yazisi\">TBB den onay yazisi</option><option value=\"Nufus cuzdani fotokopisi\">Nufus cuzdani fotokopisi</option><option value=\"T.C kimlik nosu ciktisi\">T.C kimlik nosu ciktisi</option><option value=\"TTB den onay yazisi<\">TTB den onay yazisi</option><option value=\"Distribütörlük yetki yazisi<\">Distribütörlük yetki yazisi</option><option value=\"Turizm isletme belgesi\">Turizm isletme belgesi</option><option value=\"Seyahat acentesi isletme belgesi\">Seyahat acentesi isletme belgesi</option><option value=\"Ozel Ogretim Kurumlari Ruhsatnamesi\">Ozel Ogretim Kurumlari Ruhsatnamesi</option><option value=\"Özel Hastane Ruhsati\">Özel Hastane Ruhsati</option><option value=\"Film adlari için izin belgesi\">Film adlari için izin belgesi</option><option value=\"Sanatçi odalarindan üye kayit belgesi\">Sanatçi odalarindan üye kayit belgesi</option><option value=\"Televizyon Gösterim Belgesi\">Televizyon Gösterim Belgesi</option><option value=\"RTÜK frekans tahsis bildirim yazısi\">RTÜK frekans tahsis bildirim yazısi</option><option value=\"Mevkute Beyannamesi\">Mevkute Beyannamesi</option><option value=\"Tıbbi Müs. Belgesi\">Tıbbi Müs. Belgesi</option><option value=\"İrtibat Bürosu Açilim Belgesi\">İrtibat Bürosu Açilim Belgesi</option><option value=\"T.C.Gemi Tasdiknamesi\">T.C.Gemi Tasdiknamesi</option><option value=\"Fuar Izni Yazısı\">Fuar Izni Yazısı</option><option value=\"Fuar Takvimi Tebliği\">Fuar Takvimi Tebliği</option><option value=\"Özel Servis Numarasi Tahsis Belgesi\">Özel Servis Numarasi Tahsis Belgesi</option><option value=\"TID onay yazisi\">TID onay yazisi</option><option value=\"ISS Genel Izin Belgesi\">ISS Genel Izin Belgesi</option><option value=\"Vakif senedi\">Vakif senedi</option><option value=\"Dernek tuzugu\">Dernek tuzugu</option><option value=\"Sivil toplum orgutlerinden destek yazisi\">Sivil toplum orgutlerinden destek yazisi</option><option value=\"Ziraat odasi kayit belgesi\">Ziraat odasi kayit belgesi</option><option value=\"Kurulus kanunu\">Kurulus kanunu</option><option value=\"Kitabın aslı\">Kitabın aslı</option><option value=\"Gazetenin aslı\">Gazetenin aslı</option><option value=\"Derginin aslı\">Derginin aslı</option><option value=\"Diger (ilk kez iletilecek olanlar)\">Diger (ilk kez iletilecek olanlar)</option></select></div><div class=\"col-sm-5\"><input type=\"file\" name=\"attachments[]\" class=\"form-control\"></div><div class=\"col-sm-2\"></div></div></div>");
        return false;
    });
</script>
    ';
    return $output;
}

function alastyr_ViewClientMultiUploadForm($params){


    $domainlist = "";
    foreach ($params as $domain) {
        $domainlist .= '<option value="'.$domain.'">'.$domain.'</option>';
    }
    $params = array_merge($params,alastyr_GetConfigurationParamsData());

    if($params['ordertype'] == 'transfer'){
        $firstitem = '<option value="İdari Sorumlu Değişiklik Formu">İdari Sorumlu Değişiklik Formu</option>';
    } else {
        $firstitem = '<option value="Alan Adı Tahsis Formu">Alan Adı Tahsis Formu</option>';
    }

    $table = '<!--Table-->
       
    ';
    $table .= '  <div id="formtable" class="inner-container"  style="display: none;"> <label for="selectdomain" style="text-align: center" class="col-sm-12">Belge Yüklenecek Domain</label>
<div class="col-sm-6 col-sm-offset-3"><select class="form-control col-sm-7" id="selectdomain" name="uploaddomain">'.$domainlist.' </select></div>
    <table cellspacing="1" cellpadding="3" width="100%" border="0" class="datatable">
        <thead>
        <tr>
            <th><div class="col-sm-5">Belge Türü</div><div class="col-sm-7">Belge Seçimi</div></th>
        </tr>
        </thead>        
        <tbody>          
        <tr>
            <td>
            
<div class="row">
<div class="col-sm-5">
<select class="form-control" name="doctype[]" id="doctype"><option value="">Seçiniz</option>'. $firstitem .'<option value="Marka tescil belgesi">Marka tescil belgesi</option><option value="Ticaret Sicili gazetesi">Ticaret Sicili gazetesi</option><option value="Ticari Sicil Tasdiknamesi">Ticari Sicil Tasdiknamesi</option><option value="Faaliyet belgesi">Faaliyet belgesi</option><option value="Oda sicil kayit sureti">Oda sicil kayit sureti</option><option value="Marka basvurusu belgesi">Marka basvurusu belgesi</option><option value="Sicil tasdiknamesi (ESO)">Sicil tasdiknamesi (ESO)</option><option value="Mesleki faaliyet belgesi (ESO)">Mesleki faaliyet belgesi (ESO)</option><option value="Mesleki faaliyet belgesi (Odalar)<">Mesleki faaliyet belgesi (Odalar)</option><option value="TBB den onay yazisi">TBB den onay yazisi</option><option value="Nufus cuzdani fotokopisi">Nufus cuzdani fotokopisi</option><option value="T.C kimlik nosu ciktisi">T.C kimlik nosu ciktisi</option><option value="TTB den onay yazisi<">TTB den onay yazisi</option><option value="Distribütörlük yetki yazisi<">Distribütörlük yetki yazisi</option><option value="Turizm isletme belgesi">Turizm isletme belgesi</option><option value="Seyahat acentesi isletme belgesi">Seyahat acentesi isletme belgesi</option><option value="Ozel Ogretim Kurumlari Ruhsatnamesi">Ozel Ogretim Kurumlari Ruhsatnamesi</option><option value="Özel Hastane Ruhsati">Özel Hastane Ruhsati</option><option value="Film adlari için izin belgesi">Film adlari için izin belgesi</option><option value="Sanatçi odalarindan üye kayit belgesi">Sanatçi odalarindan üye kayit belgesi</option><option value="Televizyon Gösterim Belgesi">Televizyon Gösterim Belgesi</option><option value="RTÜK frekans tahsis bildirim yazısi">RTÜK frekans tahsis bildirim yazısi</option><option value="Mevkute Beyannamesi">Mevkute Beyannamesi</option><option value="Tıbbi Müs. Belgesi">Tıbbi Müs. Belgesi</option><option value="İrtibat Bürosu Açilim Belgesi">İrtibat Bürosu Açilim Belgesi</option><option value="T.C.Gemi Tasdiknamesi">T.C.Gemi Tasdiknamesi</option><option value="Fuar Izni Yazısı">Fuar Izni Yazısı</option><option value="Fuar Takvimi Tebliği">Fuar Takvimi Tebliği</option><option value="Özel Servis Numarasi Tahsis Belgesi">Özel Servis Numarasi Tahsis Belgesi</option><option value="TID onay yazisi">TID onay yazisi</option><option value="ISS Genel Izin Belgesi">ISS Genel Izin Belgesi</option><option value="Vakif senedi">Vakif senedi</option><option value="Dernek tuzugu">Dernek tuzugu</option><option value="Sivil toplum orgutlerinden destek yazisi">Sivil toplum orgutlerinden destek yazisi</option><option value="Ziraat odasi kayit belgesi">Ziraat odasi kayit belgesi</option><option value="Kurulus kanunu">Kurulus kanunu</option><option value="Kitabın aslı">Kitabın aslı</option><option value="Gazetenin aslı">Gazetenin aslı</option><option value="Derginin aslı">Derginin aslı</option><option value="Diger (ilk kez iletilecek olanlar)">Diger (ilk kez iletilecek olanlar)</option></select>
</div>
                    <div class="col-sm-5">
                        <input type="file" name="attachments[]" class="form-control">
                    </div>
                    <div class="col-sm-2">
                        <a href="#" id="add-file-upload" class="btn btn-success btn-block add-file-upload" data-more-id="fileuploads"><i class="fas fa-plus"></i> Daha </a>
                    </div>
                </div>
            
            </td>
        </tr></tbody>
    </table></div>
        ';
    $table .= '
        
    ';

    $output = '
    <div class="modal fade" id="upload-form" tabindex="-1" role="dialog" aria-labelledby="upload-form" aria-hidden="false">
    
        <div class="modal-dialog modal-lg" role="form">
            <div class="modal-content">
            <div class="modal-header"><h2 class="title-box">TR Alan Adı Belge Yükleme</h2></div>
                <div class="modal-body" id="addform"> 
                <div class="filetypeerror"></div>
                '. $table .'
                 </form></div>
                <div class="modal-footer">
                <button id="fileuploadbutton" form="file-upload-form"  type="button" onclick="$(\'#file-upload-form\').submit();return false" class="btn btn-primary">Gönder</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Kapat</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    $(\'#Primary_Sidebar-Domain_Details_Management-Belge_Gönder\').on("click", function (e) {e.preventDefault(); $(\'#upload-form\').modal(\'show\'); });
   
    $(\'#addform\').append("<form enctype=\"multipart/form-data\" name=\"file-upload-form\" id=\"file-upload-form\" method=\"post\" action=\"index.php?m=tr_domain&action=uploadfile\">" + $(\'#formtable\').html() + "<div id=\"fileuploads\"></div>");
     $(\'.add-file-upload\').click(function () {
        var moreId = $(this).data(\'more-id\');
        $(\'#\' + moreId).append("<div class=\"inner-container\"  style=\"display: block;\"><div class=\"row\"><div class=\"col-sm-5\"><select class=\"form-control\" name=\"doctype[]\" id=\"doctype\"><option value=\"\">Seçiniz</option><option value=\"Alan Adı Tahsis Formu\">Alan Adı Tahsis Formu</option><option value=\"Marka tescil belgesi\">Marka tescil belgesi</option><option value=\"Ticaret Sicili gazetesi\">Ticaret Sicili gazetesi</option><option value=\"Ticari Sicil Tasdiknamesi\">Ticari Sicil Tasdiknamesi</option><option value=\"Faaliyet belgesi\">Faaliyet belgesi</option><option value=\"Oda sicil kayit sureti\">Oda sicil kayit sureti</option><option value=\"Marka basvurusu belgesi\">Marka basvurusu belgesi</option><option value=\"Sicil tasdiknamesi (ESO)\">Sicil tasdiknamesi (ESO)</option><option value=\"Mesleki faaliyet belgesi (ESO)\">Mesleki faaliyet belgesi (ESO)</option><option value=\"Mesleki faaliyet belgesi (Odalar)<\">Mesleki faaliyet belgesi (Odalar)</option><option value=\"TBB den onay yazisi\">TBB den onay yazisi</option><option value=\"Nufus cuzdani fotokopisi\">Nufus cuzdani fotokopisi</option><option value=\"T.C kimlik nosu ciktisi\">T.C kimlik nosu ciktisi</option><option value=\"TTB den onay yazisi<\">TTB den onay yazisi</option><option value=\"Distribütörlük yetki yazisi<\">Distribütörlük yetki yazisi</option><option value=\"Turizm isletme belgesi\">Turizm isletme belgesi</option><option value=\"Seyahat acentesi isletme belgesi\">Seyahat acentesi isletme belgesi</option><option value=\"Ozel Ogretim Kurumlari Ruhsatnamesi\">Ozel Ogretim Kurumlari Ruhsatnamesi</option><option value=\"Özel Hastane Ruhsati\">Özel Hastane Ruhsati</option><option value=\"Film adlari için izin belgesi\">Film adlari için izin belgesi</option><option value=\"Sanatçi odalarindan üye kayit belgesi\">Sanatçi odalarindan üye kayit belgesi</option><option value=\"Televizyon Gösterim Belgesi\">Televizyon Gösterim Belgesi</option><option value=\"RTÜK frekans tahsis bildirim yazısi\">RTÜK frekans tahsis bildirim yazısi</option><option value=\"Mevkute Beyannamesi\">Mevkute Beyannamesi</option><option value=\"Tıbbi Müs. Belgesi\">Tıbbi Müs. Belgesi</option><option value=\"İrtibat Bürosu Açilim Belgesi\">İrtibat Bürosu Açilim Belgesi</option><option value=\"T.C.Gemi Tasdiknamesi\">T.C.Gemi Tasdiknamesi</option><option value=\"Fuar Izni Yazısı\">Fuar Izni Yazısı</option><option value=\"Fuar Takvimi Tebliği\">Fuar Takvimi Tebliği</option><option value=\"Özel Servis Numarasi Tahsis Belgesi\">Özel Servis Numarasi Tahsis Belgesi</option><option value=\"TID onay yazisi\">TID onay yazisi</option><option value=\"ISS Genel Izin Belgesi\">ISS Genel Izin Belgesi</option><option value=\"Vakif senedi\">Vakif senedi</option><option value=\"Dernek tuzugu\">Dernek tuzugu</option><option value=\"Sivil toplum orgutlerinden destek yazisi\">Sivil toplum orgutlerinden destek yazisi</option><option value=\"Ziraat odasi kayit belgesi\">Ziraat odasi kayit belgesi</option><option value=\"Kurulus kanunu\">Kurulus kanunu</option><option value=\"Kitabın aslı\">Kitabın aslı</option><option value=\"Gazetenin aslı\">Gazetenin aslı</option><option value=\"Derginin aslı\">Derginin aslı</option><option value=\"Diger (ilk kez iletilecek olanlar)\">Diger (ilk kez iletilecek olanlar)</option></select></div><div class=\"col-sm-5\"><input type=\"file\" name=\"attachments[]\" class=\"form-control\"></div></div></div>");
        return false;
    });
</script>
    ';
    return $output;
}

function alastyr_SendMultiDocument($params){
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

    $res = alastyr_getRequest("getdomainstatusdomain", $postfields, $auth);
    $result = $res['result'];
    $logdocuments = "";
    logModuleCall('alastyr', 'file1', $params, $_FILES['attachments']['name'], '', '');
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
                    return array('error' => 'Dosya türü desteklenmemektedir');
                }

                $handle = fopen($tmpfile, "r");              // Open the temp file
                $contents = fread($handle, filesize($tmpfile));  	// Read the temp file
                fclose($handle);                                 	// Close the temp file
                $postfields['documents']   = base64_encode($contents);


                if( ($domain_data['status'] == 'Pending Transfer')){
                    $postfields['documentType'] = 3;
                    $postfields['description'] = $params['doctype'][$key];
                    $postfields['operationType'] = 1;
                    $postfields['sourceFileName'] = $filename;
                    // $postfields['ticketNumber'] = $result['ticketNumber'];
                } else {
                    $postfields['documentType'] = 1;
                    $postfields['description'] = $params['doctype'][$key];
                    $postfields['operationType'] = 1;
                    $postfields['sourceFileName'] = $filename;
                    $postfields['ticketNumber'] = $result['ticketNumber'];
                }

                if($result['status'] == 0){
                    $res = alastyr_getRequest("uploaddocuments", $postfields, $auth);
                    if($res['result']['uploaded'] == "true") {
                        $logdocuments .= "(" . $res['result']['description'] . " :<a href=\"clientsdomains.php?action=domaindetails&id=".$params['domainid']."&regaction=custom&ac=GetDomainDocument&document=".$res['result']['file']."\"  target=\"_blank\">" . $res['result']['file'] . "</a>)";
                    } else {
                        return array('error' => $res['error']);
                    }
                }
            } else {
                return array('error' => "Yüklenecek dosyayı seçiniz");
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