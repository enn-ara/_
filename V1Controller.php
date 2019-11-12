<?php

class V1Controller extends ApiController {

  protected $before_actions = [["require_login", "except" => ["index", "show", "repeater", "test", "connection"]]];

  public function require_login() {
    if (isset($_POST["appkey"])) {
      if ($_POST["appkey"] != "********") {
        $json = self::_query_json_template(500, "Parola yanlış!");
        return $this->render(["text" => $json], ["content_type" => "application/json"]);
      }
    } else {
      $json = self::_query_json_template(500, "Üzülerek söylüyorumki buraya erişmeye yetkiniz yok!");
      return $this->render(["text" => $json], ["content_type" => "application/json"]);
    }
  }

  public static function _query_json_template($status, $message, $datas = NULL, $name = NULL) {
    $json_array = ["status" => $status, "message" => $message, "name" => $name, "datas" => $datas];
    return json_encode($json_array, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  }

}
?>
