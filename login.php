<?php
//parameters sent from html script over ajax
if(isset($_POST['email'])) {
    $email = $_POST['email'];
} else {
    $email = null;
}
if(isset($_POST['password'])) {
    $password = $_POST['password'];
} else {
    $password = null;
}
$appl_cookie_name = "loaner-shure";
$appl_cookie_value = "logged-in";

$user_cookie_name = "loaner-shure-user-type";
$user_cookie_value = "";
//insert the delivery in delivery collection
if (isset($email) and isset($password)) {

    $curl = curl_init();

    //Basic API access
    //$query = getenv('LOGIN_API');
    $query = "https://loaner-shure-server.herokuapp.com/api/users/authenticate";
    $headers = array();
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'Authorization: Basic ' . base64_encode($email . ":" . $password);
    curl_setopt($curl, CURLOPT_URL, $query);

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_POSTFIELDS, "");

    $result = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    if ($http_code == '200') {
        $result_json = json_decode($result, true);
        $user_cookie_value = $result_json['type'];
        echo $user_cookie_value;
        setcookie($appl_cookie_name, $appl_cookie_value,0,"/");
        setcookie($user_cookie_name, $user_cookie_value,0,"/");
        //echo $result;
    }
    curl_close($curl);

}
?>