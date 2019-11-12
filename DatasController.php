<?php

class DatasController extends V1Controller {


  public function index() {

    $datas = [];
    $this->datas = Data::load()->select("id", "barcode", "name")->limit(10000)->get_all();
    if ($this->datas) {
      foreach ($this->datas as $data) {
        $datas[] = $this->_data_object2array_template($data);
      }
    }

    $json = self::_query_json_template(200, "Barcode hepsi bu kadar!", $datas);
    $this->render(["text" => $json], ["content_type" => "application/json"]);
  }

  public function repeater() {
    $datas = Data::load()->group("barcode")->count();

    $_datas = [];
    foreach ($datas as $data) {
      $count = $data["count(*)"];
      if ($count > 1)
        $_datas[] = $data;
    }

    $json = self::_query_json_template(200, "Barcode bulundu!", $_datas, count($_datas));
    $this->render(["text" => $json], ["content_type" => "application/json"]);
  }

  public function savecheck() {

    if (!isset($_POST["barcode"]) || !isset($_POST["device"])) {
      $json = self::_query_json_template(429, "Verilerde eksiklik var!");
      return $this->render(["text" => $json], ["content_type" => "application/json"]);
    }

    $data = Datadraft::unique(["barcode" => $_POST["barcode"], "name" => $_POST["name"]]);
    if (!$data) {
      $json = self::_query_json_template(200, "Bu device ürün ismi kaydedebilir", [], "");
      return $this->render(["text" => $json], ["content_type" => "application/json"]);
    } else {
      $json = self::_query_json_template(404, "Bu device ürün ismi daha önceden kaydetmiştir.");
      return $this->render(["text" => $json], ["content_type" => "application/json"]);
    }
  }

  public function save() {

    if (!isset($_POST["barcode"]) || !isset($_POST["name"]) || !isset($_POST["device"])) {
      $json = self::_query_json_template(429, "Verilerde eksiklik var!");
      return $this->render(["text" => $json], ["content_type" => "application/json"]);
    }

    $post_barcode = $_POST["barcode"];
    $post_name = $_POST["name"];
    $post_device = $_POST["device"];

    if (Datadraft::unique(["barcode" => $post_barcode, "device" => $post_device])) {
      $json = self::_query_json_template(404, "Bu kullanıcı daha önceden barcode öneride bulunmuş");
    }

    Datadraft::create(["barcode" => $post_barcode, "name" => $post_name, "device" => $post_device, "created_at" => date("Y-m-d H:i:s")]);

    $datas = Datadraft::load()->where("barcode", $post_barcode)->get_all();

    if (count($datas) >= 100) {

      $datas1 = $datas;
      $datas2 = $datas;

      $similar_strings = [];

      foreach ($datas1 as $data1) {
        $percent = 0;
        foreach ($datas2 as $data2) {
          similar_text($data1->name, $data2->name, $data_percent);
          $percent += $data_percent;
        }
        $similar_strings["$percent"] = TurkishHelper::strtoupper($data1->name);
      }

      $max_key = max(array_keys($similar_strings));
      $data_name = $similar_strings[$max_key];

      /* remove dataerror */
      $dataerror = Dataerror::unique(["barcode" => $post_barcode]);
      $dataerror->destroy();

      /* remove datadrafts */
      Datadraft::load()->where("barcode", $post_barcode)->delete_all();

      /* create data */
      $data = Data::create(["barcode" => $post_barcode, "name" => TurkishHelper::strtoupper($name), "created_at" => date("Y-m-d H:i:s")]);

      $_data = $this->_data_object2array_template($data);
      $json = self::_query_json_template(200, "Barcode kalıcı olarak kaydedildi", [$_data]);

      $this->render(["text" => $json], ["content_type" => "application/json"]);
    } else {
      $json = self::_query_json_template(201, "Barcode isim kaydına katkıda bulunuldu", $datas);
      $this->render(["text" => $json], ["content_type" => "application/json"]);
    }
  }

