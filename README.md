
Lütfen öncelikle https://www.alastyr.com/secure/ adresinden üye olup, bayi olmak istediğinizi belirten bir destek talebi açınız.

###Kurulum Adımları

- Tüm dosyaları WHMCS ana dizinine yükleyiniz.
- WHMCS Admin Panelinde Kurulum > Ürün ve Hizmetler > Domain Kayıt Operatörleri adımından Alastyr Registrar modulunu aktif edip, size verilen Api Key Ve Api Secret bilgilerini ilgili alanlara giriniz.
- Kurulum > İlave Moduller adımından  Alastyr Domain modülümü aktif ediniz ve TC Kimlik No, Vergi Dairesi ve Vergi No için kullanmak istediğiniz CustomFiels alanlarını seçip kaydediniz.
- WHMCS dizinizinde bulunan crons > tr_domain_sync.php dosyası için bir cronjob oluşturunuz. Nic.tr yönetiçi standart mesai düzeninde çalıştığı için hafta içi 09:00 18:00 arasında saatlik çalışması yeterlidir.


Modul aracılığı ile tüm alan adı kayıt işlemleri ve, belge yükleme işlemleri sizin veya müşteriniz tarafından online olarak yapılabilmektedir.