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

    //consolidate multiple line items having same material
    $mat_qty = array();
    $materials_unik = array_unique( $materials );
    $materials_duplicates = array_unique( array_diff_assoc( $materials, $materials_unik ) );
    foreach ($materials as $key => $mat) {
        if (in_array($mat, $materials_duplicates)) {
            if (empty($mat_qty[$mat])) {
                $mat_qty[$mat] = $quantity[$key];
            } else {
                $mat_qty[$mat] = $mat_qty[$mat] + $quantity[$key];
            }
        } else {
            $mat_qty[$mat] = $quantity[$key];
        }
    }

    $data_length = sizeof($materials_unik);
    $materials_array = array();
    $material_bin_array = array();
    $picking_list_array = array();
    $picking_list_itemised = array();
    $item_count = array();

    $curl = curl_init();

    foreach ($materials_unik as $key=>$material) {
        //check if material is invalid or serial_number is missing in input

        $valid = checkMaterialValid($material, $curl);
        if(!$valid) {

            $material_status = "Material ".$material." not valid";
            array_push($material_bin_array, [$material_status, " ", " ", " "]);
        } else {
            //function to get all the bins which have the materials
            $binCount = getBinCount($material, $curl);
            //Material is missing serial number
            if (empty($binCount)) {
                $material_status = "Material ".$material." not found in any bin";
                array_push($material_bin_array, [$material_status, " ", " "]);
            } else {
                //get all the bins that have the exact amount to be picked. This array only has the bin numbers
                $bin_array = array_keys(array_intersect($binCount, [$mat_qty[$material]]));

                if (!empty($bin_array)) {
                    //exact quantity fulfillment from one bin
                    $bin = getClosestBin($bin_array, $curl);
                    //add bin to material_bin_array
                    array_push($material_bin_array, [$material, $bin, $mat_qty[$material]]);
                } else {
                    //Partial fulfillments from multiple bins
                    $partial_bins = getClosestPartialBins($binCount, $mat_qty[$material], $curl);
                    //add bins to material_bin_array
                    foreach ($partial_bins as $partial_bin) {
                        array_push($material_bin_array, [$material, $partial_bin[0], $partial_bin[1]]);
                    }
                }
            }
        }
    }

    // Obtain a bin column
    $bin_column  = array_column($material_bin_array, '1');

    array_multisort($bin_column, SORT_ASC, $material_bin_array);
    $seq = 0;
    $seq_new = 0;
    foreach ($mat_qty as $key => $val) {
        $seq++;
        array_push($materials_array, array('seq_num' => $seq, 'material_number' => $key, 'quantity' => $val));
        $item_count[$key] = "0";
    }

    $count_item = 0;
    foreach ($material_bin_array as $picking_list_value) {
        foreach ($materials_array as $materials_array_el) {
            if ($materials_array_el['material_number'] == $picking_list_value[0]) {
                $item_sequence = $materials_array_el['seq_num'];
            }
        }

        foreach ($item_count as $k => $v) {
            if ($k == $picking_list_value[0]) {
                $count_mat_itemised = $v;
                break;
            }
        }

        if ($picking_list_value[1] != "not available") {
            $q = intval($picking_list_value[2], 10);
            for($i=0; $i<$q; $i++) {
                $count_mat_itemised++;
                $count_item++;
                array_push($picking_list_itemised, [
                    'item_number' => $item_sequence.'/'.$count_mat_itemised,
                    'material' => $picking_list_value[0],
                    'bin' => $picking_list_value[1],
                    'qty' => "1",
                    'pick_status' => false]);
            }
            $item_count[$picking_list_value[0]] = $count_mat_itemised;
        }
    }

    foreach ($item_count as $k => $v) {
        $seq_new++;
       array_push($picking_list_array, [ 'seq_num' => $seq_new,
                                                'material' => $k,
                                                'qty' => $v]);
    }

    $data = array('status' => 'created', 'delivery_number' => $delivery_number, 'picking_list' => $materials_array, 'picking_list_header' => $picking_list_array, 'picking_list_itemised' => $picking_list_itemised, 'count_itemised' => $count_item);
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
        //echo json_encode($material_bin_array);
        $results = array(
            "sEcho" => 1,
            "iTotalRecords" => count($material_bin_array),
            "iTotalDisplayRecords" => count($material_bin_array),
            "aaData"=>$material_bin_array);

        echo json_encode($results);
    }
    curl_close($curl);
}

//function to check if material is valid
function checkMaterialValid($material, $curl) {
    //Basic API access
    //$query = getenv('LOGIN_API');
    $query = "https://loaner-shure-server.herokuapp.com/api/loaners/loaner/".$material;
    $headers = array();
    $headers[] = 'Content-Type: application/json';
    //$headers[] = 'Authorization: Basic '.base64_encode($username.":".$password);
    curl_setopt($curl, CURLOPT_URL, $query);

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

    curl_exec($curl);

    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    if ($http_code == '200') {
        return true;
    } else {
        return false;
    }
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