  public function show() {

    if (!$data = Data::unique(["barcode" => $this->barcode])) {
      $json = self::_query_json_template(404, "Barcode bulunamadı!");
    } else {
      $_data = $this->_data_object2array_template($data);
      $json = self::_query_json_template(200, "Barcode bulundu!", [$_data]);
    }

    $this->render(["text" => $json], ["content_type" => "application/json"]);
  }

  public function pages() {
    $pages = [];

    $page1 = [
    "title" => "Kullanım Şartları",
    "content" => [
    [
    "subtitle" => "UYGULUMA MÜLKİYETİ; KULLANIM ŞEKLİ ANLAŞMASI",
    "subcontent" => "EnnAra aracılığıyla sunulan hizmetlerin fikri ve sınai mülkiyet hakları EnnAra'ya aittir. EnnAra'nın içeriğini ve altyapısını oluşturan bölümlerinin, kaynak kodlarının, metinlerinin ve görsellerinin tümü ya da bir kısmı EnnAra izni olmadan basılı ya da internet ortamında kullanılamaz, çoğaltılamaz. EnnAra'ya ait ya da EnnAra tarafından sağlanan tüm kurumsal logolar, görseller, fotoğraflar yazılı izin alınmadan basılı ya da internet ortamında kullanılamaz, çoğaltılamaz. Kullanıcılar tarafından, bu yönde bir olanak sağlanması halinde, EnnAra'ya gönderilen, iletilen, kullanılan, oluşturulan ya da EnnAra aracılığıyla 3. şahıslara iletilen her türlü kullanıcı içeriğinin gizlilik niteliğinin ortadan kalktığı ve herhangi bir fikri ve sınai hakkı/telif/lisans hakkını içermediği kabul edilir. Kullanıcılar gizli ya da üzerinde fikri ve sınai mülkiyet hakkı olduğu düşünülen herhangi bir içerik oluşturup hesabına eklediğinde bu içeriğin 'hukuki bir ayıp' içermediği ve bunları dijital iletim suretiyle yayınlama hakkı olduğu kabul edilir. Aksi halde tüm sorumluluk yasal ihlal oluşturan içeriği gönderen kullanıcıya aittir. EnnAra tarafından sunulan dosyalar, yazılmış özgün içerik, görseller, açıklamalar ve benzeri ögeler ile tüm lisanslı tablo, lisanslı analiz ve lisanslı grafikler, kişisel bilgiler ve kurumsal şirket hakları nedeniyle EnnAra'ın yazılı izni olmadan hiçbir ortamda yayınlanamaz, kullanılamaz, çoğaltılamaz. Kurumlar ile yapılan kiralama anlaşmaları gereği EnnAra 'ın hak sahipliği ve yukarıda tanımlanan hakların ihlalinin tespiti sonucunda EnnAra, yasal işlem başlatma hakkına sahiptir. Kullanıcıların EnnAra'ya dahil olmasıyla bu telif hakkı bildirisini kabul ettiğini taahhüt eder."
    ],
    [
    "subtitle" => "İÇERİK",
    "subcontent" => "Tasarım, yapı, seçim, koordinasyon, ifade, sınırlama dahil olmak ancak bunlarla sınırlı olmaksızın tüm metin, grafik, kullanıcı arabirimleri, görsel arayüzler, fotoğraflar, ticari markalar, logolar, sesler, müzik, resim ve bilgisayar kodu (topluca 'İçerik' Uygulamada yer alan, İçerikte yer alan, kontrol edilen veya lisanslanan ve EnnAra tarafından lisanslanan ve ticaret elbisesi, telif hakkı, patent ve ticari marka yasaları ile çeşitli fikri mülkiyet hakları ve haksız rekabet yasaları ile korunan, 'görünüş ve his' ve düzenlenmesi. Bu Kullanım Koşullarında açıkça belirtilmediği sürece, Uygulamanın hiçbir kısmı ve İçeriği, herhangi bir şekilde ('yansıtma' da dahil olmak üzere) kopyalanamaz, çoğaltılamaz, yeniden yayınlanamaz, yüklenemez, yayınlanır, kamuya açık şekilde görüntülenemez, kodlanmaz, tercüme edilemez, iletilemez veya dağıtılamaz. EnnAra ürününü ve hizmetlerini (veri sayfaları, bilgi tabanı makaleleri ve benzeri materyaller gibi), Uygulamadan indirilmek üzere EnnAra tarafından kullanılabilir hale getirmek için kullanabilirsiniz; ancak şu koşullar sağlanır: (1) tüm kopyalarında herhangi bir tescilli bildirim dilini kaldırmazsınız (2) bu tür bilgileri yalnızca kişisel, ticari olmayan bilgi amaçlı kullanın ve bu tür bilgileri ağa bağlı bir bilgisayara kopyalamayın veya yayınlamayın veya herhangi bir ortamda yayınlamayın, (3) bu tür herhangi bir bilgide herhangi bir değişiklik yapmayın ve (4) bu tür belgelerle ilgili ek beyan veya garanti vermeyin."
      ],
      [
      "subtitle" => "ÇEREZLER",
      "subcontent" => "Çerezler, EnnAra'nın sağladığı işlevlerin sağlıklı çalışmasını sağlayan, kullanıcının bilgisayarına tarayıcı aracılığıyla yerleştirilen küçük bir dosyadır. EnnAra çerezleri cihazları tanınması, güvenli bir şekilde erişim sağlanması, güvenlik kontrolleri gibi teknik alanlar ya da kullancının kullanımına göre özelleştirilmiş seçenekler sunmak dışında kullanılmaz. Çerezlere kişisel verileriniz asla yerleştirilmez."
      ],
      ]
      ];
      $page2 = [
      "title" => "Gizlilik Politikası",
      "content" => [
      [
      "subtitle" => "TELİF HAKLARI",
      "subcontent" => "EnnAra aracılığıyla sunulan hizmetlerin fikri ve sınai mülkiyet hakları EnnAra'ya aittir. EnnAra'nın içeriğini ve altyapısını oluşturan bölümlerinin, kaynak kodlarının, metinlerinin ve görsellerinin tümü ya da bir kısmı EnnAra izni olmadan basılı ya da internet ortamında kullanılamaz, çoğaltılamaz. EnnAra'ya ait ya da EnnAra tarafından sağlanan tüm kurumsal logolar, görseller, fotoğraflar yazılı izin alınmadan basılı ya da internet ortamında kullanılamaz, çoğaltılamaz. Kullanıcılar tarafından, bu yönde bir olanak sağlanması halinde, EnnAra'ya gönderilen, iletilen, kullanılan, oluşturulan ya da EnnAra aracılığıyla 3. şahıslara iletilen her türlü kullanıcı içeriğinin gizlilik niteliğinin ortadan kalktığı ve herhangi bir fikri ve sınai hakkı/telif/lisans hakkını içermediği kabul edilir. Kullanıcılar gizli ya da üzerinde fikri ve sınai mülkiyet hakkı olduğu düşünülen herhangi bir içerik oluşturup hesabına eklediğinde bu içeriğin 'hukuki bir ayıp' içermediği ve bunları dijital iletim suretiyle yayınlama hakkı olduğu kabul edilir. Aksi halde tüm sorumluluk yasal ihlal oluşturan içeriği gönderen kullanıcıya aittir. EnnAra tarafından sunulan dosyalar, yazılmış özgün içerik, görseller, açıklamalar ve benzeri ögeler ile tüm lisanslı tablo, lisanslı analiz ve lisanslı grafikler, kişisel bilgiler ve kurumsal şirket hakları nedeniyle EnnAra'nın yazılı izni olmadan hiçbir ortamda yayınlanamaz, kullanılamaz, çoğaltılamaz. Kurumlar ile yapılan kiralama anlaşmaları gereği EnnAra'nın hak sahipliği ve yukarıda tanımlanan hakların ihlalinin tespiti sonucunda EnnAra, yasal işlem başlatma hakkına sahiptir. Kullanıcıların EnnAra'ya dahil olmasıyla bu telif hakkı bildirisini kabul ettiğini taahhüt eder."
      ],
      [
      "subtitle" => "GİZLİLİK",
      "subcontent" => "EnnAra'da yer alan barkodlar sadece bilgilendirme amaçlıdır. Fiyat geçerlilik 'Türkiye Geneli' ise genel olarak satıldığı ortalama rakam fiyat olarak bulmaktadır. Uygulamada yer alan fiyatların ve fiyat geçerlilik yerinin bir kesinliği bulunmamakla beraber ürün-fiyat eşleşmesi de hatalı olabilir. Bu bilgilerin kullanımından doğacak sorumluluk kullanan kişiye aittir, EnnAra herhangi bir sorumluluk kabul etmez. Kullanıcı bu uygulamayı kullanarak bu şartları kabul etmiş sayılır. EnnAra bireylerin mahremiyetine saygı duyar ve kişilere ait toplanan verileri yasal zorunluluklar hali dışında üçüncü şahıslarla asla paylaşmaz; şahsi bilgilerinizin güvenliği için tüm tedbirlerin alındığını taahhüt eder. Kişisel verileriniz reklam, kampanya, veri madenciliği ve benzer maksatlarla satılması söz konusu değildir. Kurumların bilgi, sonuç, değerlendirme ve benzeri verileri EnnAra tarafından kurumlara sunulan analizlerin dışında üçüncü kişilerle paylaşılmaz. Bu veriler geçmişe dönük olarak EnnAra tarafından saklanabilir ve kurum için yapılacak uzun vadeli analizlerde tekrar kullanılabilir. EnnAra ilgili verilerin güvenliği için tüm tedbirlerin alındığını taahhüt eder."
      ],
      [
      "subtitle" => "ÇEREZLER",
      "subcontent" => "Çerezler, EnnAra'nın sağladığı işlevlerin sağlıklı çalışmasını sağlayan, kullanıcının bilgisayarına tarayıcı aracılığıyla yerleştirilen küçük bir dosyadır. EnnAra çerezleri cihazları tanınması, güvenli bir şekilde erişim sağlanması, güvenlik kontrolleri gibi teknik alanlar ya da kullancının kullanımına göre özelleştirilmiş seçenekler sunmak dışında kullanılmaz. Çerezlere kişisel verileriniz asla yerleştirilmez."
      ],
      ]
      ];
      $pages[] = $page1;
      $pages[] = $page2;

      $json = self::_query_json_template(200, "Sayfa bilgileri getirildi", $pages);
      return $this->render(["text" => $json], ["content_type" => "application/json"]);
    }

