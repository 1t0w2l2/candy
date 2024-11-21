<?php
session_start();
include 'db.php'; // 確保包含你的資料庫連接

if (isset($_POST['institution_id'])) {
    $institution_id = $_POST['institution_id'];

    // 預防 SQL 注入
    $stmt = $link->prepare("SELECT * FROM `institution` WHERE `institution_id` = ?");
    $stmt->bind_param("s", $institution_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<span style='color: red;'>此醫療機構代碼已存在。</span>";
    } else {
        echo "<span style='color: green;'>此醫療機構代碼可用。</span>";
    }

    $stmt->close();
    $link->close();
}
?>