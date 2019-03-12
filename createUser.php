<?php
//parameters sent from html script over ajax
if(isset($_POST['name'])) {
    $name = $_POST['name'];
} else {
    $name = null;
}
if(isset($_POST['email'])) {
    $email = $_POST['email'];
} else {
    $email = null;
}
if(isset($_POST['type'])) {
    $type = $_POST['type'];
} else {
    $type = null;
}
if(isset($_POST['password'])) {
    $password = $_POST['password'];
} else {
    $password = null;
}

//insert the delivery in delivery collection
if (isset($name) and isset($email) and isset($type) and isset($password)) {
    $post_fields = array("name" => $name, "email" => $email, "password" => $password, "type" => $type);
    $post_fields_string = json_encode($post_fields);
    $curl = curl_init();

    //Basic API access
    //$query = getenv('LOGIN_API');
    $query = "https://loaner-shure-server.herokuapp.com/api/users/register";
    $headers = array();
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'Content-Length: ' . strlen($post_fields_string);
    $headers[] = 'Authorization: Basic ' . base64_encode($email . ":" . $password);
    curl_setopt($curl, CURLOPT_URL, $query);

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $post_fields_string);

    $result = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    echo $result;
    /*
    if ($http_code == '200') {
        $result_json = json_decode($result, true);
        echo $result;
    }*/
    curl_close($curl);
}
?>