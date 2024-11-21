<?php
session_start();
include "db.php";

$account = isset($_SESSION['account']) ? $_SESSION['account'] : '';
if (empty($account)) {
    header("Location: login.php");
    exit();
}

// 獲取活動 ID
$activity_id = isset($_POST['activity_id']) ? intval($_POST['activity_id']) : (isset($_GET['activity_id']) ? intval($_GET['activity_id']) : 0);

// 獲取用戶類型
$sql_user_type = "SELECT user_type FROM user WHERE account = '$account'";
$result_user_type = mysqli_query($link, $sql_user_type);
if (!$result_user_type) {
    echo "<script>alert('查詢使用者類型失敗: " . mysqli_error($link) . "');</script>";
    exit();
}
$user = mysqli_fetch_assoc($result_user_type);
if (!$user) {
    echo "<script>alert('查無此帳號');</script>";
    exit();
}
$role = $user['user_type'];

// 獲取用戶姓名
$sql_user_name = "SELECT name FROM user WHERE account = '$account'";
$result_user_name = mysqli_query($link, $sql_user_name);
$user_data = mysqli_fetch_assoc($result_user_name);
$user_name = $user_data ? $user_data['name'] : '';

// 獲取綁定的患者
$binded_patients = [];
if ($role === 'caregiver') {
    $sql_caregiver_id = "SELECT caregiver_id FROM caregiver WHERE account = '$account'";
    $result_caregiver_id = mysqli_query($link, $sql_caregiver_id);
    $caregiver_data = mysqli_fetch_assoc($result_caregiver_id);

    if ($caregiver_data) {
        $caregiver_id = $caregiver_data['caregiver_id'];

        $sql_patient = "SELECT p.account, u.name, MAX(r.status) AS status 
                FROM patient_caregiver pc
                JOIN patient p ON pc.patient_id = p.patient_id
                JOIN user u ON p.account = u.account
                LEFT JOIN registration r ON r.activity_id = '$activity_id' AND r.account = p.account
                WHERE pc.caregiver_id = '$caregiver_id'
                GROUP BY p.account, u.name";
;
        $result_patient = mysqli_query($link, $sql_patient);
        while ($patient = mysqli_fetch_assoc($result_patient)) {
            $binded_patients[] = [
                'account' => $patient['account'],
                'name' => $patient['name'],  // 添加患者姓名
                'registered' => $patient['status'] === '已報名'
            ];
        }
    }
}


// 獲取活動資訊
$sql_activity = "SELECT * FROM activity WHERE activity_id = '$activity_id'";
$result_activity = mysqli_query($link, $sql_activity);
$activity = mysqli_fetch_assoc($result_activity);
$activity_exists = !empty($activity);

// 獲取問題與選項
$questions = [];
if ($activity_exists) {
    $sql_questions = "SELECT * FROM questions WHERE activity_id = $activity_id";
    $result_questions = mysqli_query($link, $sql_questions);

    while ($row = mysqli_fetch_assoc($result_questions)) {
        $questions_id = $row['questions_id'];

        $sql_options = "SELECT * FROM options WHERE questions_id = $questions_id";
        $result_options = mysqli_query($link, $sql_options);
        $options = [];
        while ($options_row = mysqli_fetch_assoc($result_options)) {
            $options[] = [
                'options_text' => $options_row['options_text'],
                'options_type' => $options_row['options_type'],
            ];
        }

        $questions[] = [
            'questions_text' => $row['questions_text'],
            'options' => $options,
        ];
    }
}

$selected_patient_account = isset($_POST['patient_account']) ? $_POST['patient_account'] : '';