    public function tops() {

      $tops = [];
      $datatops = Datatop::load()->order("count", "desc")->limit(3)->get_all();

      if ($datatops) {
        if (ApplicationCache::exists("__datatops__")) {
          $tops = ApplicationCache::read("__datatops__");
        } else {

          foreach ($datatops as $datatop) {
            // $data = Data::find($datatop->data_id);
            $barcode_datas = Data::load()->where("barcode", $datatop->barcode)->get_all();

            $datas = [];
            foreach ($barcode_datas as $data) {
              $_datas = self::_query_all($data, $data->barcode);
              $datas = array_merge($datas, $_datas);
            }

            // remove duplicate names
            $datas = array_unique($datas, SORT_REGULAR);

            $tops = array_merge($tops, $datas);
          }

          ApplicationCache::write("__datatops__", $tops);
        }
      }

      $json = self::_query_json_template(200, "Top 3 ürün ile ilgili veriler", $tops);
      return $this->render(["text" => $json], ["content_type" => "application/json"]);
    }

    public function search_name() {

      if (!isset($_POST["name"]) || !isset($_POST["barcode"]) || !isset($_POST["from"])) {
        $json = self::_query_json_template(429, "Verilerde eksiklik var!");
        return $this->render(["text" => $json], ["content_type" => "application/json"]);
      }

      if ($datauser = Datauser::unique(["device" => $_POST["device"]])) {
        if ($datauser->credit > 0) {

          $data = Data::draft(["name" => $_POST["name"]]);

          $cachename = $_POST["barcode"] . $_POST["from"] . $_POST["name"];
          $datas = self::_query_all($data, $cachename);

          if ($datas) {
            $datauser->credit = $datauser->credit - 1;
            $datauser->save();
            $json = self::_query_json_template(200, "Başarılı istek", $datas, $data->name);
            return $this->render(["text" => $json], ["content_type" => "application/json"]);
          } else {
            $json = self::_query_json_template(404, "Üzgünüm aradığım kaynaklarımda ürününüzü bulamadım.");
            return $this->render(["text" => $json], ["content_type" => "application/json"]);
          }

        } else if ($datauser->credit <= 0) {

          $datauser_object2array = [
          "id" => $datauser->id,
          "device" => $datauser->device,
          "credit" => $datauser->credit
          ];

          $json = self::_query_json_template(500, "Üzgünüm kullanıcının kredisi bitmiş.", [$datauser_object2array], $datauser->device);
          return $this->render(["text" => $json], ["content_type" => "application/json"]);
        }

      } else {
        $json = self::_query_json_template(404, "Böyle bir cihaz yok!");
        return $this->render(["text" => $json], ["content_type" => "application/json"]);
      }

    }

