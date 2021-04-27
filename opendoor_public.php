<?php

define('HIKVISION_LOGIN', ''); // login
define('HIKVISION_PASSWORD', ''); // hash - view in fiddler
define('FC', ''); // view in fiddler
define('CUSTOMNO', ''); // view in fiddler
define('CLIENT_VERSION', '4.14.0.1042873'); // view in fiddler
define('CLIENT_TYPE', 54); // view in fiddler
define('APP_ID', 'NewHikConnect'); // view in fiddler
define('CUNAME', ''); // view in fiddler
define('PUSH_TOKEN', ''); // view in fiddler
define('PUSH_VOIPTOKEN', ''); // view in fiddler
define('DEVICE_SERIAL', 'Q12345678'); // view in fiddler

$openTime = (int) file_get_contents('./opentime');


if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (time() - $openTime > 10) {
        echo json_encode(['is_active' => false]);
    }
    else {
        echo json_encode(['is_active' => true]);
    }

    die;
}
elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
    file_put_contents('./opentime', time());


    if (time() - $openTime > 3600) {
        reauth();
    }
}


function request($method, $url, $data = [], $headers = [])
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);

    if (!empty($data)) {
        if (is_array($data)) {
            $data = http_build_query($data);
        }
    }

    if ($method == 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    elseif ($method == 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'HikConnect/4.14.0 (iPhone; iOS 14.4.2; Scale/3.00)');


    $output = curl_exec($ch);
    curl_close ($ch);

    return $output;
}

function login()
{
    $url = 'https://apiirus.hik-connectru.com/v3/users/login/v2';

    $data = [
        'account'           => HIKVISION_LOGIN,
        'bizType'           => '',
        'cuName'            => CUNAME,
        'featureCode'       => FC,
        'imageCode'         => '',
        'latitude'          => '',
        'longitude'         => '',
        'password'          => HIKVISION_PASSWORD,
        'pushExtJson'       => '{"criticalAlert":"true","language":"en"}',
        'pushRegisterJson'  => '[{"channel" : 99},{"channel" : 5,"channelRegisterJson":"{\"token\" : \"' . PUSH_TOKEN . '\",\"voipToken\":\"' . PUSH_VOIPTOKEN . '\",\"callTokenType\":0}"}]',
        'redirect'          => '',
        'smsCode'           => '',
        'smsToken'          => '',
    ];

    $headers = [
        'customno: ' . CUSTOMNO,
        'Content-Type: application/x-www-form-urlencoded',
        'featureCode: ' . FC,
        'Accept-Language: ru-RU;q=1, en-RU;q=0.9',
        'clientVersion: ' . CLIENT_VERSION,
        'appId: ' . APP_ID,
        'sessionId: ',
        'clientType: ' . CLIENT_TYPE,
        'lang: ru-RU',
        'clientNo:',
        'areaId: 114',
        'netType: WIFI',
        'osVersion: 14.4.2',
    ];

    echo 'HEADERS: ' . print_r($headers, true) . PHP_EOL;

    $result = request('POST', $url, $data, $headers);

    echo $result;

}


function reauth()
{
    $url = 'https://apiirus.hik-connectru.com/v3/apigateway/login';

    $authData = authData();

    $data = [
        'cuName'            => 'iPhone Mikhail N',
        'featureCode'       => FC,
        'pushExtJson'       => '{"criticalAlert":"true","language":"en"}',
        'pushRegisterJson'  => '[{"channel" : 99},{"channel" : 5,"channelRegisterJson":"{\"token\" : \"6192a4a410929ad5d9654e49b6e5da1abe10dfb5a19309582a2ca6c696bccdcb\",\"voipToken\":\"fb53ab84f7db549ce521bd5945cdbffb231c6b655d07218f27460dbbe477a0c8\",\"callTokenType\":0}"}]',
        'refreshSessionId'  => $authData['refreshSessionId'],
    ];

    $headers = [
        'customno: ' . CUSTOMNO,
        'Content-Type: application/x-www-form-urlencoded',
        'featureCode: ' . FC,
        'Accept-Language: ru-RU;q=1, en-RU;q=0.9',
        'clientVersion: ' . CLIENT_VERSION,
        'appId: ' . APP_ID,
        'sessionId: ' . $authData['sessionId'],
        'clientType: ' . CLIENT_TYPE,
        'lang: ru-RU',
        'clientNo:',
        'areaId: 114',
        'netType: WIFI',
        'osVersion: 14.4.2',
    ];

    $result = request('PUT', $url, $data, $headers);
    writeNewAuthData($result);
}

function authData()
{
    $jsonData = file_get_contents('./opendoor.json');

    $data = json_decode($jsonData, true);

    return [
        'sessionId'         => $data['sessionInfo']['sessionId'],
        'refreshSessionId'  => $data['sessionInfo']['refreshSessionId'],
    ];
}

function writeNewAuthData($content)
{
    file_put_contents('./opendoor.json', $content);
}

function openDoor()
{
    $authData = authData();

    $url = 'https://apiirus.hik-connectru.com/v3/userdevices/v1/isapi';

    $data = 'apiData=PUT%20/ISAPI/AccessControl/RemoteControl/door/1%0D%0A%3CRemoteControlDoor%3E%3Ccmd%3Eopen%3C/cmd%3E%20%3CchannelNo%3E1%3C/channelNo%3E%20%3CcontrolType%3Emonitor%3C/controlType%3E%20%3Cpassword%3E%3C/password%3E%20%3C/RemoteControlDoor%3E&apiKey=100044&channelNo=1&deviceSerial=' . DEVICE_SERIAL;

    $headers = [
        'customno: ' . CUSTOMNO,
        'Content-Type: application/x-www-form-urlencoded',
        'featureCode: ' . FC,
        'Accept-Language: ru-RU;q=1, en-RU;q=0.9',
        'clientVersion: ' . CLIENT_VERSION,
        'appId: ' . APP_ID,
        'sessionId: ' . $authData['sessionId'],
        'clientType: ' . CLIENT_TYPE,
        'lang: ru-RU',
        'clientNo:',
        'areaId: 114',
        'netType: WIFI',
        'osVersion: 14.4.2',
    ];

    $result = request('POST', $url, $data, $headers);
    echo $result;
}

openDoor();