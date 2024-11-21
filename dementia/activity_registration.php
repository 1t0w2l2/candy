<?php
session_start();
include "db.php";

// 檢查用戶是否登入
$account = isset($_SESSION['account']) ? $_SESSION['account'] : '';
if (empty($account)) {
    header("Location: login.php");
    exit();
}


$account = mysqli_real_escape_string($link, $account);
// 查詢患者的報名紀錄
$patient_records = [];
$sql_patient = "
SELECT 
    a.activity_name, 
    a.start_time, 
    u.name, 
    r.status, 
    r.registration_id, 
    a.activity_id,
    r.account AS registration_account,
    j.account AS join_account
FROM 
    registration r 
JOIN 
    activity a ON r.activity_id = a.activity_id 
JOIN 
    join_activity j ON r.registration_id = j.registration_id 
JOIN 
    user u ON u.account = j.account 
WHERE 
    j.account = (SELECT account FROM patient WHERE account = '$account')";

// 執行查詢患者報名紀錄
$result_patient = mysqli_query($link, $sql_patient);
if ($result_patient) {
    while ($row = mysqli_fetch_assoc($result_patient)) {
        $patient_records[] = $row;
    }
} else {
    echo '查詢錯誤: ' . mysqli_error($link);
}

// 查詢照護者的報名紀錄
$caregiver_records = [];
$sql_caregiver = "
SELECT 
    a.activity_name, 
    a.start_time, 
    u.name, 
    r.status, 
    r.registration_id, 
    a.activity_id,
    r.account AS registration_account,
    j.account AS join_account
FROM 
    registration r 
JOIN 
    activity a ON r.activity_id = a.activity_id 
LEFT JOIN 
    join_activity j ON r.registration_id = j.registration_id 
LEFT JOIN 
    user u ON u.account = j.account 
WHERE 
    r.account = '$account' 
    OR r.registration_id IN (
        SELECT r2.registration_id 
        FROM patient_caregiver pc 
        JOIN join_activity j2 ON pc.caregiver_id = j2.account 
        JOIN registration r2 ON j2.registration_id = r2.registration_id 
        WHERE pc.patient_id = (SELECT patient_id FROM patient WHERE account = '$account')
    )";

// 執行查詢照護者報名紀錄
$result_caregiver = mysqli_query($link, $sql_caregiver);
if ($result_caregiver) {
    while ($row = mysqli_fetch_assoc($result_caregiver)) {
        // 檢查是否已在患者紀錄中存在
        $is_duplicate = false;
        foreach ($patient_records as $patient_record) {
            if ($patient_record['registration_id'] == $row['registration_id']) {
                $is_duplicate = true;
                break;
            }
        }
        // 只添加不重複的紀錄
        if (!$is_duplicate) {
            $caregiver_records[] = $row;
        }
    }
} else {
    echo '查詢錯誤: ' . mysqli_error($link);
}

// 合併報名紀錄
$all_records = array_merge($patient_records, $caregiver_records);

