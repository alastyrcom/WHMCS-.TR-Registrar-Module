<?php

add_hook('ClientAreaHeadOutput', 1, function($vars) {

    if ($vars['action'] == "confdomains"){

        foreach ($vars['domains'] as $domainkey => $domain){

            $ext = explode(".", $domain['domain'], 2);

            if($ext[1] == "com.tr"){
                $domainf[] = $domainkey;
            }
        }

        $html =  '<script type="text/javascript">$(document).ready(function(){';

foreach($domainf as $fieldid){
    $html .= '

         
    $("select[name*=\'domainfield['.$fieldid.'][0]\']").change(function () {
    var vas = $("select[name*=\'domainfield['.$fieldid.'][0]\']").val();
    if(vas == "Kurum Adına"){
    $(".personal").hide();
    $(".organization").show();
    }
    else {
    $(".personal").show();
    $(".organization").hide();
    }
   
    });
    
    ';
}
 $html .=  '});</script>';

return $html;

    }



});