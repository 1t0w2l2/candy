<?php 
require_once('db.php');

// 確認是否提供了 institution_id
if (isset($_GET['institution_id']) && !empty($_GET['institution_id'])) {
    $institution_id = $_GET['institution_id'];

    if (!$link) {
        die("資料庫連接失敗: " . mysqli_connect_error());
    }

    // 刪除 user 表中的資料 (不檢查 account)
    $delete_user_sql = "DELETE FROM `user` WHERE `account` IN (SELECT `account` FROM `hospital` WHERE `institution_id` = ?)";
    $stmt = mysqli_prepare($link, $delete_user_sql);
    if ($stmt === false) {
        die('刪除用戶資料準備失敗: ' . mysqli_error($link));
    }

    mysqli_stmt_bind_param($stmt, "i", $institution_id);
    if (!mysqli_stmt_execute($stmt)) {
        die('刪除用戶資料失敗: ' . mysqli_error($link));
    }
    mysqli_stmt_close($stmt);

    // 刪除 institution 表中的資料
    $delete_institution_sql = "DELETE FROM `institution` WHERE `institution_id` = ?";
    $stmt = mysqli_prepare($link, $delete_institution_sql);
    if ($stmt === false) {
        die('刪除機構資料準備失敗: ' . mysqli_error($link));
    }

    mysqli_stmt_bind_param($stmt, "i", $institution_id);
    if (!mysqli_stmt_execute($stmt)) {
        die('刪除機構資料失敗: ' . mysqli_error($link));
    }
    mysqli_stmt_close($stmt);

    // 刪除 hospital 表中的資料
    $delete_hospital_sql = "DELETE FROM `hospital` WHERE `institution_id` = ?";
    $stmt = mysqli_prepare($link, $delete_hospital_sql);
    if ($stmt === false) {
        die('刪除醫院資料準備失敗: ' . mysqli_error($link));
    }

    mysqli_stmt_bind_param($stmt, "i", $institution_id);
    if (!mysqli_stmt_execute($stmt)) {
        die('刪除醫院資料失敗: ' . mysqli_error($link));
    }
    mysqli_stmt_close($stmt);

    // 顯示成功訊息並跳轉回 landmark.php
    echo "<script>
            alert('機構資料已成功刪除！');
            window.location.href = 'landmark.php';
          </script>";
    
    // 關閉資料庫連接
    mysqli_close($link);
} else {
    // 未提供有效的 institution_id，顯示錯誤訊息並跳轉
    echo "<script>
            alert('未提供有效的 institution_id，請重試。');
            window.location.href = 'landmark.php';
          </script>";
}
?>