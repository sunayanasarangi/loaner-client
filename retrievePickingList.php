<?php

//parameters sent from html script over ajax

if(isset($_POST['deliveries'])) {
    $deliveries = json_decode($_POST['deliveries'], true);
} else {
    $deliveries = array();
}

//insert the delivery in delivery collection
if (!empty($deliveries)) {
    $picking_list_array = array();
    $curl = curl_init();
    $query = "https://loaner-shure-server.herokuapp.com/api/deliveries/";
    $headers = array();
    $headers[] = 'Content-Type: application/json';
    //$headers[] = 'Authorization: Basic '.base64_encode($username.":".$password);

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

    foreach ($deliveries as $delivery) {
        curl_setopt($curl, CURLOPT_URL, $query . $delivery);
        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $results = json_decode($response, true);
        if ($http_code == '200') {
            //array_push($picking_list_array, ["success", $results['delivery_number'], $results['picking_list']]) ;
            foreach ($results['picking_list'] as $el) {
                array_push($picking_list_array, [$results['delivery_number'], $el['material'], $el['bin'], $el['qty']]);
            }


        } elseif ($http_code == '404') {
            array_push($picking_list_array, ["Delivery Number \"".$delivery."\" not found.", "", "", ""]);

        }
    }
    //echo json_encode($picking_list_array);
    $results = array(
        "sEcho" => 1,
        "iTotalRecords" => count($picking_list_array),
        "iTotalDisplayRecords" => count($picking_list_array),
        "aaData"=>$picking_list_array);

    echo json_encode($results);
    curl_close($curl);
}

?>