// 處理顯示邏輯：照護者幫患者報名時，兩邊都顯示，自己報名只顯示一筆
$final_records = [];
foreach ($all_records as $record) {
    // 確保只保留一筆自己的報名紀錄
    if ($record['registration_account'] === $account) {
        $exists = false;
        foreach ($final_records as $final_record) {
            if ($final_record['registration_id'] == $record['registration_id']) {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            $final_records[] = $record; // 只添加一筆
        }
    } else {
        // 如果是照護者幫患者報名，則添加所有相關紀錄
        $final_records[] = $record;
    }
}

// 查詢取消報名紀錄
$cancellation_records = [];
$sql_cancellations = "
SELECT 
    a.activity_name, 
    a.start_time,  
    u.name, 
    r.status, 
    r.registration_id 
FROM 
    registration r 
JOIN 
    activity a ON r.activity_id = a.activity_id 
JOIN
    join_activity j ON r.registration_id = j.registration_id
JOIN 
    user u ON u.account = j.account 
WHERE 
    (r.account = '$account' OR r.registration_id IN (
        SELECT registration_id 
        FROM join_activity 
        WHERE account = '$account'
    )) AND r.status = '取消報名'";

// 執行查詢取消報名紀錄
$result_cancellations = mysqli_query($link, $sql_cancellations);
if ($result_cancellations) {
    while ($row = mysqli_fetch_assoc($result_cancellations)) {
        $cancellation_records[] = $row;
    }
} else {
    echo '查詢錯誤: ' . mysqli_error($link);
}

// 處理取消報名
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_registration'])) {
    $registration_id = isset($_POST['registration_id']) ? $_POST['registration_id'] : '';

    if (!empty($registration_id)) {
        // 更新報名狀態為取消
        $sql_cancel = "UPDATE registration SET status = '取消報名' WHERE registration_id = '$registration_id' AND account = '$account'";
        if (mysqli_query($link, $sql_cancel)) {
            // 獲取活動名稱
            $sql_activity = "SELECT a.activity_name,h.account AS institution_account FROM activity a JOIN registration r ON a.activity_id = r.activity_id JOIN hospital h ON a.institution_id = h.institution_id WHERE r.registration_id = '$registration_id';";
            $result_activity = mysqli_query($link, $sql_activity);
            $activity = mysqli_fetch_assoc($result_activity);


            if ($activity) {
                $notification_content = "您已成功取消報名：" . htmlspecialchars($activity['activity_name']);
               
                // 發送通知給自己
                $sql_insert_notification = "INSERT INTO notification (account,send_account,notification_type, content, is_read) VALUES ('$account','$activity[institution_account]','activity' '$notification_content', 0)";
                mysqli_query($link, $sql_insert_notification);

                // 獲取活動參加者的帳號
                $sql_participant_accounts = "SELECT account FROM registration WHERE activity_id = (SELECT activity_id FROM registration WHERE registration_id = '$registration_id') AND account != '$account'";
                $result_participant_accounts = mysqli_query($link, $sql_participant_accounts);

                // 發送通知給其他參加者，確保不重複發送
                while ($participant = mysqli_fetch_assoc($result_participant_accounts)) {
                    $participant_account = $participant['account'];
                    $notification_content_for_participant = "帳號：$account 已取消報名活動：" . htmlspecialchars($activity['activity_name']);

                    // 檢查是否已發送通知
                    $sql_check_notification = "SELECT * FROM notification WHERE account = '$participant_account' AND content = '$notification_content_for_participant'";
                    $result_check = mysqli_query($link, $sql_check_notification);

                    if (mysqli_num_rows($result_check) === 0) {
                        // 將通知插入到資料庫
                        $sql_insert_participant_notification = "INSERT INTO notification (account, send_account,notification_type,content, is_read) VALUES ('$participant_account','$activity[institution_account]','activity','$notification_content_for_participant', 0)";
                        mysqli_query($link, $sql_insert_participant_notification);
                    }
                }

                // 刪除活動報名的紀錄
                $sql_join = "DELETE FROM `join_activity` WHERE `registration_id`='$registration_id'";
                mysqli_query($link, $sql_join);

                echo "<script>alert('取消報名成功，並已發送通知');window.location.href = 'activity_registration.php';</script>";
            } else {
                echo "<script>alert('活動不存在');</script>";
                exit();
            }
        } else {
            echo "<script>alert('取消失敗: " . mysqli_error($link) . "');</script>";
        }
    } else {
        echo "<script>alert('報名 ID 無效');</script>";
    }
}

$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$all_records = [];
$cancellation_records = [];

// 獲取所有報名紀錄
function fetchAllRecords($link, $account) {
    $query = "
        SELECT 
            a.activity_name, 
            a.start_time,  
            u.name, 
            r.status, 
            r.registration_id, 
            r.account AS registration_account 
        FROM 
            registration r 
        JOIN 
            activity a ON r.activity_id = a.activity_id 
        JOIN 
            join_activity j ON r.registration_id = j.registration_id 
        JOIN 
            user u ON u.account = j.account 
        WHERE 
            (r.account = '$account' OR r.registration_id IN (
                SELECT registration_id 
                FROM join_activity 
                WHERE account = '$account'
            ))";

    return mysqli_query($link, $query);
}

// 根據查詢條件獲取報名紀錄
function fetchRecords($link, $account, $keyword, $start_date) {
    $query = "
        SELECT 
            a.activity_name, 
            a.start_time,  
            u.name, 
            r.status, 
            r.registration_id, 
            r.account AS registration_account 
        FROM 
            registration r 
        JOIN 
            activity a ON r.activity_id = a.activity_id 
        JOIN 
            join_activity j ON r.registration_id = j.registration_id 
        JOIN 
            user u ON u.account = j.account 
        WHERE 
            (r.account = '$account' OR r.registration_id IN (
                SELECT registration_id 
                FROM join_activity 
                WHERE account = '$account'
            ))";

    if ($keyword) {
        $query .= " AND (a.activity_name LIKE '%$keyword%' OR u.name LIKE '%$keyword%')";
    }
    if ($start_date) {
        $query .= " AND a.start_time >= '$start_date'";
    }

    return mysqli_query($link, $query);
}

// 初始獲取所有報名紀錄
$all_records_result = fetchAllRecords($link, $account);
if ($all_records_result) {
    while ($row = mysqli_fetch_assoc($all_records_result)) {
        $all_records[] = $row;
    }
}

// 根據查詢條件獲取報名紀錄
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $result = fetchRecords($link, $account, $keyword, $start_date);
    if ($result) {
        $filtered_records = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $filtered_records[] = $row;
        }
    } else {
        echo '查詢錯誤: ' . mysqli_error($link);
    }
}
?>

