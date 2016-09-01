<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of db_handler
 *
 * @author Lenovo
 */
require_once __DIR__ . '\db_connect.php';

class DBHandler {

    //put your code here
    private $conn;
    private $connectToDB;

    function __construct() {
        $this->connectToDB = new DBConnect();

        $this->conn = $this->connectToDB->connect();
    }

    function __destruct() {
        $this->connectToDB->disconnect();
    }

    protected function isUserPresent($phone) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE phone=?");
        $stmt->bind_param("i", $phone);
        $stmt->execute();
        $stmt->store_result();
        $row_count = $stmt->num_rows;
        $stmt->close();
        return $row_count == 1;
    }

    public function newUser($name, $email, $phone) {
        $response = array();
        $response['error'] = true;
        $response['message'] = "An error occurred";

        if ($this->isUserPresent($phone)) {
            $response['error'] = true;
            $response['message'] = "Existing user";
        } else {
            $stmt = $this->conn->prepare("INSERT INTO users(name,phone,email) VALUES (?,?,?)");
            $stmt->bind_param("sis", $name, $phone, $email);

            if ($stmt->execute()) {
                $response['error'] = false;
                $response['message'] = "User registration successful.";
                $response['user'] = $this->getUserById($this->conn->insert_id);
            }
            $stmt->close();
        }
        return $response;
    }

    public function newGroup($groupName, $creatorUID) {
        $response['error'] = true;
        $response['message'] = "An error occurred";

        //REGISTRING GROUP IN ALL GROUP LIST
        $stmt = $this->conn->prepare("INSERT INTO group_list(group_name,creator_uid) VALUES(?,?)");
        $stmt->bind_param("si", $groupName, $creatorUID);
        $temp = $this->conn->insert_id;
        if ($stmt->execute()) {
            $response['group_data'] = $this->getGroupById($temp);

            //ADD GROUP TO TABLE MAINTAING LIST OF MEMBERS
            $this->addMemberToGroup($creatorUID, $temp);
        }
        $stmt->close();

        return $response;
    }

    public function addMemberToGroup($memberUID, $groupID) {
        $response = array();
        $response['error'] = true;
        $response['message'] = "An error occurred.";
        $stmt = $this->conn->prepare("INSERT INTO group_members(member_uid,group_id) VALUES (?,?)");
        $stmt->bind_param('ii', $memberUID, $groupID);
        if ($stmt->execute()) {
            $response['error'] = false;
            $response['message'] = $memberUID . " added to group with id " . $groupID;
        }
        $stmt->close();
        return $response;
    }

    protected function getGroupById($gID) {
        $stmt = $this->conn->prepare(
                "SELECT * FROM group_list WHERE group_id=?"
        );
        $stmt->bind_param('i', $gID);
        $stmt->execute();
        $groupData = array();
        $stmt->bind_result($groupData['id'], $groupData['group_name'], $groupData['creation_date'], $groupData['creator_uid']
        );
        $stmt->fetch();
        $stmt->close();
        return $groupData;
    }

    protected function getUserById($uid) {
        $stmt = $this->conn->prepare(
                "SELECT * FROM users WHERE user_id=?"
        );
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $userData = array();
        $stmt->bind_result($userData['id'], $userData['name'], $userData['email'], $userData['phone'], $userData['fcm_id']);
        $stmt->fetch();
        $stmt->close();
        return $userData;
    }

    public function getAllUsers() {
        $stmt = $this->conn->prepare("SELECT * FROM users");
        $stmt->execute();
        $result = $stmt->get_result();
        $users = array();
        while ($user = $result->fetch_assoc()) {
            array_push($users, $user);
        }
        $stmt->close();
        return $users;
    }

    public function getAllGroups() {
        $stmt = $this->conn->prepare("SELECT * FROM group_list");
        $stmt->execute();
        $result = $stmt->get_result();
        $groups = array();
        while ($group = $result->fetch_assoc()) {
            array_push($groups, $group);
        }
        $stmt->close();
        return $groups;
    }

    public function getGroupMembers($gID) {
        $query = "SELECT a.member_uid,b.name,b.email,b.phone" .
                " FROM group_members a,users b" .
                " WHERE a.group_id=? AND a.member_uid=b.user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $gID);
        $stmt->execute();
        $result = $stmt->get_result();
        $members = array();
        while ($member = $result->fetch_assoc()) {
            array_push($members, $member);
        }
        $stmt->close();
        return $members;
    }

    public function getAllGroupsByUserUID($uid) {
        $query = "SELECT a.group_id,b.group_name,b.creation_date,b.creator_uid," .
                "c.name,c.phone,c.email FROM group_members a,group_list b," .
                "users c WHERE a.member_uid=? AND a.group_id=b.group_id " .
                "AND b.creator_uid=c.user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $result = $stmt->get_result();
        $groups = array();
        while ($group = $result->fetch_assoc()) {
            array_push($groups, $group);
        }
        $stmt->close();
        return $groups;
    }

    public function getAllGroupsByAdminUID($uid) {
        $stmt = $this->conn->prepare("SELECT * FROM group_list WHERE creator_uid=?");
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $result = $stmt->get_result();
        $groups = array();
        while ($group = $result->fetch_assoc()) {
            array_push($groups, $group);
        }
        $stmt->close();
        return $groups;
    }

    public function getAllMessages($gID) {
        $stmt = $this->conn->prepare("SELECT * FROM group_messages WHERE group_id=?");
        $stmt->bind_param('i', $gID);
        $stmt->execute();
        $result = $stmt->get_result();
        $msgs = array();
        while ($msg = $result->fetch_assoc()) {
            array_push($msgs, $msg);
        }
        $stmt->close();
        return $msgs;
    }

    public function newMessage($senderUID, $groupUID, $message) {
        $response['error'] = true;
        $response['data'] = 'error';
        $stmt = $this->conn->prepare("INSERT INTO group_messages(group_id,sender_uid,message)" .
                " VALUES(?,?,?)");
        $stmt->bind_param('iis', $groupUID, $senderUID, $message);
        if ($stmt->execute()) {
            $response['error'] = false;
            $data = array();
            $msgID = $this->conn->insert_id;
            $stmt->close();
            $stmt2 = $this->conn->prepare("SELECT * FROM group_messages WHERE message_id=?");
            $stmt2->bind_param('i', $msgID);
            $stmt2->execute();
            $temp = NULL;

            $stmt2->bind_result(
                    $data['message_id'], $data['group_id'], $temp, $data['message'], $data['time_stamp']);
            $stmt2->fetch();
            $data['sender'] = $this->getUserById($temp);
            $response['data'] = $data;
            $stmt2->close();
        }
        return $response;
    }

    public function updateFcmId($uID, $fcmID) {
        $response['error'] = true;
        $response['message'] = "Could not update token id";
        $stmt = $this->conn->prepare("UPDATE users SET fcm_id=? WHERE user_id=?");
        $stmt->bind_param('si', $fcmID, $uID);
        if ($stmt->execute()) {
            $response['error'] = false;
            $response['message'] = "FCM token id updated successfully";
        }
        $stmt->close();
        return $response;
    }

}

//function list
    //getgroupbyid -- done
    //getuserbyid -- done
    //getallusers -- done
    //getallgroups -- done
    //getgroupmembers(gid) -- done
    //getallgroupsbyuseruid -- done
    //getallgroupsbyadminuid -- not necessary -- done
    //newmessage -- done
    //getallmessages(gid) -- done
    //updatefcmid -- done