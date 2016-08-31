<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Push
 *
 * @author Lenovo
 */
class Push {

    //put your code here
    private $title;
    private $data;
    private $notif;

    function __construct() {
        
    }

    function setTitle($title) {
        $this->title = $title;
    }

    function setData($data) {
        $this->data = $data;
    }

    function setNotif($notif) {
        $this->notif = $notif;
    }

    public function getPush() {
        $res = array();
        $res['title'] = $this->title;
        $res['data'] = $this->data;
        $res['notification'] = $this->notif;
        return $res;
    }

}