    public function search_barcode() {

      if (!isset($_POST["barcode"])) {
        $json = self::_query_json_template(429, "Verilerde eksiklik var!");
        return $this->render(["text" => $json], ["content_type" => "application/json"]);
      }

      $post_barcode = $_POST["barcode"];

      if (!$barcode_datas = Data::load()->where("barcode", $post_barcode)->get_all()) {

        /* search to sinamega.com */
        if ($name = self::_query_sm($post_barcode)) {
          Data::create(["barcode" => $post_barcode, "name" => TurkishHelper::strtoupper($name), "created_at" => date("Y-m-d H:i:s")]);
          $barcode_datas = Data::load()->where("barcode", $post_barcode)->get_all();
          /* search to dr.com */
          /*
          } else if ($name = self::_query_dr($post_barcode)) {
            Data::create(["barcode" => $post_barcode, "name" => TurkishHelper::strtoupper($name), "created_at" => date("Y-m-d H:i:s")]);
            $barcode_datas = Data::load()->where("barcode", $post_barcode)->get_all();
          } */
        } else {

          // Dataerror kaldırılacak
          /* start dataerror */
          if ($dataerror = Dataerror::unique(["barcode" => $post_barcode])) {
            $dataerror->count = $dataerror->count + 1;
            $dataerror->updated_at = date("Y-m-d H:i:s");
            $dataerror->save();
          } else  {
            Dataerror::create(["barcode" => $post_barcode, "count" => 1, "created_at" => date("Y-m-d H:i:s")]);
          }
          /* end dataerror */

          $json = self::_query_json_template(404, "Wow! Uzay çağında henüz bilmediğimiz bir barcode kullanıyorsunuz!");
          return $this->render(["text" => $json], ["content_type" => "application/json"]);
        }

      }

      $datas = [];
      foreach ($barcode_datas as $data) {
        $_datas = self::_query_all($data, $data->barcode);
        $datas = array_merge($datas, $_datas);
      }

      // remove duplicate names
      $datas = array_unique($datas, SORT_REGULAR);

      /* start datatop */
      if ($datatop = Datatop::unique(["barcode" => $post_barcode])) {
        $datatop->count = $datatop->count + 1;
        $datatop->updated_at = date("Y-m-d H:i:s");
        $datatop->save();
      } else  {
        Datatop::create(["barcode" => $post_barcode, "count" => 1, "created_at" => date("Y-m-d H:i:s")]);
      }
      /* end datatop */

      if (!empty($datas)) {
        $json = self::_query_json_template(200, "Başarılı istek", $datas, $data->name);
        return $this->render(["text" => $json], ["content_type" => "application/json"]);
      } else {
        $json = self::_query_json_template(404, "Üzgünüm aradığım kaynaklarımda ürününüzü bulamadım.");
        return $this->render(["text" => $json], ["content_type" => "application/json"]);
      }

    }

