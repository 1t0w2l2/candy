<?php
require_once('db.php');

// 統一的錯誤處理函式
function handleError($message)
{
    echo "<script>
            alert('$message');
            window.history.back();
          </script>";
    exit;
}

// 檢查是否為 POST 請求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 接收表單數據
    $institution_id = $_POST['institution_id'];
    $institution_name = $_POST['institution_name'];
    $county = $_POST['county'];
    $town = $_POST['town'];
    $address = $_POST['address'];
    $phone = $_POST['phone'];
    $person_charge = $_POST['person_charge'];
    $website = $_POST['website'];
    $lat = $_POST['lat'];
    $lng = $_POST['lng'];

    // 確保必填欄位非空
    if (empty($institution_id) || empty($institution_name) || empty($address) || empty($phone)) {
        handleError('請填寫所有必填欄位！');
    }
    
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

    // 更新 hospital 表中的資料
    $update_hospital_sql = "
        UPDATE `hospital` 
        SET 
            `institution_name` = '$institution_name',
            `institution_address` = '$address',
            `institution_phone` = '$phone'
        WHERE 
            `institution_id` = '$institution_id'
    ";

    if (!mysqli_query($link, $update_hospital_sql)) {
        handleError('更新 hospital 表資料失敗: ' . mysqli_error($link));
    }

    // 更新 institution 表中的資料
    $update_institution_sql = "
        UPDATE `institution` 
        SET 
            `institution_name` = '$institution_name',
            `county` = '$county',
            `town` = '$town',
            `address` = '$address',
            `phone` = '$phone',
            `person_charge` = '$person_charge',
            `website` = '$website',
            `lat` = '$lat',
            `lng` = '$lng'
        WHERE 
            `institution_id` = '$institution_id'
    ";

    if (!mysqli_query($link, $update_institution_sql)) {
        handleError('更新 institution 表資料失敗: ' . mysqli_error($link));
    }

    // 更新成功訊息並跳轉
    echo "<script>
        alert('機構資料已成功更新！');
        window.location.href = 'edit_institution_1.php?institution_id=$institution_id';
      </script>";
} else {
    handleError('無效的請求方式。');
}
?>