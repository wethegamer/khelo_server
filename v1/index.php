<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

error_reporting(-1);
ini_set('display_errors', 'On');

require_once __DIR__ . '\..\include\db_handler.php';
require __DIR__ . '\..\libs\Slim\Slim.php';

\Slim\Slim::registerAutoloader();

//LOG WRITER
//necessary requests
//GET REQUESTS

$app = new \Slim\Slim();

//get all messages for group_id

$app->get('/group/:gp_id/messages', function($gId) {
    $db = new DBHandler();
    $result = $db->getAllMessages($gId);
    echoResponse($result, 200);
});

//get all members of group
$app->get('/group/:gp_id/members', function($gpID) {
    $db = new DBHandler();
    $resp = $db->getGroupMembers($gpID);
    echoResponse($resp, 200);
});

//get all groups of a member
$app->get('/user/:m_uid/groups', function($mUID) {
    $db = new DBHandler();
    $list = $db->getAllGroupsByUserUID($mUID);
    echoResponse($list, 200);
});

//get all groups of an admin
$app->get('/admin/:admin_id/groups', function($id) {
    $db = new DBHandler();
    $list = $db->getAllGroupsByAdminUID($id);
    echoResponse($list, 200);
});

//POST REQUESTS
//new user
$app->post('/user/register', function() use ($app) {
    verifyParams(array('name', 'phone', 'email'));

    $name = $app->request()->post('name');
    $phone = $app->request()->post('phone');
    $email = $app->request()->post('email');

    verifyEmail($email);

    $db = new DBHandler();
    $res = $db->newUser($name, $email, $phone);
    echoResponse($res, 200);
});

//new group
$app->post('/group/create/:uid', function($uid) use ($app) {
    verifyParams(array('group_name'));

    $gpName = $app->request()->post('group_name');

    $db = new DBHandler();
    $res = $db->newGroup($gpName, $uid);
    echoResponse($res, 200);
});

//new member to group
$app->post('/group/:g_id/add-member/:m_id', function($gID, $mUID) {
    $db = new DBHandler();
    $res = $db->addMemberToGroup($mUID, $gID);
    echoResponse($res, 200);
});

//new message in group
$app->post('/group/:group_id/message', function($gID) use ($app) {
    verifyParams(array('sender_uid', 'message'));
    $senderUID = (int)$app->request()->post('sender_uid');
    $message = $app->request()->post('message');

    $db = new DBHandler();

    $resp = $db->newMessage($senderUID, $gID, $message);

    if (!$resp['error']) {
        require_once __DIR__ . '\..\libs\fcm\FcmSend.php';
        require_once __DIR__ . '\..\libs\fcm\Push.php';

        $fcmSend = new FcmSend();
        $push = new Push();

        $data['group'] = $db->getGroupById($gID);
        $data['message'] = $resp['data'];
        $push->setTitle($data['group']['group_name']);
        $notif['title'] = $data['group']['group_name'];
        $notif['body'] = $message;
        $push->setNotif($notif);
        $push->setData($data);
        $fcmSend->sendToMultiple(fcmIdList($gID), $push->getPush());

        echoResponse($data, 200);
    }
});

//PUT request
//update fcm id
$app->put('/user-token-update/:uid', function($uID) use ($app) {
    verifyParams(array('fcm_id'));
    $fcmID = $app->request()->put('fcm_id');

    $db = new DBHandler();
    $result = $db->updateFcmId($uID, $fcmID);
    echoResponse($result, 200);
});

//ADMIN PAGE REQUESTS
//get
//getallusers
$app->get('/users', function() {
    $db = new DBHandler();
    echoResponse($db->getAllUsers(), 200);
});

//get all group list
$app->get('/groups', function() {
    $db = new DBHandler();
    echoResponse($db->getAllGroups(), 200);
});

//verificaion and response
function verifyParams($fields) {
    $error = false;
    $errorFields = '';
    $params = $_REQUEST;

    if ($_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'POST') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $params);
    }

    foreach ($fields as $field) {
        if (!isset($params[$field]) || strlen(trim($params[$field])) <= 0) {
            $error = true;
            $errorFields.=$field . ', ';
        }
    }
    if ($error) {
        $app = \Slim\Slim::getInstance();
        $errorFields = "Required field(s) missing: " . substr($errorFields, 0, -2);
        $res['error'] = $error;
        $res['message'] = $errorFields;
        echoResponse($res, 400);
        $app->stop();
    }
}

function verifyEmail($email) {
    $app = \Slim\Slim::getInstance();
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['error'] = true;
        $response['message'] = 'Invalid email';
        echoResponse($response, 400);
        $app->stop();
    }
}

function echoResponse($resp, $statCode) {
    $app = \Slim\Slim::getInstance();
    $app->status($statCode);
    $app->contentType('application/json');
    echo json_encode($resp);
}

function fcmIdList($gID) {
    $db = new DBHandler();
    $idList = array();
    $regId = $db->getGroupMembers($gID);

    foreach ($regId as $value) {
        array_push($idList, $value['fcm_id']);
    }
    return $idList;
}

$app->run();
