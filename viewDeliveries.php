<?php

$deliveries = array();

$curl = curl_init();
$query = "https://loaner-shure-server.herokuapp.com/api/deliveries";
$headers = array();
$headers[] = 'Content-Type: application/json';
//$headers[] = 'Authorization: Basic '.base64_encode($username.":".$password);
curl_setopt($curl, CURLOPT_URL, $query);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

$response = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

if ($http_code == '200') {

    $results = json_decode($response, true);

    foreach ($results as $result) {

        $created = $result['created_at'];

        $exploded=explode("T",$created);
        $created_date = $exploded[0];
        $created_time = substr($exploded[1], 0, 8);
        $created_at = $created_date . " " . $created_time;

        foreach ($result['materials'] as $item) {

            $material = $item['material_number'];
            $serial_number = $item['serial_number'];
            $qty = $item['quantity'];

            if ($serial_number === "0") {
                array_push($deliveries, [$result['delivery_number'], $created_at, $material, "", $qty]);
            } else {
                array_push($deliveries, [$result['delivery_number'], $created_at, $material, $serial_number, $qty]);
            }
        }
    }

    $results = array(
        "sEcho" => 1,
        "iTotalRecords" => count($deliveries),
        "iTotalDisplayRecords" => count($deliveries),
        "aaData"=>$deliveries);

    echo json_encode($results);

}
?>


