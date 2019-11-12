<?php

class DevicesController extends V1Controller {

  public function payment() {

    if (!isset($_POST["payment"]) || !isset($_POST["device"])) {
      $json = self::_query_json_template(429, "Verilerde eksiklik var!");
      return $this->render(["text" => $json], ["content_type" => "application/json"]);
    }

    $payment_index = [0,  1,   2];
    $payment_value = [10, 100, 1000];

    $payment = $_POST["payment"];
    if (in_array($payment, $payment_index)) {
      $credit = $payment_value[$payment];
    } else {
      $json = self::_query_json_template(429, "Ödeme türü bilinmiyor!");
      return $this->render(["text" => $json], ["content_type" => "application/json"]);
    }

    if ($datauser = Datauser::unique(["device" => $_POST["device"]])) {
      DataMailer::delivery("payment", [$_POST["device"]]);

      $datauser->credit = $datauser->credit + $credit;
      $datauser->updated_at = date("Y-m-d H:i:s");
      $datauser->save();

      $datauser_array = self::_datauser_object2array_template($datauser);
      $json = self::_query_json_template(200, "Cihaz için ödeme yapıldı", [$datauser_array], $datauser->device);
      return $this->render(["text" => $json], ["content_type" => "application/json"]);
    } else {
      $json = self::_query_json_template(404, "Ödeme yapılacak bu cihazı bulamadım!");
      return $this->render(["text" => $json], ["content_type" => "application/json"]);
    }

  }

  public function show() {

    if (!isset($_POST["device"])) {
      $json = self::_query_json_template(429, "Verilerde eksiklik var!");
      return $this->render(["text" => $json], ["content_type" => "application/json"]);
    }

    if ($datauser = Datauser::unique(["device" => $_POST["device"]])) {
      $datauser_array = self::_datauser_object2array_template($datauser);
      $json = self::_query_json_template(200, "Cihaz bilgileri bulundu", [$datauser_array], $datauser->device);
      return $this->render(["text" => $json], ["content_type" => "application/json"]);
    } else {
      $json = self::_query_json_template(404, "Böyle bir cihaz yok!");
      return $this->render(["text" => $json], ["content_type" => "application/json"]);
    }
  }

  public function save() {

    if (!isset($_POST["device"])) {
      $json = self::_query_json_template(429, "Verilerde eksiklik var!");
      return $this->render(["text" => $json], ["content_type" => "application/json"]);
    }

    if (!Datauser::unique(["device" => $_POST["device"]])) {
      $datauser = Datauser::create(["device" => $_POST["device"], "credit" => 1000, "created_at" => date("Y-m-d H:i:s")]);
      $datauser_array = self::_datauser_object2array_template($datauser);
      $json = self::_query_json_template(200, "Yeni cihaz kaydedildi", [$datauser_array], $datauser->device);
      return $this->render(["text" => $json], ["content_type" => "application/json"]);
    } else {
      $json = self::_query_json_template(100, "Bu cihaz daha önceden kaydedilmiş!");
      return $this->render(["text" => $json], ["content_type" => "application/json"]);
    }
  }

  private static function _datauser_object2array_template($datauser) {
    return [
      "id" => $datauser->id,
      "device" => $datauser->device,
      "credit" => $datauser->credit
    ];
  }

}
?>
