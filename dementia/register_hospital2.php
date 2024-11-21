<?php
include 'db.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acc = $_SESSION['account'];
    $institution_name = isset($_POST['institution_name']) ? trim($_POST['institution_name']) : '';
    $institution_id = isset($_POST['institution_id']) ? trim($_POST['institution_id']) : '';
    $institution_latlng = isset($_POST['institution_latlng']) ? trim($_POST['institution_latlng']) : '';
    $city = isset($_POST['city']) ? str_replace('台', '臺', $_POST['city']) : '';
    $town = isset($_POST['town']) ? str_replace('台', '臺', $_POST['town']) : '';
    $institution_address = isset($_POST['institution_address']) ? str_replace('台', '臺', $_POST['institution_address']) : '';

    echo "<script type='text/javascript'>console.log('縣市'," . json_encode($city) . ");</script>";
    echo "<script type='text/javascript'>console.log('鄉鎮市區'," . json_encode($town) . ");</script>";
    $person_charge = isset($_POST['person_charge']) ? trim($_POST['person_charge']) : '';
    $institution_phone = isset($_POST['institution_phone']) ? trim($_POST['institution_phone']) : '';
    $institution_url = isset($_POST['institution_url']) ? trim($_POST['institution_url']) : '';
    $institution_img = isset($_FILES['institution_img']) ? $_FILES['institution_img'] : null;



    $account = isset($_POST['account']) ? $_POST['account'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $name = isset($_POST['name']) ? $_POST['name'] : '';
    $sex = isset($_POST['sex']) ? $_POST['sex'] : '';
    $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
    $addressuser = isset($_POST['addressuser']) ? $_POST['addressuser'] : '';
    $userType = isset($_POST['userType']) ? $_POST['userType'] : '';

    $sql_user = "INSERT INTO user (account, password, email, name, sex, phone, address, user_type) 
                 VALUES ('$account', '$password', '$email', '$name', '$sex', '$phone', '$addressuser', 'hospital')";
    mysqli_query($link, $sql_user);

    // 檢查所有欄位是否填寫
    if (!empty($institution_name) && !empty($institution_address) && !empty($city) && !empty($town) && !empty($institution_latlng) && !empty($institution_phone) && !empty($institution_id) && !empty($_POST['business_hours'])) {

        if (!empty($institution_latlng)) {
            list($longitude, $latitude) = explode(',', $institution_latlng);
        } else {
            echo "<script type='text/javascript'>console.log('經緯度未提供。');</script>";
        }

        // 檢查是否已存在機構資料
        $sql_hospital = "SELECT * FROM `institution` WHERE `institution_name` = ?";
        $stmt = mysqli_prepare($link, $sql_hospital);
        mysqli_stmt_bind_param($stmt, 's', $institution_name);
        mysqli_stmt_execute($stmt);
        $hospital_row = mysqli_stmt_get_result($stmt)->fetch_assoc();

        if (empty($hospital_row)) {
            // 機構尚未存在，進行插入操作
            $sql_delete = "DELETE FROM `institution` WHERE `institution_id` = '$institution_id'";
            mysqli_query($link, $sql_delete);

            // 插入新機構資料
            $sql_insert = "INSERT INTO `institution` (`institution_id`, `institution_name`, `county`, `town`, `address`, `phone`, `person_charge`, `website`, `lat`, `lng`) 
                           VALUES ('$institution_id', '$institution_name', '$city', '$town', '" . $city . $town . $institution_address . "', '$institution_phone', '$person_charge', '$institution_url', '$latitude', '$longitude')";

            if (!mysqli_query($link, $sql_insert)) {
                echo "<script>alert('機構資料插入失敗！');</script>";
            }

        } else {
            // 更新現有的機構資料
            $sql = "UPDATE `institution` SET `institution_name`='$institution_name',`county`='$city',`town`='$town',`address`='" . $city . $town . $institution_address . "',`phone`='$institution_phone',`person_charge`='$person_charge',`website`='$institution_url',`lat`='$latitude',`lng`='$longitude' WHERE `institution_id`='$institution_id'";

            if (!mysqli_query($link, $sql)) {
                echo "<script>alert('機構資料更新失敗！');</script>";
            }
        }

        // 處理營業時間
        if (isset($_POST['business_hours'])) {
            $sql_delete = "DELETE FROM `servicetime` WHERE `institution_id` = '$institution_id'";
            mysqli_query($link, $sql_delete);

            $business_hours = $_POST['business_hours'];

            // 定義一個包含所有星期的陣列
            $daysOfWeek = [
                '星期一',
                '星期二',
                '星期三',
                '星期四',
                '星期五',
                '星期六',
                '星期日'
            ];

            // 遍歷每一天
            foreach ($daysOfWeek as $day) {
                // 確定當前這一天是否有時間段
                $day_times = isset($business_hours[$day]) ? $business_hours[$day] : [];

                // 檢查該天是否有時間段
                $hasTimeSlot = false;

                foreach ($day_times as $time) {
                    $open_time = isset($time['open_time']) ? $time['open_time'] : '';
                    $close_time = isset($time['close_time']) ? $time['close_time'] : '';

                    // 判斷如果開放時間和關閉時間符合特定條件
                    if (($open_time === '00:00:00' && $close_time === '23:59') || ($open_time === '00:00' && $close_time === '23:59')) {
                        $close_time = '24:00'; // 將關閉時間設置為 24:00
                    }

                    // 如果有有效的開放時間和關閉時間
                    if ($open_time && $close_time) {
                        $hasTimeSlot = true; // 標記該天有時間段
                        $sql = "INSERT INTO `servicetime`(`institution_id`, `day`, `open_time`, `close_time`) VALUES ('$institution_id', '$day', '$open_time', '$close_time')";

                        // 執行插入操作
                        if (!mysqli_query($link, $sql)) {
                            echo "<script>alert('營業時間資料插入失敗！');</script>";
                        }
                    }
                }

                // 如果該天沒有時間段，插入 '00:00' 和 '00:00' 作為預設時間
                if (!$hasTimeSlot) {
                    $open_time = '00:00';
                    $close_time = '00:00';
                    $sql = "INSERT INTO `servicetime`(`institution_id`, `day`, `open_time`, `close_time`) VALUES ('$institution_id', '$day', '$open_time', '$close_time')";

                    // 執行插入操作
                    if (!mysqli_query($link, $sql)) {
                        echo "<script>alert('營業時間資料插入失敗！');</script>";
                    }
                }
            }

            echo "<script type='text/javascript'>console.log(" . json_encode($business_hours) . ");</script>";
        }


        // 處理文件上傳
        $extension = pathinfo($institution_img['name'], PATHINFO_EXTENSION);
        $target_file = 'hospital/' . $institution_id . '.' . $extension;
        // 處理 hospital 表的插入
        $sql = "INSERT INTO `hospital`(`account`, `institution_id`, `institution_name`, `institution_address`, `institution_phone`, `institution_img`) 
                VALUES ('$account','$institution_id','$institution_name','" . $city . $town . $institution_address . "','$institution_phone','$target_file')";

        if (!mysqli_query($link, $sql)) {
            echo "<script>alert('醫院資料插入失敗！');</script>";
        }


        if (move_uploaded_file($institution_img['tmp_name'], $target_file)) {
            // 發送驗證郵件
            $email = $_SESSION['email'];
            $name = $_SESSION['name'];
            $r = rand(100000, 999999);
            $_SESSION['verification_code'] = $r;

            send_verification_email($email, $name, $r);

            echo "<script>alert('感謝您！已成功送出帳號申請，請到信箱查收驗證碼！'); window.location.href = 'email.php';</script>";
            exit;
        } else {
            echo "<script>alert('檔案上傳失敗，請稍後再試。');</script>";
        }
    } else {

        $sql_hospital = "SELECT * FROM `institution` WHERE `institution_name` = '$institution_name'";
        $result = mysqli_query($link, $sql_hospital);
        $hospital_row = mysqli_fetch_all($result, MYSQLI_ASSOC);

        if (!empty($hospital_row)) {
            $_SESSION['hospital_data'] = $hospital_row[0];
            echo "<form id='hospitalForm' action='register_hospital.php' method='post'>";

            foreach ($hospital_row[0] as $key => $value) {
                echo "<input type='hidden' name='$key' value='" . htmlspecialchars($value) . "'>";
            }

            echo "</form>";
            // 在 alert 中顯示錯誤訊息，然後提交表單
            //echo "<script>alert('請確保所有欄位均已填寫，以下欄位缺失：$missing_message'); document.getElementById('hospitalForm').submit();</script>";
            echo "<script>alert('請確保所有欄位均已填寫。'); document.getElementById('hospitalForm').submit();</script>";
            exit();
        } else {
            $institution_name = $_POST['institution_name'];
            echo "<form id='hospitalForm' action='register_hospital.php' method='post'>";
            echo "<input type='hidden' name='institution_name' value='$institution_name'>";
            echo "</form>";
            //echo "<script>alert('請確保所有欄位均已填寫，以下欄位缺失：$missing_message'); document.getElementById('hospitalForm').submit();</script>";
            echo "<script>alert('請確保所有欄位均已填寫。'); document.getElementById('hospitalForm').submit();</script>";
            exit();
        }

    }



}


function send_verification_email($email, $name, $r)
{
    // 加载 PHPMailer 的文件
    require 'src/Exception.php';
    require 'src/PHPMailer.php';
    require 'src/SMTP.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $message1 = "您好，我是失智守護系统的管理員，為了確保您的信箱是正確的，請用以下驗證碼，在註冊頁輸入「{$r}」數字，即可完成註冊";
        $title = "這是您的驗證信";

        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'dementia0920@gmail.com';
        $mail->Password = 'okos hkzz dzic mobs';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS; // 'ssl'
        $mail->Port = 465;
        $mail->CharSet = "utf8";
        $mail->setFrom('dementia0920@gmail.com', '失智守護系统');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = $title;
        $mail->Body = $message1;
        $mail->AltBody = strip_tags($message1);

        $mail->send();
    } catch (Exception $e) {
        echo "郵件發送失敗：" . $mail->ErrorInfo;
    }
}

?>