    private static function _query_all($data, $cachename) {
      $name = TurkishHelper::strtolower($data->name);
      $queryname = urlencode($name);
      $querybarcode = $data->barcode;

      if (ApplicationCache::exists("$cachename")) {
        $datas = ApplicationCache::read("$cachename");
      } else {
        $datas = [];

        // MIGROS ///////////////////////////////////////////////////////

        // barcode
        $names = self::_query_migros($querybarcode, $name);
        // name
        if (!$names) $names = self::_query_migros($queryname, $name);
        // finish
        if ($names) $datas = array_merge($datas, $names);

        // CAREFOURSA ///////////////////////////////////////////////////

        // barcode
        $names = self::_query_carrefoursa($querybarcode, $name);
        // name
        if (!$names)
        	$names = self::_query_carrefoursa($queryname, $name);
        // finish
        if ($names) $datas = array_merge($datas, $names);

        // A101 //////////////////////////////////////////////////////////

        // barcode
        $names = self::_query_a101($querybarcode, $name);
        // name
        if (!$names) $names = self::_query_a101($queryname, $name);
        // finish
        if ($names) $datas = array_merge($datas, $names);

        ApplicationCache::write("$cachename", $datas);
      }
      return $datas;
    }



    private static function _query_dr($queryname) {
      $file = file_get_contents("https://www.dr.com.tr/Search?q=" . $queryname);

      preg_match_all("'<figure>\s* <a href=\"(.*?)\" class=\"item-name\">\s*<img src=\"(.*?)\" alt=\"(.*?)\"/>\s*</a>\s*</figure>'si", $file, $images);
      preg_match_all("'<a href=\"(.*?)\" class=\"item-name\">\s*<h3>(.*?)</h3>\s*</a>'si", $file, $names);
      preg_match_all("'<span class=\"price\">(.*?)</span>'si", $file, $prices);

      $_images = $images[2];
      $_names = $names[2];
      $_prices = $prices[1];

      if (isset($_names[0])) {
        $_image = $_images[0];

        $_name = $_names[0];
        // remove TL crachter
        $_price = preg_replace("/[^0-9,.|]/", "", $_prices[0]);

        $data = [
        "name" => $_name,
        "price" => $_price,
        "image" => $_image,
        "from" => "DR"
        ];
        $data = $_name;
      } else {
        $data = NULL;
      }

      // $_name = mb_convert_encoding($_names[0],  'ISO-8859-1', 'UTF-8');
      //  $_name = utf8_decode($_name);
      // $_name = mb_convert_encoding($_name, "UTF-8", "ISO-8859-1");

      // $_name = mb_convert_encoding($_name, 'UTF-8', mb_detect_encoding($_name, 'UTF-8, ISO-8859-1', true));
      // $_name = iconv('ASCII', 'UTF-8//IGNORE', $_name);
      // $_name = html_entity_decode($_name);

      // $_name = html_entity_decode($_name, ENT_COMPAT, $encoding = 'UTF-8');
      // $_name = html_entity_decode($_name, ENT_QUOTES | ENT_HTML5);
      // $_name = html_entity_decode($_name, ENT_COMPAT, 'ISO-8859-1');

      // $_name = htmlspecialchars_decode($_name, ENT_QUOTES);


      return $data;

    }