// 處理報名
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'addregistration') {
    if (!$activity_exists) {
        echo "<script>alert('活動不存在'); window.location.href='activity.php';</script>";
        exit();
    }

    // Step 1: 從活動表中取得醫療機構代號
    $sql_activity = "SELECT institution_id FROM activity WHERE activity_id = '$activity_id'";
    $result_activity = mysqli_query($link, $sql_activity);
    if (!$result_activity) {
        echo "<script>alert('查詢活動失敗: " . mysqli_error($link) . "');</script>";
        exit();
    }
    $activity_data = mysqli_fetch_assoc($result_activity);
    $medical_institution_id = $activity_data['institution_id'];

    // Step 2: 使用醫療機構代號去查找醫療機構帳號
    $sql_medical_account = "SELECT account FROM hospital WHERE institution_id = '$medical_institution_id'";
    $result_medical_account = mysqli_query($link, $sql_medical_account);
    if (!$result_medical_account) {
        echo "<script>alert('查詢醫療機構帳號失敗: " . mysqli_error($link) . "');</script>";
        exit();
    }
    $medical_account_data = mysqli_fetch_assoc($result_medical_account);
    $medical_account = $medical_account_data['account'];

    // 設定報名與參加者帳號
    $registration_account = $account; // 默認報名帳號
    $participant_account = $account; // 默認參加者帳號

    // 如果是照顧者，報名帳號為照顧者，參加者帳號為所選的患者帳號
    if ($role === 'caregiver' && !empty($selected_patient_account)) {
        $participant_account = $selected_patient_account;
    }

    // 檢查是否已經報名過
    $sql_check_registration = "SELECT * FROM join_activity WHERE activity_id = '$activity_id' AND account = '$participant_account'";
    $result_check = mysqli_query($link, $sql_check_registration);
    if (!$result_check) {
        echo "<script>alert('查詢報名狀態失敗: " . mysqli_error($link) . "');</script>";
        exit();
    }
    $registration_data = mysqli_fetch_assoc($result_check);

    // 如果已報名且未取消，則提示
    if ($registration_data && $registration_data['status'] !== '取消報名') {
        echo "<script>alert('您已經報名過此活動！'); window.location.href='registration.php?activity_id=$activity_id';</script>";
        exit();
    }

    // 新增報名
    $sql_registration = "INSERT INTO registration (activity_id, account, status) VALUES ('$activity_id', '$registration_account', '已報名')";
    if (mysqli_query($link, $sql_registration)) {
        $registration_id = mysqli_insert_id($link);

        // 儲存參加者資訊
        $sql_join = "INSERT INTO join_activity (account, activity_id, registration_id, attended) VALUES ('$participant_account', '$activity_id', '$registration_id', NULL)";
        mysqli_query($link, $sql_join);

        // 儲存問題答案
        foreach ($_POST['answer'] as $questions_text => $answer) {
            $sql_questions = "SELECT questions_id FROM questions WHERE questions_text = '" . mysqli_real_escape_string($link, $questions_text) . "' AND activity_id = $activity_id";
            $result_questions = mysqli_query($link, $sql_questions);
            if (!$result_questions) {
                echo "<script>alert('查詢問題 ID 失敗: " . mysqli_error($link) . "');</script>";
                exit();
            }
            $questions = mysqli_fetch_assoc($result_questions);

            if ($questions) {
                $questions_id = $questions['questions_id'];

                // 如果答案是多選
                if (is_array($answer)) {
                    foreach ($answer as $options_text) {
                        $sql_options = "SELECT options_id, options_type FROM options WHERE options_text = '" . mysqli_real_escape_string($link, $options_text) . "' AND questions_id = $questions_id";
                        $result_options = mysqli_query($link, $sql_options);
                        if (!$result_options) {
                            echo "<script>alert('查詢選項 ID 失敗: " . mysqli_error($link) . "');</script>";
                            exit();
                        }
                        $options = mysqli_fetch_assoc($result_options);
                        if ($options) {
                            $options_id = $options['options_id'];
                            $sql_responses = "INSERT INTO responses (account, questions_id, options_id, registration_id, activity_id) VALUES ('$registration_account', '$questions_id', '$options_id', '$registration_id', '$activity_id')";
                            mysqli_query($link, $sql_responses);
                        }
                    }
                } else {
                    // 單選或文本回答
                    $sql_options = "SELECT options_id, options_type FROM options WHERE options_text = '" . mysqli_real_escape_string($link, $answer) . "' AND questions_id = $questions_id";
                    $result_options = mysqli_query($link, $sql_options);
                    if (!$result_options) {
                        echo "<script>alert('查詢選項 ID 失敗: " . mysqli_error($link) . "');</script>";
                        exit();
                    }
                    $options = mysqli_fetch_assoc($result_options);

                    if ($options) {
                        $options_id = $options['options_id'];
                        $sql_responses = "INSERT INTO responses (account, questions_id, options_id, registration_id, activity_id) VALUES ('$registration_account', '$questions_id', '$options_id', '$registration_id', '$activity_id')";
                        mysqli_query($link, $sql_responses);
                    } else {
                        // 插入文本回答
                        $answer_text = mysqli_real_escape_string($link, $answer);
                        $sql_responses = "INSERT INTO responses (account, questions_id, answer_text, registration_id, activity_id) VALUES ('$registration_account', '$questions_id', '$answer_text', '$registration_id', '$activity_id')";
                        mysqli_query($link, $sql_responses);
                    }
                }
            }
        }
        $sql_activity_details = "SELECT start_time, end_time,activity_name FROM activity WHERE activity_id = '$activity_id'";
        $result_activity_details = mysqli_query($link, $sql_activity_details);
        if (!$result_activity_details) {
            echo "<script>alert('查詢活動詳細資料失敗: " . mysqli_error($link) . "');</script>";
            exit();
        }
        $activity_details = mysqli_fetch_assoc($result_activity_details);
        
        // Step 2: 取得活動的起始和結束時間
       // 取得活動的起始和結束時間
        $date = date('Y-m-d', strtotime($activity_details['start_time'])); // 行程日期
        $event_time = date('H:i', strtotime($activity_details['start_time'])); // 假設使用活動的開始時間
        $end_time = date('H:i', strtotime($activity_details['end_time'])); // 假設使用活動的結束時間
        $event_name = htmlspecialchars($activity['activity_name']);
        
        $sql_plan = "INSERT INTO plan (account, edit_account, event_type, event_name, date, event_time, end_time, created_at, updated_at, remark) 
                     VALUES ('$registration_account', '$registration_account', '活動提醒', '$event_name', '$date', '$event_time', '$end_time', NOW(), NOW(), '無')";
        
        if (!mysqli_query($link, $sql_plan)) {
            echo "<script>alert('新增行程失敗: " . mysqli_error($link) . "');</script>";
        }
        // 通知用戶
        $notification_content = "您已成功報名活動： " . htmlspecialchars($activity['activity_name']);
        if($registration_account == $participant_account){
           $sql_insert_notification = "INSERT INTO notification (account, send_account,notification_type, content, is_read) VALUES ('$registration_account', '$medical_account','activity', '" . mysqli_real_escape_string($link, $notification_content) . "', 0)";
        }
        elseif ($registration_account != $participant_account) {
            $sql_insert_notification = "INSERT INTO notification (account, send_account,notification_type, content, is_read) VALUES 
                                        ('$registration_account', '$medical_account', 'activity','" . mysqli_real_escape_string($link, $notification_content) . "', 0),
                                        ('$participant_account', '$medical_account', 'activity','" . mysqli_real_escape_string($link, $notification_content) . "', 0)";
        }
        mysqli_query($link, $sql_insert_notification);
        echo "<script>alert('報名成功！'); window.location.href='activity.php';</script>";
    } else {
        echo "<script>alert('報名失敗: " . mysqli_error($link) . "');</script>";
    }
}