<!doctype html>
<html lang="en">

<head>
    <?php include 'head.php'; ?>
</head>

<body>

<?php include "nav.php"; ?>
    <div class="s4-container">
    <div class="filter-section">
        <h2 style="text-align: center;">報名紀錄查詢</h2>
        <form method="GET" action="">
            <input type="text" name="keyword" placeholder="請輸入關鍵字" value="<?php echo htmlspecialchars($keyword); ?>">
            <input type="date" name="start_date" placeholder="活動日期" value="<?php echo htmlspecialchars($start_date); ?>">
            <button type="submit">
                <i class="fa fa-search"></i> 搜尋
            </button>
        </form>
    </div>
    <div id="registrationRecords">
        <div class="container">
            <ul class="nav nav-tabs" id="tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tab1" data-bs-toggle="tab" data-bs-target="#content1" type="button" role="tab" aria-controls="content1" aria-selected="true">報名成功</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab2" data-bs-toggle="tab" data-bs-target="#content2" type="button" role="tab" aria-controls="content2" aria-selected="false">取消報名</button>
                </li>
            </ul>
            <div class="tab-content" id="tabContent">
                <!-- 報名成功的內容 -->
                <div class="tab-pane fade show active" id="content1" role="tabpanel" aria-labelledby="tab1">
                    <?php if (!empty($filtered_records)): ?>
                        <table class="s3-table">
                            <tr>
                                <th class="s3-th">#</th>
                                <th class="s3-th">活動名稱</th>
                                <th class="s3-th">活動時間</th>
                                <th class="s3-th">姓名</th>
                                <th class="s3-th">狀態</th>
                                <th class="s3-th">取消</th>
                            </tr>
                            <?php foreach ($filtered_records as $index => $record): ?>
                                <?php if ($record['status'] === '已報名'): ?>
                                    <tr>
                                        <td class="s3-td"><?php echo $index + 1; ?></td>
                                        <td class="s3-td"><?php echo htmlspecialchars($record['activity_name']); ?></td>
                                        <td class="s3-td"><?php echo htmlspecialchars($record['start_time']); ?></td>
                                        <td class="s3-td"><?php echo htmlspecialchars($record['name']); ?></td>
                                        <td class="s3-td"><?php echo htmlspecialchars($record['status']); ?></td>
                                        <td class="s3-td">
                                        <?php if ($record['registration_account'] === $account ): ?>
                                                <form action="" method="POST" >
                                                    <input type="hidden" name="registration_id" value="<?php echo htmlspecialchars($record['registration_id']); ?>">
                                                    <button type="submit" name="cancel_registration" class="s3-button" onclick="return confirm('確定要取消報名嗎？');">
                                                        <i class="fa-regular fa-square-minus"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </table>
                    <?php else: ?>
                        <div class="text-center">尚無報名紀錄</div>
                    <?php endif; ?>
                </div>

                <!-- 取消報名的內容 -->
                <div class="tab-pane fade" id="content2" role="tabpanel" aria-labelledby="tab2">
                    <?php
                    // 獲取取消報名紀錄
                    $sql_cancellations = "
                        SELECT a.activity_name, a.start_time, u.name, r.status, r.registration_id 
                        FROM registration r 
                        JOIN activity a ON r.activity_id = a.activity_id 
                        JOIN user u ON u.account = r.account 
                        WHERE r.account = '$account' AND r.status = '取消報名'";

                    $result_cancellations = mysqli_query($link, $sql_cancellations);
                    if ($result_cancellations) {
                        if (mysqli_num_rows($result_cancellations) > 0) {
                            echo '<table class="s3-table">';
                            echo '<tr><th class="s3-th">#</th><th class="s3-th">活動名稱</th><th class="s3-th">活動時間</th><th class="s3-th">報名者</th><th class="s3-th">狀態</th></tr>';
                            $index = 1;
                            while ($row = mysqli_fetch_assoc($result_cancellations)) {
                                echo '<tr>';
                                echo '<td class="s3-td">' . $index++ . '</td>';
                                echo '<td class="s3-td">' . htmlspecialchars($row['activity_name']) . '</td>';
                                echo '<td class="s3-td">' . htmlspecialchars($row['start_time']) . '</td>';
                                echo '<td class="s3-td">' . htmlspecialchars($row['name']) . '</td>';
                                echo '<td class="s3-td">' . htmlspecialchars($row['status']) . '</td>';
                                echo '</tr>';
                            }
                            echo '</table>';
                        } else {
                            echo '<div class="text-center">尚無取消報名的紀錄</div>';
                        }
                    } else {
                        echo '查詢錯誤: ' . mysqli_error($link);
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

</body>

</html>