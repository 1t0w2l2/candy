<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $account = $_POST['account'];
    $password = $_POST['password'];
    $email = $_POST['accountEmail'];
    $name = $_POST['name'];
    $sex = $_POST['gender'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $userType = $_POST['usertype'];
    $institution_id = isset($_POST['institution_id']) ? $_POST['institution_id'] : null;
    $institution_name = isset($_POST['institution_name']) ? $_POST['institution_name'] : null;


    try {
        // 檢查帳號是否已存在
        $query = "SELECT * FROM user WHERE account = '$account'";
        $result = mysqli_query($link, $query);
        if (mysqli_num_rows($result) > 0) {
            echo json_encode(['status' => 'error', 'message' => '該帳號名稱已被使用']);
            exit;
        }

        // 檢查 email 是否已存在
        $query = "SELECT * FROM user WHERE email = '$email'";
        $result = mysqli_query($link, $query);
        if (mysqli_num_rows($result) > 0) {
            echo json_encode(['status' => 'error', 'message' => '該 Email 已被使用']);
            exit;
        }

        if ($userType === 'hospital') {

            // 先檢查 hospital 表中的 institution_id 是否已存在
            $stmt = $link->prepare("SELECT account FROM hospital WHERE institution_id = ?");
            $stmt->bind_param("s", $institution_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // 機構 ID 已存在，返回錯誤信息
                $existingHospital = $result->fetch_assoc();
                echo json_encode([
                    'status' => 'error',
                    'message' => '該機構ID已存在，已註冊帳號為：' . $existingHospital['account']
                ]);
                exit;
            }

            // 接下來檢查 hospital 表中的 institution_name 是否已存在
            $stmt = $link->prepare("SELECT account FROM hospital WHERE institution_name = ?");
            $stmt->bind_param("s", $institution_name);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // 機構名稱已存在，返回錯誤信息
                $existingHospital = $result->fetch_assoc();
                echo json_encode([
                    'status' => 'error',
                    'message' => '該機構名稱已存在，已註冊帳號為：' . $existingHospital['account']
                ]);
                exit;
            }

            $stmt = $link->prepare("SELECT institution_id FROM institution WHERE institution_name = ?");
            $stmt->bind_param("s", $institution_name);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // 機構名稱存在，檢查 ID 是否一致
                $existingInstitution = $result->fetch_assoc();
                if ($existingInstitution['institution_id'] !== $institution_id) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => '該機構名稱已存在，請使用醫療機構ID：' . $existingInstitution['institution_id'] . ' 註冊'
                    ]);
                    exit;
                }
            } else {
                // 機構名稱不存在，檢查 institution_id 是否已存在於 institution 表
                $stmt = $link->prepare("SELECT institution_name FROM institution WHERE institution_id = ?");
                $stmt->bind_param("s", $institution_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    // 機構 ID 已存在，但名稱不同，返回錯誤信息
                    $existingInstitution = $result->fetch_assoc();
                    echo json_encode([
                        'status' => 'error',
                        'message' => '該機構ID已存在，並對應到名稱：' . $existingInstitution['institution_name'] . '，請檢查後再試'
                    ]);
                    exit;
                }
            }
        }




        // 根據 userType 插入對應的表格
        if ($userType === 'caregiver') {
            // 插入 user 表
            $stmt = $link->prepare("INSERT INTO user (account, password, email, name, sex, phone, address, user_type) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssss", $account, $password, $email, $name, $sex, $phone, $address, $userType);
            $stmt->execute();


            $stmt = $link->prepare("INSERT INTO caregiver (account) VALUES (?)");
            $stmt->bind_param("s", $account);
            $stmt->execute();
            echo json_encode(['status' => 'success', 'message' => '帳號已成功新增']);
        } elseif ($userType === 'patient') {

            // 插入 user 表
            $stmt = $link->prepare("INSERT INTO user (account, password, email, name, sex, phone, address, user_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssss", $account, $password, $email, $name, $sex, $phone, $address, $userType);
            $stmt->execute();


            $stmt = $link->prepare("INSERT INTO patient (account) VALUES (?)");
            $stmt->bind_param("s", $account);
            $stmt->execute();
            echo json_encode(['status' => 'success', 'message' => '帳號已成功新增']);
        } elseif ($userType === 'hospital') {
            // Step 1: 檢查 institution 資料表中是否已有此 institution_id 和 institution_name
            $checkQuery = "SELECT * FROM `institution` WHERE `institution_id` = '$institution_id' AND `institution_name` = '$institution_name'";
            $checkResult = mysqli_query($link, $checkQuery);

            if (mysqli_num_rows($checkResult) > 0) {
                // 若存在，插入 user 資料
                $userQuery = "INSERT INTO user (account, password, email, name, sex, phone, address, user_type) VALUES ('$account', '$password', '$email', '$name', '$sex', '$phone', '$address', '$userType')";
                if (!mysqli_query($link, $userQuery)) {
                    throw new Exception("Error inserting user data: " . mysqli_error($link));
                }

                // 插入 hospital 資料
                $hospitalQuery = "INSERT INTO hospital (account, institution_id, institution_name, institution_address, institution_phone, status) VALUES ('$account', '$institution_id', '$institution_name', '$address', '$phone', '1')";
                if (!mysqli_query($link, $hospitalQuery)) {
                    throw new Exception("Error inserting hospital data: " . mysqli_error($link));
                }

                // 插入成功後，返回 hospital 訊息
                echo json_encode(['status' => 'success', 'message' => '帳號已成功新增']);
            } else {
                // 若不存在，則回傳 hospital 訊息
                echo json_encode(['status' => 'success', 'message' => 'hospital']);
            }
        } elseif ($userType === 'admin') {
            $stmt = $link->prepare("INSERT INTO user (account, password, email, name, sex, phone, address, user_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssss", $account, $password, $email, $name, $sex, $phone, $address, $userType);
            $stmt->execute();

            echo json_encode(['status' => 'success', 'message' => '帳號已成功新增']);

        } else {
            echo json_encode(['status' => 'error', 'message' => '發生錯誤，請稍後再試456']);
        }


    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => '發生錯誤，請稍後再試']);
    }
}

mysqli_close($link);
?>