// 獲取報名人數上限和目前報名人數
$sql_max_participants = "SELECT max_participants FROM activity WHERE activity_id = '$activity_id'";
$result_max_participants = mysqli_query($link, $sql_max_participants);
$activity_info = mysqli_fetch_assoc($result_max_participants);
$max_participants = $activity_info['max_participants'];

$sql_current_participants = "SELECT COUNT(*) as current_count FROM registration WHERE activity_id = '$activity_id' AND status = '已報名'";
$result_current_participants = mysqli_query($link, $sql_current_participants);
$current_info = mysqli_fetch_assoc($result_current_participants);
$current_count = $current_info['current_count'];

$registration_closed = ($current_count >= $max_participants);
?>


<!doctype html>
<html lang="en">

<head>
    <?php include 'head.php'; ?>
</head>

<body>
<?php include "nav.php"; ?>

<!-- 判斷帳號是否被鎖 -->
<?php
// 假設已經有連線 $link 以及變數 $account 和 $activity_id 被設定

$escaped_account = mysqli_real_escape_string($link, $account);

// 查詢該帳號的未到次數與最新的鎖定日期
$sql_count = "
SELECT 
    COUNT(*) AS attended_count, 
    (SELECT activity_account_date 
     FROM join_activity 
     WHERE account = '$escaped_account' 
     ORDER BY activity_account_date ASC 
     LIMIT 1) AS activity_account_date
