<?php
include 'db.php';

// 使用 mysqli_real_escape_string 將用戶輸入的資料進行轉義
$account = mysqli_real_escape_string($link, $_POST['account']);
$password = mysqli_real_escape_string($link, $_POST['password']);
$email = mysqli_real_escape_string($link, $_POST['accountEmail']);
$name = mysqli_real_escape_string($link, $_POST['name']);
$sex = mysqli_real_escape_string($link, $_POST['gender']);
$phone = mysqli_real_escape_string($link, $_POST['phone']);
$address = mysqli_real_escape_string($link, $_POST['address']);
$userType = 'hospital';

// 醫療機構相關資料
$institution_id = mysqli_real_escape_string($link, $_POST['institution_id']);
$institution_name = mysqli_real_escape_string($link, $_POST['institution_name']);
$county = mysqli_real_escape_string($link, $_POST['city']);
$town = mysqli_real_escape_string($link, $_POST['district']);
$institution_address = mysqli_real_escape_string($link, $_POST['fullAddress']);
$institution_phone = mysqli_real_escape_string($link, $_POST['institution_phone']);
$person_charge = !empty($_POST['person_charge']) ? mysqli_real_escape_string($link, $_POST['person_charge']) : NULL;
$website = !empty($_POST['institution_url']) ? mysqli_real_escape_string($link, $_POST['institution_url']) : NULL;
$lat = mysqli_real_escape_string($link, $_POST['lat']);
$lng = mysqli_real_escape_string($link, $_POST['lng']);
$status = 1;

$serviceTimes = json_decode($_POST['serviceTimes'], true);

try {
    // Step 1: 插入 user 資料
    $userQuery = "INSERT INTO user (account, password, email, name, sex, phone, address, user_type) VALUES ('$account', '$password', '$email', '$name', '$sex', '$phone', '$address', '$userType')";
    if (!mysqli_query($link, $userQuery)) {
        throw new Exception("Error inserting user data: " . mysqli_error($link));
    }

    // Step 2: 插入 institution 資料
    $institutionQuery = "INSERT INTO institution (institution_id, institution_name, county, town, address, phone, person_charge, website, lat, lng) VALUES ('$institution_id', '$institution_name', '$county', '$town', '$institution_address', '$institution_phone', '$person_charge', '$website', '$lat', '$lng')";
    if (!mysqli_query($link, $institutionQuery)) {
        throw new Exception("Error inserting institution data: " . mysqli_error($link));
    }

    // Step 3: 插入 hospital 資料
    $hospitalQuery = "INSERT INTO hospital (account, institution_id, institution_name, institution_address, institution_phone, status) VALUES ('$account', '$institution_id', '$institution_name', '$institution_address', '$institution_phone', '1')";
    if (!mysqli_query($link, $hospitalQuery)) {
        throw new Exception("Error inserting hospital data: " . mysqli_error($link));
    }

    // Step 4: 插入 servicetime 資料
    foreach ($serviceTimes as $serviceTime) {
        $day = mysqli_real_escape_string($link, $serviceTime['day']);
        $open_time = mysqli_real_escape_string($link, $serviceTime['open_time']);
        $close_time = mysqli_real_escape_string($link, $serviceTime['close_time']);
        $serviceTimeQuery = "INSERT INTO servicetime (institution_id, day, open_time, close_time) VALUES ('$institution_id', '$day', '$open_time', '$close_time')";
        if (!mysqli_query($link, $serviceTimeQuery)) {
            throw new Exception("Error inserting service time data: " . mysqli_error($link));
        }
    }

    echo json_encode(["success" => true, "message" => "醫療機構資料插入成功！"]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "發生錯誤：" . $e->getMessage()]);
    exit;
}