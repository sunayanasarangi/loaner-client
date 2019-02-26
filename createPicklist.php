<?php
//parameters sent from html script over ajax
if(isset($_POST['deliveryNumber'])) {
    $delivery_number = $_POST['deliveryNumber'];
} else {
    $delivery_number = null;
}
if(isset($_POST['materials'])) {
    $materials = json_decode($_POST['materials'], true);
} else {
    $materials = array();
}

if(isset($_POST['quantity'])) {
    $quantity = json_decode($_POST['quantity'], true);
} else {
    $quantity = array();
}

//insert the delivery in delivery collection
if (isset($delivery_number) and isset($materials) and isset($quantity)) {

    $data_length = sizeof($materials);
    $materials_array = array();
    $material_bin_array = array();
    $picking_list_array = array();

    $curl = curl_init();

    foreach ($materials as $key=>$material) {
        //function to get all the bins which have the materials
        $binCount = getBinCount($material, $curl);
        //get all the bins that have the exact amount to be picked. This array only has the bin numbers
        $bin_array = array_keys(array_intersect($binCount, [$quantity[$key]]));

        if (!empty($bin_array)) {
            //exact quantity fulfillment from one bin
            $bin = getClosestBin($bin_array, $curl);
            //add bin to material_bin_array
            array_push($material_bin_array, [$material, $bin, $quantity[$key]]);


        } else {
            //Partial fulfillments from multiple bins
            $partial_bins = getClosestPartialBins($binCount, $quantity[$key], $curl);
            //add bins to material_bin_array
            foreach ($partial_bins as $partial_bin) {
                array_push($material_bin_array, [$material, $partial_bin[0], $partial_bin[1]]);
            }
        }
    }
    // Obtain a bin column
    $bin_column  = array_column($material_bin_array, '1');

    array_multisort($bin_column, SORT_ASC, $material_bin_array);

    //insert/update the delivery in deliveries table

    for ($index = 0; $index < $data_length; $index++) {
        array_push($materials_array, array('material_number' => $materials[$index], 'quantity' => $quantity[$index]));
    }
    foreach ($material_bin_array as $picking_list_value) {
        array_push($picking_list_array, ['material' => $picking_list_value[0],
                                                    'bin' => $picking_list_value[1],
                                                    'qty' => $picking_list_value[2]]);
    }

    $data = array('delivery_number' => $delivery_number, 'materials' => $materials_array, 'picking_list' => $picking_list_array);
    $data_string = json_encode($data, JSON_FORCE_OBJECT);

    //Basic API access
    //$query = getenv('LOGIN_API');
    $query = "https://loaner-shure-server.herokuapp.com/api/deliveries/delivery";
    $headers = array();
    $headers[] = 'Content-Type: application/json';
    //$headers[] = 'Authorization: Basic '.base64_encode($username.":".$password);
    curl_setopt($curl, CURLOPT_URL, $query);

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

    curl_exec($curl);

    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    if ($http_code == '200') {
        echo json_encode($material_bin_array);
    }
    curl_close($curl);
}

//function to get the bins that contain a material and the quantity it contains
function getBinCount($material, $curl) {
    $query = "https://loaner-shure-server.herokuapp.com/api/loaners/bins/".$material;
    $bin_count = array();
    $headers = array();
    $headers[] = 'Content-Type: application/json';
    //$headers[] = 'Authorization: Basic '.base64_encode($username.":".$password);
    curl_setopt($curl, CURLOPT_URL, $query);

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_POSTFIELDS, "");

    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if ($http_code == '200') {
        $results = json_decode($response, true);
        foreach ($results as $result) {
            $bin_count[$result['_id']] = $result['count'];
        }
    }
    return $bin_count;
}

//function to get the sequence number of bins and sort it ascending (lowest bin number close to entrance)
function getClosestBin($bin_array, $curl) {
    $seq_array = array();
    $query = "https://loaner-shure-server.herokuapp.com/api/bins/sequence/";

    foreach ($bin_array as $bin_num) {
        curl_setopt($curl, CURLOPT_URL, $query.$bin_num);
        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($http_code == '200') {
            $results = json_decode($response, true);
            $seq_array[$bin_num] = $results['sequence'];
        }
    }
    //sort the sequence array ascending order according to the value
    asort($seq_array);
    //and return the first entry
    reset($seq_array);
    return key($seq_array);
}

//function to get all bins closest to gate for partial fulfillment
function getClosestPartialBins($binCount, $qty, $curl) {

    $partial_closest_bins = array();
    $seq_array = array();
    $query = "https://loaner-shure-server.herokuapp.com/api/bins/sequence/";
    $binCountKeys = array_keys($binCount);
    $partial_qty = 0;

    foreach ($binCountKeys as $binCountKey) {
        curl_setopt($curl, CURLOPT_URL, $query.$binCountKey);
        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($http_code == '200') {
            $results = json_decode($response, true);
            $seq_array[$binCountKey] = $results['sequence'];
        }
    }
    //sort the sequence array ascending order according to the value
    asort($seq_array);
    reset($seq_array);

    foreach ($seq_array as $seq_key=>$seq_val) {
        if ($qty >= 0) {
            if ($binCount[$seq_key] >= $qty) {
                array_push($partial_closest_bins, [$seq_key, $qty]);
                return $partial_closest_bins;
            } else {
                $partial_qty = $qty - $binCount[$seq_key];
                array_push($partial_closest_bins, [$seq_key, $binCount[$seq_key]]);
                $qty = $partial_qty;
            }
        } else {
            return $partial_closest_bins;
        }
    }
    // the remaining items which cannot be fullfilled
    if($qty > 0) {
        array_push($partial_closest_bins, ['not available', $partial_qty]);
        return $partial_closest_bins;
    }
}
?>