FROM 
    join_activity 
WHERE 
    account = '$escaped_account' 
    AND attended = 0;";

$result_count = mysqli_query($link, $sql_count);

if (!$result_count) {
    echo "<p class='text-danger'>查詢失敗: " . mysqli_error($link) . "</p>";
} else {
    $row = mysqli_fetch_assoc($result_count);
    $attended_count = $row['attended_count'];
    $activity_account_date = $row['activity_account_date']; 
}

// 計算是否達到鎖定條件
if ($attended_count > 0 && $attended_count % 3 == 0 && empty($activity_account_date)) {
    // 鎖定帳號 30 天
    $locked_date = date('Y-m-d', strtotime('+30 days'));

    // 更新 join_activity 表中的 activity_account_date 欄位，將鎖定日期寫入
    $sql_update_lock = "UPDATE join_activity SET activity_account_date = '$locked_date' WHERE account = '" . mysqli_real_escape_string($link, $account) . "' ORDER BY `registration_id` DESC LIMIT 1;";

    if (!mysqli_query($link, $sql_update_lock)) {
        echo "<p class='text-danger'>更新鎖定日期失敗: " . mysqli_error($link) . "</p>";
    }

    // 顯示活動資訊和鎖定訊息
    ?>
    <div class="container">
        <div class="activity-info2" style="margin-top: 85px;">
            <h1>活動訊息</h1>
            <h2><strong>活動名稱:</strong> <?php echo htmlspecialchars($activity['activity_name']); ?></h2>
            <p><strong>活動敘述:</strong> <?php echo htmlspecialchars($activity['description']); ?></p>
            <p><strong>起訖時間:</strong> <?php echo htmlspecialchars($activity['start_time']); ?> ~
                <?php echo htmlspecialchars($activity['end_time']); ?>
            </p>
            <p><strong>地點:</strong> <?php echo htmlspecialchars($activity['location']); ?></p>
            <p><strong>報名截止時間:</strong> <?php echo htmlspecialchars($activity['registration_deadline']); ?></p>
            <p><strong>報名人數上限:</strong> <?php echo htmlspecialchars($activity['max_participants']); ?></p>
            <p><strong>聯絡人:</strong> <?php echo htmlspecialchars($activity['contact_person']); ?></p>
            <p><strong>連絡電話:</strong> <?php echo htmlspecialchars($activity['contact_phone']); ?></p>
        </div>

        <!-- 顯示鎖定提示訊息 -->
        <div class="registration-form container mt-5">
        <p class="text-danger">您的帳號已被鎖定 30 天，請<?php echo "$locked_date"; ?>再進行報名。</p>
        </div>
    </div>
    <?php
} elseif (!empty($activity_account_date) && strtotime($activity_account_date) > time()) {
    // 如果帳號被鎖定且未超過鎖定日期
    ?>
    <div class="container">
        <div class="activity-info2" style="margin-top: 85px;">
            <h1>活動訊息</h1>
            <h2><strong>活動名稱:</strong> <?php echo htmlspecialchars($activity['activity_name']); ?></h2>
            <p><strong>活動敘述:</strong> <?php echo htmlspecialchars($activity['description']); ?></p>
            <p><strong>起訖時間:</strong> <?php echo htmlspecialchars($activity['start_time']); ?> ~
                <?php echo htmlspecialchars($activity['end_time']); ?>
            </p>
            <p><strong>地點:</strong> <?php echo htmlspecialchars($activity['location']); ?></p>
            <p><strong>報名截止時間:</strong> <?php echo htmlspecialchars($activity['registration_deadline']); ?></p>
            <p><strong>報名人數上限:</strong> <?php echo htmlspecialchars($activity['max_participants']); ?></p>
            <p><strong>聯絡人:</strong> <?php echo htmlspecialchars($activity['contact_person']); ?></p>
            <p><strong>連絡電話:</strong> <?php echo htmlspecialchars($activity['contact_phone']); ?></p>
        </div>

        <!-- 顯示鎖定提示訊息 -->
        <div class="registration-form container mt-5">
            <p class="text-danger">您的帳號已被鎖定，尚未到解鎖時間。</p>
        </div>
    </div>
    <?php
} else {
    // 如果帳號未被鎖定或鎖定已過期，顯示活動資訊和報名區域
    ?>
    <div class="container">
        <div class="activity-info2" style="margin-top: 85px;">
            <h1>活動訊息</h1>
            <h2><strong>活動名稱:</strong> <?php echo htmlspecialchars($activity['activity_name']); ?></h2>
            <p><strong>活動敘述:</strong> <?php echo htmlspecialchars($activity['description']); ?></p>
            <p><strong>起訖時間:</strong> <?php echo htmlspecialchars($activity['start_time']); ?> ~
                <?php echo htmlspecialchars($activity['end_time']); ?>
            </p>
            <p><strong>地點:</strong> <?php echo htmlspecialchars($activity['location']); ?></p>
            <p><strong>報名截止時間:</strong> <?php echo htmlspecialchars($activity['registration_deadline']); ?></p>
            <p><strong>報名人數上限:</strong> <?php echo htmlspecialchars($activity['max_participants']); ?></p>
            <p><strong>聯絡人:</strong> <?php echo htmlspecialchars($activity['contact_person']); ?></p>
            <p><strong>連絡電話:</strong> <?php echo htmlspecialchars($activity['contact_phone']); ?></p>
        </div>

        <!-- 顯示報名區域 -->
        <div class="registration-form container mt-5">
            <h2 class="mb-4">活動報名</h2>

            <?php if ($registration_closed): ?>
                <p class="text-danger">報名已達上限，無法再報名此活動。</p>
            <?php else: ?>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?activity_id=<?php echo htmlspecialchars($activity_id); ?>" method="POST">
                    <?php if ($role === 'caregiver'): ?>
                        <div class="mb-4">
                            <label for="patientSelect" class="form-label">選擇綁定的患者報名</label>
                            <button type="button" class="s3-button1" tabindex="0" role="button" data-bs-toggle="popover"
                            data-bs-trigger="focus" title="綁定帳號說明" data-bs-html="true" 
                            data-bs-content="<div><p>如要幫患者報名，請先至個人資料頁面進行帳號綁定，才可幫綁定患者報名活動。</p></div>">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            </button>
                            <select class="form-select" id="patientSelect" name="patient_account" onchange="updatePatientName(); this.form.submit();">
                                <option value="">請選擇患者</option>
                                <?php foreach ($binded_patients as $patient): ?>
                                    <option value="<?php echo htmlspecialchars($patient['account']); ?>"
                                            data-name="<?php echo htmlspecialchars($patient['name']); ?>"
                                            data-registered="<?php echo $patient['registered'] ? 'true' : 'false'; ?>"
                                            <?php echo (isset($selected_patient_account) && $selected_patient_account === $patient['account']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($patient['account']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <?php
                        // 檢查選定患者是否已報名
                        $selected_patient_account = isset($_POST['patient_account']) ? $_POST['patient_account'] : '';
                        if (!empty($selected_patient_account)):
                            // 以患者的帳號檢查報名狀態
                            $sql_check_registration = "SELECT * FROM join_activity WHERE account = '" . mysqli_real_escape_string($link, $selected_patient_account) . "' AND activity_id = '$activity_id'";
                            $result_check = mysqli_query($link, $sql_check_registration);
                            

                            if (!$result_check) {
                                echo "<p class='text-danger'>查詢失敗: " . mysqli_error($link) . "</p>";
                            } else {
                                // 如果查詢成功，檢查結果行數
                                if (mysqli_num_rows($result_check) > 0): ?>
                                    <p class="text-danger">該帳號已報名此活動。</p>
                                <?php else: ?>
                                    <p class="text-success">該帳號尚未報名。</p>
                                <?php endif;
                            }
                        endif; 
                        ?>

                    <?php endif; ?>
                    <?php if (empty($questions)): ?>
                        <p class="text-center text-danger">尚無問題</p>
                    <?php else: ?>
                        <?php foreach ($questions as $question): ?>
                            <div class="mb-4 p-3 border rounded shadow-sm bg-light">
                                <p class="font-weight-bold questions-text" style="font-size:20px;">
                                    <?php echo htmlspecialchars($question['questions_text']); ?>
                                </p>
                                <?php if ($question['questions_text'] === '姓名'): ?>
                                    <input type="text" class="form-control" id="patientNameInput" name="answer[姓名]" 
                                    value="<?php echo htmlspecialchars($selected_patient_account ? $binded_patients[array_search($selected_patient_account, array_column($binded_patients, 'account'))]['name'] : $user_name); ?>" 
                                    readonly placeholder="請輸入您的姓名">
                                <?php elseif (isset($question['options']) && count($question['options']) > 0): ?>
                                    <?php foreach ($question['options'] as $index => $option): ?>
                                        <div class="form-check">
                                            <?php if ($option['options_type'] === 'radio'): ?>
                                                <input type="radio" class="form-check-input" id="option_<?php echo $index; ?>" name="answer[<?php echo htmlspecialchars($question['questions_text']); ?>]" value="<?php echo htmlspecialchars($option['options_text']); ?>" required>
                                                <label class="form-check-label" for="option_<?php echo $index; ?>"><?php echo htmlspecialchars($option['options_text']); ?></label>
                                            <?php elseif ($option['options_type'] === 'checkbox'): ?>
                                                <input type="checkbox" class="form-check-input" id="checkbox_option_<?php echo $index; ?>" name="answer[<?php echo htmlspecialchars($question['questions_text']); ?>][]" value="<?php echo htmlspecialchars($option['options_text']); ?>">
                                                <label class="form-check-label" for="checkbox_option_<?php echo $index; ?>"><?php echo htmlspecialchars($option['options_text']); ?></label>
                                            <?php elseif ($option['options_type'] === 'text'): ?>
                                                <input type="text" class="form-control" name="answer[<?php echo htmlspecialchars($question['questions_text']); ?>]" placeholder="請輸入您的回答" required>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <button type="submit" class="btn btn-primary btn-block" name="action" value="addregistration">送出報名</button>
                    <?php endif; ?>
                </form>
            <?php endif; ?>
        </div>
    </div>
<?php } ?>




 <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
 <script>
function updatePatientName() {
    var select = document.getElementById("patientSelect");
    var patientNameInput = document.getElementById("patientNameInput");
    var selectedOption = select.options[select.selectedIndex];

    if (selectedOption.value) {
        patientNameInput.value = selectedOption.getAttribute("data-name");
    } else {
        patientNameInput.value = "<?php echo htmlspecialchars($user_name); ?>";
    }
}

document.addEventListener('DOMContentLoaded', function () {
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
});
</script>





</body>

</html>