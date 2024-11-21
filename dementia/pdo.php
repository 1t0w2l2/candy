<?php
// $conn = new PDO("mysql:host=127.0.0.1;dbname=memories;charset=utf8;","memories","y9xBn@66fHx");
$conn = new PDO("mysql:host=127.0.0.1;dbname=0819;charset=utf8;","root","");
function query($query){
    global $conn;
    return $conn->query($query);
}

function fetch($result){
    return $result->fetch();
}

function fetchAll($result){
    return $result->fetchAll();
}

function rownum($result){
    return $result->rowCount();
}