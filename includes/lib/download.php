<?php
$quality = isset($_REQUEST['quality']) ? $_REQUEST['quality'] : 'preview';
$url = 'https://'. $_REQUEST['subdomain'] .'.canto.com/api_binary/v1/image/'. $_REQUEST['id'] .'/'.$quality;

$header = array( 'Authorization: Bearer '. $_REQUEST['token']);

$ch = curl_init();

$options = array(
    CURLOPT_URL            => $url,
    CURLOPT_REFERER        => 'Wordpress Plugin',
    CURLOPT_USERAGENT      => 'Wordpress Plugin',
    CURLOPT_HTTPHEADER     => $header,
    CURLOPT_SSL_VERIFYHOST => 0,
    CURLOPT_SSL_VERIFYPEER => 0,
    CURLOPT_HEADER         => 1,
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_TIMEOUT        => 10,
//    CURLOPT_POST           => 1
);

curl_setopt_array( $ch, $options );
$data = curl_exec( $ch );
curl_close( $ch );

echo $data;

//$out = json_decode($data);
//header('Content-Type: application/json;charset=utf-8');
//echo json_encode($out->url->LowJPG);
//var_dump($out->url->LowJPG);
?>
