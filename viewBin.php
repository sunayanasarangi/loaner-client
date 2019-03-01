<?php
$materials_all = array();
$materials = array();
$viewBinArray = array();
$curl = curl_init();
$query = "https://loaner-shure-server.herokuapp.com/api/loaners";
$headers = array();
$headers[] = 'Content-Type: application/json';
//$headers[] = 'Authorization: Basic '.base64_encode($username.":".$password);
curl_setopt($curl, CURLOPT_URL, $query);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

$response = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$results = json_decode($response, true);
if ($http_code == '200') {
    foreach ($results as $result) {
        array_push($materials_all, [$result['sku'], $result['serial_number']]);
    }

    $materials = array_map("unserialize", array_unique(array_map("serialize", $materials_all)));
    //$materials = array_unique($materials_all);

    foreach ($materials as $material) {
        curl_setopt($curl, CURLOPT_URL, $query . "/bins/" . $material[0]. "/" . $material[1]);
        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($http_code == '200') {
            $materials_sorted = json_decode($response, true);

            foreach ($materials_sorted as $item) {
                $item_enc = json_encode($item);
                $row = json_decode($item_enc, true);

                $bin = $row["_id"];
                $count = $row["count"];
                if ($material[1] === "0") {
                    array_push($viewBinArray, [$material[0], "", $bin, $count]);
                } else {
                    array_push($viewBinArray, [$material[0], $material[1], $bin, $count]);
                }

            }
        }
    }

    $results = array(
        "sEcho" => 1,
        "iTotalRecords" => count($viewBinArray),
        "iTotalDisplayRecords" => count($viewBinArray),
        "aaData"=>$viewBinArray);

    echo json_encode($results);
}
?>


