<?php
use WHMCS\Carbon;
use WHMCS\Database\Capsule;
function tr_domain_config() {

    $result = Capsule::table('tblcustomfields')->where('type', 'client')->get();
    $array=	json_decode(json_encode($result), True);
    $fieldopt[] = "Seçiniz";
        foreach ($array as $res){
            $fieldopt[] = $res['id']."|".$res['fieldname'];
        }
    $options = implode(",", $fieldopt );
    $configarray = array(
        "name" => "Alastyr Domain",
        "description" => ".TR alan adı kaydı için gerekli ayarlar ve düzenlemeler. (TR uzantılı alan adlarının kayıt edilmesi için aktif edilmelidir.)",
        "version" => "1.0",
        "author" => "Alastyr",
        "fields" => array(
            "option1" => array ("FriendlyName" => "TC Kimlik No Alanı", "Type" => "dropdown", "Options" =>
               $options, "Description" => "Belgesiz alan adı kaydında kullanılacak TC Kimlik no alanı", "Default" => "", ),
            "option2" => array ("FriendlyName" => "Vergi Dairesi Alanı", "Type" => "dropdown", "Options" =>
                $options, "Description" => "Belgesiz alan adı kaydında kullanılacak Vergi dairesi alanı", "Default" => "", ),
            "option3" => array ("FriendlyName" => "Vergi Numarası Alanı", "Type" => "dropdown", "Options" =>
                $options, "Description" => "Belgesiz alan adı kaydında kullanılacak Vergi no alanı", "Default" => "", ),
        ));
    return $configarray;
}
function tr_domain_activate()
{
    try {
        Capsule::schema()
            ->create(
                'mod_alastyrdomain',
                function ($table) {
                    $table->increments('id', 11);
                    $table->integer('domainid', false);
                    $table->integer('userid', false)->nullable();
                    $table->string('domainname')->nullable();
                    $table->string('ticketid', 64)->nullable();
                    $table->string('laststatus', 64)->nullable();
                    $table->integer('actiontype', false)->nullable();
                    $table->text('lastdescription')->nullable();
                    $table->dateTime('updated_at');
                    $table->dateTime('created_at');
                }
            );
        Capsule::schema()
            ->create(
                'mod_alastyrdomainlog',
                function ($table) {
                    $table->increments('id', 11);
                    $table->integer('domainid', false)->nullable();
                    $table->integer('userid', false)->nullable();
                    $table->string('ticketid', 64)->nullable();
                    $table->string('status', 64)->nullable();
                    $table->integer('actiontype', false)->nullable();
                    $table->string('actioncomment', 255)->nullable();
                    $table->text('description')->nullable();
                    $table->dateTime('created_at');
                }
            );
        Capsule::insert("INSERT INTO `tblemailtemplates` (`type`, `name`, `subject`, `message`, `attachments`, `fromname`, `fromemail`, `disabled`, `custom`, `language`, `copyto`, `blind_copy_to`, `plaintext`, `created_at`, `updated_at`) VALUES
        ('domain', 'TR Alan Adı Basvuru', 'Alan Adı Başvurunuz İçin Belge Bekleniyor', '<p>Sayın \{\$client_name\},</p>\r\n<p>Alan adı başvurunuzun ilk adımı tamamlandı . \{\$domain_tld\} uzantılı alan adlarının kaydının tamamlanması için bazı belgelere ihtiyaç duyulmaktadır.</p>\r\n<p>Müşteri panelinizde \{\$domains_manage_url\} linkinden alan adı detayları sayfasına ulaşarak gerekli belgeler hakkında bilgi alabilir ve belge yükleme işlemlerini gerçekleştirebilirsiniz.</p>\r\n<p>\{\$gereklibelge\}</p>\r\n<p>Not: Belge yükleme işleminin 4 gün içerisinde yapılması gerekmektedir, 4 gün içerisinde belge yükleme işlemi yapılmayan alan adları sistemden silinmektedir.</p>\r\n<p>Alan Adı: \{\$domain_name\}<br />Başvuru Tarihi: \{\$domain_reg_date\}<br />Kayıt Dönemi:  \{\$domain_reg_period\}<br /><br /></p>\r\n<p>Bizi tercih ettiğiniz için teşekkür ederiz!</p>\r\n<p>\{\$signature\}</p>', '', '', '', 0, 1, '', '', '', 0, '2020-02-19 10:14:44', '2020-02-19 10:14:44'),
('domain', 'TR Alan Adı Basvuru Onayi', 'Alan Adı Başvurunuz Onaylandı', '<p>Sayın \{\$client_name\},</p>\r\n<p>Tebrikler, alan adı başvurunuz onaylandı.</p>\r\n<p>Müşteri panelinizde \{\$domains_manage_url\} linkinden alan adınızın yönetim işlemlerini gerçekleştirebilirsiniz.</p>\r\n<p>Alan Adı: \{\$domain_name\}<br />Başvuru Tarihi: \{\$domain_reg_date\}<br />Kayıt Dönemi:  \{\$domain_reg_period\}<br /><br /></p>\r\n<p>Bizi tercih ettiğiniz için teşekkür ederiz!</p>\r\n<p>\{\$signature\}</p>', '', '', '', 0, 1, '', '', '', 0, '2020-01-31 15:25:07', '2020-01-31 15:25:07'),
('domain', 'TR Alan Adı Belge', 'TR Alan Adı Belgeleri Yüklendi', '<p>Sayın \{\$client_name\},</p>\r\n<p>\{\$domain_name} alan adınız için sisteme yüklediğiniz belgeler TR alan adı yönetimine iletildi..</p>\r\n<p>Müşteri panelinizde \{\$domains_manage_url\} linkinden yüklediğiniz belgeler ve alan adınız ile ilgili son durumu görüntüleyebilirsiniz.</p>\r\n<p>Not: TR alan adı yönetimi gönderdiğiniz belgeleri red edebilir, veya ek belgeler talep edebilir.</p>\r\n<p>Alan Adı: \{\$domain_name\}<br />Başvuru Tarihi: \{\$domain_reg_date\}<br />Kayıt Dönemi:  \{\$domain_reg_period\}<br /><br /></p>\r\n<p>Bizi tercih ettiğiniz için teşekkür ederiz!</p>\r\n<p>\{\$signature\}</p>', '', '', '', 0, 1, '', '', '', 0, '2020-02-20 07:26:49', '2020-02-20 07:26:49')
");

        return [
            'status' => 'success',
            'description' => 'Modul kurulumu gerçekleşmiştir. Lütfen "Eklentiler > Alastyr Domain" adımlarını takip ederek gerekli ayarları yapın. ',
        ];
    } catch (\Exception $e) {
        return [
            'status' => "error",
            'description' => 'Modul kurulumunda hata oluştu: ' . $e->getMessage(),
        ];
    }
}
function tr_domain_deactivate()
{
    try {
        Capsule::schema()
            ->dropIfExists('mod_alastyrdomain');
        Capsule::schema()
            ->dropIfExists('mod_alastyrdomainlog');
        return [
            'status' => 'success',
            'description' => 'Modül kaldırıldı.',
        ];
    } catch (\Exception $e) {
        return [
            "status" => "error",
            "description" => "Modul kaldırılırken bir hata oluştu: {$e->getMessage()}",
        ];
    }
}


function tr_domain_clientarea($vars) {
require_once ("modules/registrars/alastyr/alastyr.php");

    if($_REQUEST['uploaddomain']){

        $domainext = explode('.', $_REQUEST['uploaddomain'], 2);

        $uploaddata = $_REQUEST;
        $uploaddata['sld'] = $domainext[0];
        $uploaddata['tld'] = $domainext[1];

        
       $data =  alastyr_SendMultiDocument($uploaddata);
       if(!empty($data['error'])){
           $status = "error";
       } else {
           $status = "success";
        }

    }
    
    $clientid = $_SESSION['uid'];


    $command = 'GetClientsDomains';
    $postData = array(
        'clientid' => $clientid,
        'stats' => true,
    );

    $domainresults = localAPI($command, $postData);




    $domainlist = "";
    foreach($domainresults['domains']['domain'] as $domaindata){
        $domainarray[$domaindata['id']] = $domaindata['domainname'];
        if(($domaindata['registrar'] == 'alastyr') and ($domaindata['status'] == 'Pending Registration')){
            $domainstatus = Capsule::table('mod_alastyrdomain')->where('domainname', '=', $domaindata['domainname'])->first();

            if($domainstatus->laststatus == "0"){

        $domainlist .= '<option value="'.$domaindata['domainname'].'">'.$domaindata['domainname'].'</option>';
    
     

            }
        }

    }


    $firstitem = '<option value="Alan Adı Tahsis Formu">Alan Adı Tahsis Formu</option>';

    $table = '  <div id="formtable" class="inner-container"  style="display: none;"> <label for="selectdomain" style="text-align: center" class="col-sm-12">Belge Yüklenecek Domain</label>
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
    
    <script>
    $(\'#Primary_Sidebar-Domain_Details_Management-Belge_Gönder\').on("click", function (e) {e.preventDefault(); $(\'#upload-form\').modal(\'show\'); });
   
    $(\'#addform\').append("<form enctype=\"multipart/form-data\" name=\"file-upload-form\" id=\"file-upload-form\" method=\"post\" action=\"index.php?m=tr_domain&action=uploadfile\">" + $(\'#formtable\').html() + "<div id=\"fileuploads\"></div>");
     $(\'.add-file-upload\').click(function () {
        var moreId = $(this).data(\'more-id\');
        $(\'#\' + moreId).append("<div class=\"inner-container\"  style=\"display: block;\"><div class=\"row\"><div class=\"col-sm-5\"><select class=\"form-control\" name=\"doctype[]\" id=\"doctype\"><option value=\"\">Seçiniz</option><option value=\"Alan Adı Tahsis Formu\">Alan Adı Tahsis Formu</option><option value=\"Marka tescil belgesi\">Marka tescil belgesi</option><option value=\"Ticaret Sicili gazetesi\">Ticaret Sicili gazetesi</option><option value=\"Ticari Sicil Tasdiknamesi\">Ticari Sicil Tasdiknamesi</option><option value=\"Faaliyet belgesi\">Faaliyet belgesi</option><option value=\"Oda sicil kayit sureti\">Oda sicil kayit sureti</option><option value=\"Marka basvurusu belgesi\">Marka basvurusu belgesi</option><option value=\"Sicil tasdiknamesi (ESO)\">Sicil tasdiknamesi (ESO)</option><option value=\"Mesleki faaliyet belgesi (ESO)\">Mesleki faaliyet belgesi (ESO)</option><option value=\"Mesleki faaliyet belgesi (Odalar)<\">Mesleki faaliyet belgesi (Odalar)</option><option value=\"TBB den onay yazisi\">TBB den onay yazisi</option><option value=\"Nufus cuzdani fotokopisi\">Nufus cuzdani fotokopisi</option><option value=\"T.C kimlik nosu ciktisi\">T.C kimlik nosu ciktisi</option><option value=\"TTB den onay yazisi<\">TTB den onay yazisi</option><option value=\"Distribütörlük yetki yazisi<\">Distribütörlük yetki yazisi</option><option value=\"Turizm isletme belgesi\">Turizm isletme belgesi</option><option value=\"Seyahat acentesi isletme belgesi\">Seyahat acentesi isletme belgesi</option><option value=\"Ozel Ogretim Kurumlari Ruhsatnamesi\">Ozel Ogretim Kurumlari Ruhsatnamesi</option><option value=\"Özel Hastane Ruhsati\">Özel Hastane Ruhsati</option><option value=\"Film adlari için izin belgesi\">Film adlari için izin belgesi</option><option value=\"Sanatçi odalarindan üye kayit belgesi\">Sanatçi odalarindan üye kayit belgesi</option><option value=\"Televizyon Gösterim Belgesi\">Televizyon Gösterim Belgesi</option><option value=\"RTÜK frekans tahsis bildirim yazısi\">RTÜK frekans tahsis bildirim yazısi</option><option value=\"Mevkute Beyannamesi\">Mevkute Beyannamesi</option><option value=\"Tıbbi Müs. Belgesi\">Tıbbi Müs. Belgesi</option><option value=\"İrtibat Bürosu Açilim Belgesi\">İrtibat Bürosu Açilim Belgesi</option><option value=\"T.C.Gemi Tasdiknamesi\">T.C.Gemi Tasdiknamesi</option><option value=\"Fuar Izni Yazısı\">Fuar Izni Yazısı</option><option value=\"Fuar Takvimi Tebliği\">Fuar Takvimi Tebliği</option><option value=\"Özel Servis Numarasi Tahsis Belgesi\">Özel Servis Numarasi Tahsis Belgesi</option><option value=\"TID onay yazisi\">TID onay yazisi</option><option value=\"ISS Genel Izin Belgesi\">ISS Genel Izin Belgesi</option><option value=\"Vakif senedi\">Vakif senedi</option><option value=\"Dernek tuzugu\">Dernek tuzugu</option><option value=\"Sivil toplum orgutlerinden destek yazisi\">Sivil toplum orgutlerinden destek yazisi</option><option value=\"Ziraat odasi kayit belgesi\">Ziraat odasi kayit belgesi</option><option value=\"Kurulus kanunu\">Kurulus kanunu</option><option value=\"Kitabın aslı\">Kitabın aslı</option><option value=\"Gazetenin aslı\">Gazetenin aslı</option><option value=\"Derginin aslı\">Derginin aslı</option><option value=\"Diger (ilk kez iletilecek olanlar)\">Diger (ilk kez iletilecek olanlar)</option></select></div><div class=\"col-sm-5\"><input type=\"file\" name=\"attachments[]\" class=\"form-control\"></div><div class=\"col-sm-2\"><a href=\"#\" id=\"add-file-upload\" class=\"btn btn-success btn-block add-file-upload\" data-more-id=\"fileuploads\"><i class=\"fas fa-plus\"></i> Daha </a></div></div></div>");
        return false;
    });
</script>
    ';

    if($_REQUEST['a'] == 'ApplicationForm'){


        $domainext = explode('.', $domainarray[$_REQUEST['id']], 2);

        $params['original']['sld'] = $domainext[0];
        $params['original']['tld'] = $domainext[1];

        //$output = serialize($params);
       alastyr_ApplicationForm($params);

    }

    return array(
        'breadcrumb' => array('index.php?m=tr_domain'=>'TR Domain Addon'),
        'templatefile' => 'fileupload',
        'requirelogin' => true, # accepts true/false
        'forcessl' => true, # accepts true/false
        'vars' => array(
            'output' => $output,
            'error' => $data['error'],
            'status' => $status,
        ),
    );
}
