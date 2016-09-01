<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of FcmSend
 *
 * @author Lenovo
 */
class FcmSend {

    //put your code here
    function __construct() {
        
    }

    public function sendToMultiple($fcmIds, $message) {
        $fields = array(
            'registration_ids' => $fcmIds,
            'data' => $message
        );

        return $this->sendPushToGroup($fields);
    }

    private function sendPushToGroup($fields) {
        require_once __DIR__ . '\..\..\include\db_config.php';

        $url = 'https://fcm.googleapis.com/fcm/send';

        $headers = array(
            'Authorization: key=' . GOOGLE_API_KEY,
            'Content-Type: application/json'
        );
        
        $cURLconn=  curl_init();
        curl_setopt($cURLconn, CURLOPT_URL, $url);
        curl_setopt($cURLconn, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($cURLconn, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($cURLconn, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($cURLconn, CURLOPT_POSTFIELDS, json_encode($fields));
        
        $result=  curl_exec($cURLconn);
        
        if (!$result) {
            die('cURL failed: '.curl_error($cURLconn));
        }
        
        curl_close($cURLconn);
        
        return $result;
    }

}
