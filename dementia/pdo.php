<?php
$conn = new PDO("mysql:host=localhost;dbname=0819;charset=utf8;","root","");

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