    private static function _query_migros($queryname, $dataname) {
      $file = file_get_contents("https://www.migros.com.tr/arama?q=" . $queryname);

      preg_match_all("'<img class=\"product-card-image lozad\" src=\"(.*?)\" data-src=\"(.*?)\"'si", $file, $images);

      // preg_match_all('@data-monitor-name="([^"]+)"@' , $file, $names);
      preg_match_all('@data-product-name="([^"]+)"@' , $file, $names);

      preg_match_all("'<div class=\"price-tag\"><span class=\"value\">(.*?)</span></div>'si", $file, $prices);
      // preg_match_all('@data-monitor-price="([^"]+)"@' , $file, $prices);

      $_images = $images[2];
      $_names = $names[1];
      $_prices = $prices[1];

      foreach ($_names as $index => $name) {
        $_names[$index] = htmlspecialchars_decode($name, ENT_QUOTES);
      }

      // remove TL crachter
      $_prices = preg_replace("/[^0-9,.|]/", "", implode("|", $_prices));
      $_prices = explode("|", $_prices);

      return ($_names) ? self::_data_similar_get($_names, $_prices, $_images, $dataname, "migros") : null;

    }

    private static function _query_carrefoursa($queryname, $dataname) {
      $file = file_get_contents("https://www.carrefoursa.com/tr/search/?text=" . $queryname);

      preg_match_all("'<span class=\"thumb\">\s*<img src=\"(.*?)\"'si", $file, $images);
      preg_match_all("'<span class=\"item-name\">(.*?)</span>'si", $file, $names);
      preg_match_all("'<span class=\"item-price\">(.*?)</span>'si", $file, $prices);

      $_images = $images[1];
      $_names = $names[1];
      $_prices = $prices[1];

      if ($_names) {
        if ($_names[0] == "{{= p.title }}") {
          $_names = [];
        }
      }

      foreach ($_names as $index => $name) {
        $_names[$index] = htmlspecialchars_decode($name, ENT_QUOTES);
      }

    // remove TL crachter
      $_prices = preg_replace("/[^0-9,.|]/", "", implode("|", $_prices));
      $_prices = explode("|", $_prices);

      return ($_names) ? self::_data_similar_get($_names, $_prices, $_images, $dataname, "carrefoursa") : null;
    }


