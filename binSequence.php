<?php

$binSequence = array();

$curl = curl_init();
$query = "https://loaner-shure-server.herokuapp.com/api/bins";
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
        array_push($binSequence, [$result['bin'], $result['sequence']]);
    }

    $results = array(
        "sEcho" => 1,
        "iTotalRecords" => count($binSequence),
        "iTotalDisplayRecords" => count($binSequence),
        "aaData"=>$binSequence);

    echo json_encode($results);

}
?>


