<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class DBConnect {
    private $con;
    
    public function connect() {
        include_once __DIR__.'\db_config.php';
        $this->con=  mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
        
        if (mysqli_connect_errno()) {
            echo "Failed to connect: ". mysqli_connect_error();
        }
        
        return $this->con;
    }
    
    public function disconnect() {
        mysqli_close($this->con);
    }
}