    private static function _query_a101($queryname, $dataname) {

      $file = file_get_contents("https://www.a101.com.tr/list/?search_text=" . $queryname);

      preg_match_all("'<div class=\"product-image(?: |  passive)\">\s*<img src=\"(.*?)\"'si", $file, $images);
      preg_match_all("'<div class=\"name\">(.*?)</div>'si", $file, $names);
      preg_match_all("'<span class=\"current\">(.*?)</span>'si", $file, $prices);

      $_images = $images[1];
      $_names = $names[1];
      $_prices = $prices[1];

      foreach ($_names as $index => $name) {
        $_names[$index] = htmlspecialchars_decode(trim($name), ENT_QUOTES);
      }

      array_shift($_prices);

      // remove TL crachter
      $_prices = preg_replace("/[^0-9,.|]/", "", implode("|", $_prices));
      $_prices = explode("|", $_prices);

      array_shift($_names);

      return ($_names) ? self::_data_similar_get($_names, $_prices, $_images, $dataname, "a101") : null;
    }

    private static function _query_sm($queryname) {

      $file = file_get_contents("https://www.sinamega.com/ara/?f_keyword=" . $queryname);

      $file = mb_convert_encoding($file, 'UTF-8', mb_detect_encoding($file, 'UTF-8, ISO-8859-9', true));

    // preg_match_all("'<div class=\"urun-ismi\">(.*?)</div>'si", $file, $names);
    // preg_match_all("/<div class=\"urun-ismi\">\s*(.*?)\s*<\/div>/siu", $file, $names);

      preg_match_all('/<div class="urun-ismi">\s*(.*?)\s*<\/div>/si', $file, $names);

      $_names = $names[1];

/*

    foreach ($_names as $key => $value) {
      $_names[$key] = trim($value);
    }
*/
    return (!empty($_names)) ? $_names[0] : NULL;
  }

