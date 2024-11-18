<?php
include "db.php";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'edit') {
    // 檢查是否傳遞了必要的欄位
    if (isset($_POST['plan_id'], $_POST['event_name'], $_POST['event_time'], $_POST['end_time'], $_POST['remark'])) {
        $plan_id = $_POST['plan_id'];
        $event_name = $_POST['event_name'];
        $event_time = $_POST['event_time'];
        $end_time = $_POST['end_time'];
        $remark = $_POST['remark'];
        
        // 檢查是否填寫所有必要欄位
        if (empty($event_name) || empty($event_time) || empty($end_time) || empty($remark)) {
            echo "error: 請填寫所有必要欄位";
            exit;
        }

        // 更新資料庫中的行程
        $sql = "UPDATE `plan` SET `event_name` = '".$event_name."', `event_time` = '".$event_time."', `end_time` = '".$end_time."', `remark` = '".$remark."' WHERE `plan_id` = '".$plan_id."';";

        // 執行更新並檢查是否成功
        if (mysqli_query($link, $sql)) {
            echo "success";  // 更新成功只回傳 success
        } else {
            echo "error: " . mysqli_error($link);  // 回傳具體的資料庫錯誤訊息
        }
    } else {
        echo "error: 欄位遺漏";  // 如果缺少必要的欄位，回傳錯誤
    }
    exit; // 確保沒有額外輸出
}


?>