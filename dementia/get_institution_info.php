<?php
include 'db.php';

if (isset($_POST['institution_id'])) {
    $institution_id = $_POST['institution_id'];

    // 查詢 hospital 與 institution 資料
    $sql = "SELECT h.*, i.person_charge, i.website 
            FROM hospital h 
            JOIN institution i ON h.institution_id = i.institution_id 
            WHERE h.institution_id = ?";
    $stmt = $link->prepare($sql);
    $stmt->bind_param("s", $institution_id);
    $stmt->execute();
    $result = $stmt->get_result();

    echo "<table id='modal-table' class='table table-bordered'><tbody>";

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo '<tr><th>醫療機構代碼</th><td>' . htmlspecialchars($row['institution_id']) . '</td></tr>';
        echo '<tr><th>醫療機構名稱</th><td>' . htmlspecialchars($row['institution_name']) . '</td></tr>';
        echo '<tr><th>醫療機構地址</th><td>' . htmlspecialchars($row['institution_address']) . '</td></tr>';
        echo '<tr><th>醫療機構電話</th><td>' . htmlspecialchars($row['institution_phone']) . '</td></tr>';
        echo '<tr><th>帳號</th><td>' . htmlspecialchars($row['account']) . '</td></tr>';
        echo '<tr><th>負責人</th><td>' . htmlspecialchars($row['person_charge']) . '</td></tr>';
        echo '<tr><th>網站</th><td><a href="' . htmlspecialchars($row['website']) . '" target="_blank">' . htmlspecialchars($row['website']) . '</a></td></tr>';
    } else {
        echo '<tr><td colspan="2">無法找到醫療機構的詳細資訊。</td></tr>';
    }
    echo "</tbody></table>";

    // 顯示 institution 圖片
    if (!empty($row['institution_img'])) {
        echo '<div style="margin-top: 20px;">';
        echo '<h5>開業執照或評鑑合格證明書-相關證明</h5>';
        echo '<img src="' . htmlspecialchars($row['institution_img']) . '" alt="醫療機構圖片" style="max-width: 100%; height: auto;"/>';
        echo '</div>';
    } else {
        echo '<div style="margin-top: 20px;">';
        echo '<h5>開業執照或評鑑合格證明書-相關證明</h5>';
        echo '無圖片可顯示。';
        echo '</div>';
    }

    // 查詢 servicetime 多筆資料
    $sql_service = "SELECT * FROM servicetime WHERE institution_id = ?";
    $stmt_service = $link->prepare($sql_service);
    $stmt_service->bind_param("s", $institution_id);
    $stmt_service->execute();
    $result_service = $stmt_service->get_result();
    echo "<h5 style='margin-top: 20px;'>營業時間</h5>";
    echo "<table class='table table-bordered'><thead><tr><th>營業日</th><th>營業時間</th></tr></thead><tbody>";

    if ($result_service->num_rows > 0) {
        while ($row_service = $result_service->fetch_assoc()) {
            // 預設營業時間顯示
            $time_display = htmlspecialchars($row_service['open_time']) . ' - ' . htmlspecialchars($row_service['close_time']);

            // 根據條件調整顯示內容
            if ($row_service['open_time'] == '00:00:00' && $row_service['close_time'] == '24:00:00') {
                $time_display = '24小時';
            } elseif ($row_service['open_time'] == '00:00:00' && $row_service['close_time'] == '00:00:00') {
                $time_display = '休息';
            }

            // 顯示營業日與營業時間
            echo '<tr><td>' . htmlspecialchars($row_service['day']) . '</td>';
            echo '<td>' . $time_display . '</td></tr>';
        }
    } else {
        echo '<tr><td colspan="2">無法找到服務時間的詳細資訊。</td></tr>';
    }

    echo "</tbody></table>";

    $stmt->close();
    $stmt_service->close();
}
$link->close();
?>