  private static function _data_similar_get($names, $prices, $images, $dataname, $from) {
    $datas = [];
    foreach ($names as $index => $name) {
      preg_match_all('!\d+!', $name, $name_numbers);
      preg_match_all('!\d+!', $dataname, $dataname_numbers);

      $percent_number = self::similar_number($name_numbers, $dataname_numbers);
      similar_text($dataname, TurkishHelper::strtolower($name), $percent_normal);
      $percent_string = self::similar_string(TurkishHelper::strtolower($dataname), TurkishHelper::strtolower($name));

      $percent = ($percent_normal + $percent_string + $percent_number) / 3;
      // $datas["$percent"][] = [
      $datas["$percent_normal"][] = [
      "name" => $name,
      "price" => $prices[$index],
      "image" => $images[$index],
      "percent" => $percent,
      "percent_normal" => $percent_normal,
      "percent_string" => $percent_string,
      "percent_number" => $percent_number,
      "from" => $from
      ];

    }

    $max_key = max(array_keys($datas));
    return $datas[$max_key];
  }

  private static function similar_word($word1, $word2) {
    $check_list = [];
    $chars1 = TurkishHelper::str_split($word1);
    $chars2 = TurkishHelper::str_split($word2);

    $count_array1 = count($chars1);
    $count_array2 = count($chars2);
    if ($count_array1 >= $count_array2) {
      $max_array = $chars1;
      $min_array = $chars2;
    } else {
      $max_array = $chars2;
      $min_array = $chars1;
    }

    foreach ($max_array as $index => $value)  $check_list[$index] = NULL;

    foreach ($min_array as $index => $value) {
      foreach ($max_array as $k => $v) {
        if ($value == $v & $check_list[$k] == NULL) {
          $check_list[$k] = TRUE;
          break;
        }
      }
    }

    $ok = 0; $no = 0;
    foreach ($check_list as $key => $value) {
      if ($value)
        $ok++;
      else
        $no++;
    }
    $all = $ok + $no;
    $percent = $ok * 100 / $all;

    switch (count($max_array)) {
      case 1:  $wall=100; break;
      case 2:  $wall=50;  break;
      default: $wall=65;  break;

    }
    return ($percent >= $wall) ? TRUE : NULL;
  }

  private static function similar_string($string1, $string2) {
    $check_list = [];
    $words1 = preg_split("/\s+/", $string1);
    $words2 = preg_split("/\s+/", $string2);

    if (count($words1) >= count($words2)) {
      $max_array = $words1;
      $min_array = $words2;
    } else {
      $max_array = $words2;
      $min_array = $words1;
    }

    foreach ($max_array as $index => $value)  $check_list[$index] = NULL;

    foreach ($min_array as $index => $value) {
      foreach ($max_array as $k => $v) {
        if ($value == $v & $check_list[$k] == NULL) {
          $check_list[$k] = TRUE;
          break;
        } else {
          $state = self::similar_word($value, $v);
          if ($state & $check_list[$k] == NULL) {
            $check_list[$k] = TRUE;
            break;
          }
        }

      }
    }

    $ok = 0; $no = 0;
    foreach ($check_list as $key => $value) {
      if ($value)
        $ok++;
      else
        $no++;
    }
    $all = $ok + $no;
    $percent = $ok * 100 / $all;
    return $percent;
  }

  private static function similar_number($array1, $array2) {
    $check_list = [];
    $count_array1 = count($array1);
    $count_array2 = count($array2);
    if ($count_array1 >= $count_array2) {
      $max_array = $array1;
      $min_array = $array2;
    } else {
      $max_array = $array2;
      $min_array = $array1;
    }

    foreach ($max_array as $index => $value)  $check_list[$index] = NULL;

    foreach ($min_array as $index => $value) {
      foreach ($max_array as $k => $v) {
        if ($value == $v & $check_list[$k] == NULL) {
          $check_list[$k] = TRUE;
          break;
        }
      }
    }

    $ok = 0; $no = 0;
    foreach ($check_list as $key => $value) {
      if ($value)
        $ok++;
      else
        $no++;
    }
    $all = $ok + $no;
    $percent = $ok * 100 / $all;
    return $percent;
  }

  private static function _data_object2array_template($data) {
    return [
    "id" => $data->id,
    "barcode" => $data->barcode,
    "name" => $data->name
    ];
  }

}
?>
