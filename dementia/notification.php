<?php
session_start(); // 確保 session 已啟動
include "db.php";

error_reporting(E_ALL);
ini_set('display_errors', 1);

$account = isset($_SESSION['account']) ? $_SESSION['account'] : '';
if (empty($account)) {
    header("Location: login.php");
    exit();
}

// 獲取用戶的所有通知
$sql = "SELECT * FROM notification WHERE account = '" . mysqli_real_escape_string($link, $account) . "' ORDER BY time DESC";
$result = mysqli_query($link, $sql);
if (!$result) {
    echo "<script>alert('資料庫查詢失敗: " . mysqli_error($link) . "');</script>";
}
$notifications = mysqli_fetch_all($result, MYSQLI_ASSOC);

// AJAX 處理
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['notification_id'])) {
    $notification_id = mysqli_real_escape_string($link, $_POST['notification_id']);
    $action = $_POST['action'];

    if ($action == 'read') {
        // 更新通知為已讀
        $sql_update = "UPDATE notification SET is_read = 1 WHERE notification_id = '$notification_id'";
        if (!mysqli_query($link, $sql_update)) {
            echo "<script>alert('更新通知失敗: " . mysqli_error($link) . "');</script>";
        }
        exit(); // 結束腳本執行
    }

    // 獲取通知發送者的帳號
    $sql_sender = "SELECT account, send_account, notification_type FROM notification WHERE notification_id = '$notification_id'";
    $result_sender = mysqli_query($link, $sql_sender);
    if (!$result_sender || mysqli_num_rows($result_sender) == 0) {
        echo "<script>alert('獲取發送者帳號失敗: " . mysqli_error($link) . "');</script>";
        exit();
    }

    $sender_info = mysqli_fetch_assoc($result_sender);
    $sender = $sender_info['account'];
    $send_account = $sender_info['send_account'];
    $notification_type = $sender_info['notification_type'];

    // 獲取發送者的用戶類型
    $sql_usertype = "SELECT user_type FROM user WHERE account = '" . mysqli_real_escape_string($link, $send_account) . "'";
    $result_usertype = mysqli_query($link, $sql_usertype);
    if (!$result_usertype || mysqli_num_rows($result_usertype) == 0) {
        echo "<script>alert('獲取發送者用戶類型失敗: " . mysqli_error($link) . "');</script>";
        exit();
    }
    $sender_usertype = mysqli_fetch_assoc($result_usertype)['user_type'];

    $alert_message = ''; // 初始化提示訊息

    if ($action == 'agree') {
        if ($sender_usertype == 'patient') {
            // 患者與照護者綁定
            $patient_account = $account;
            $caregiver_account = $send_account;

            // 獲取 caregiver_id
            $sql_get_caregiver_id = "SELECT caregiver_id FROM caregiver WHERE account = '" . mysqli_real_escape_string($link, $caregiver_account) . "'";
            $result_caregiver_id = mysqli_query($link, $sql_get_caregiver_id);
            if (!$result_caregiver_id || mysqli_num_rows($result_caregiver_id) == 0) {
                echo "<script>alert('找不到照護者 ID。');</script>";
                exit();
            }
            $caregiver_id = mysqli_fetch_assoc($result_caregiver_id)['caregiver_id'];

            // 獲取 patient_id
            $sql_get_patient_id = "SELECT patient_id FROM patient WHERE account = '" . mysqli_real_escape_string($link, $patient_account) . "'";
            $result_patient_id = mysqli_query($link, $sql_get_patient_id);
            if (!$result_patient_id || mysqli_num_rows($result_patient_id) == 0) {
                echo "<script>alert('找不到患者 ID。');</script>";
                exit();
            }
            $patient_id = mysqli_fetch_assoc($result_patient_id)['patient_id'];

            // 綁定帳號
            $sql_bind_account = "INSERT INTO patient_caregiver (patient_id, caregiver_id) VALUES ('$patient_id', '$caregiver_id')";
            if (mysqli_query($link, $sql_bind_account)) {
                $alert_message = '帳號綁定成功。';
            } else {
                echo "<script>alert('綁定患者和照護者失敗: " . mysqli_error($link) . "');</script>";
            }

        } elseif ($sender_usertype == 'hospital') {
            // 醫療機構與照護者綁定
            $hospital_account = $send_account;
            $pc_account = $account;

            // 獲取 institution_id
            $sql_institution_id = "SELECT institution_id FROM hospital WHERE account='" . mysqli_real_escape_string($link, $hospital_account) . "'";
            $result_institution_id = mysqli_query($link, $sql_institution_id);
            if ($result_institution_id && mysqli_num_rows($result_institution_id) > 0) {
                $institution_id = mysqli_fetch_assoc($result_institution_id)['institution_id'];
            } else {
                echo "<script>alert('找不到醫療機構。');</script>";
                exit();
            }

            // 獲取 user_type
            $sql_user_type = "SELECT user_type FROM user WHERE account='$account'";
            $result_user_type = mysqli_query($link, $sql_user_type);
            if ($result_user_type && mysqli_num_rows($result_user_type) > 0) {
                $user_type = mysqli_fetch_assoc($result_user_type)['user_type'];
            } else {
                echo "<script>alert('找不到用戶類型。');</script>";
                exit();
            }

            // 檢查是否已經存在綁定
            $sql_check_binding = "SELECT * FROM patientcarelink WHERE account = '$account' AND institution_id = '$institution_id'";
            $result_check_binding = mysqli_query($link, $sql_check_binding);
            if ($result_check_binding && mysqli_num_rows($result_check_binding) > 0) {
                echo "<script>alert('該帳號已經綁定過此醫療機構。');</script>";
                exit();
            }

            // 綁定帳號
            $sql_bind_account = "INSERT INTO patientcarelink (account, institution_id, user_type) VALUES ('$account', '$institution_id', '$user_type')";
            if (mysqli_query($link, $sql_bind_account)) {
                $alert_message = '帳號綁定成功。';
            } else {
                echo "<script>alert('綁定醫療機構和照護者失敗: " . mysqli_error($link) . "');</script>";
            }

        } elseif ($sender_usertype == 'caregiver') {
            // 照護者與患者綁定
            $caregiver_account = $account;
            $patient_account = $send_account;

            // 獲取 caregiver_id
            $sql_get_caregiver_id = "SELECT caregiver_id FROM caregiver WHERE account = '" . mysqli_real_escape_string($link, $caregiver_account) . "'";
            $result_caregiver_id = mysqli_query($link, $sql_get_caregiver_id);
            if (!$result_caregiver_id || mysqli_num_rows($result_caregiver_id) == 0) {
                echo "<script>alert('找不到照護者 ID。');</script>";
                exit();
            }
            $caregiver_id = mysqli_fetch_assoc($result_caregiver_id)['caregiver_id'];

            // 獲取 patient_id
            $sql_get_patient_id = "SELECT patient_id FROM patient WHERE account = '" . mysqli_real_escape_string($link, $patient_account) . "'";
            $result_patient_id = mysqli_query($link, $sql_get_patient_id);
            if (!$result_patient_id || mysqli_num_rows($result_patient_id) == 0) {
                echo "<script>alert('找不到患者 ID。');</script>";
                exit();
            }
            $patient_id = mysqli_fetch_assoc($result_patient_id)['patient_id'];

            // 綁定帳號
            $sql_bind_account = "INSERT INTO patient_caregiver (patient_id, caregiver_id) VALUES ('$patient_id', '$caregiver_id')";
            if (mysqli_query($link, $sql_bind_account)) {
                $alert_message = '帳號綁定成功。';
            } else {
                echo "<script>alert('綁定患者和照護者失敗: " . mysqli_error($link) . "');</script>";
            }
        }

        // 返回成功訊息
        echo "<script>alert('" . $alert_message . "');</script>";
        exit();
    } elseif ($action == 'disagree') {
        echo "<script>alert('帳號綁定失敗。');</script>";
        exit();
    }
}

// 獲取用戶的所有通知
$sql = "SELECT * FROM notification WHERE account = '" . mysqli_real_escape_string($link, $account) . "' ORDER BY time DESC";
$result = mysqli_query($link, $sql);
if (!$result) {
    echo "<script>alert('資料庫查詢失敗: " . mysqli_error($link) . "');</script>";
}
$notifications = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<!doctype html>
<html lang="en">

<head>
    <?php include "head.php"; ?>
</head>

<body>
    <?php include "nav.php"; ?>
    <div class="container mt-5">
        <h2 class="text-center">通知</h2>
        <div class="table-container">
            <?php if (!empty($notifications)): ?>
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-card"
                        onclick="showModal('<?php echo htmlspecialchars($notification['content']); ?>', '<?php echo htmlspecialchars($notification['time']); ?>', '<?php echo $notification['notification_id']; ?>', '<?php echo $notification['notification_type']; ?>', this, <?php echo $notification['is_read']; ?>)">
                        <div class="notification-content">
                            <i
                                class="fa-solid fa-circle notification-icon <?php echo $notification['is_read'] ? 'read' : ''; ?>"></i>
                            <strong>訊息：</strong><?php echo htmlspecialchars($notification['content']); ?>
                        </div>
                        <div class="notification-time">
                            <?php echo htmlspecialchars($notification['time']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-notifications">沒有通知</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 模態框 -->
    <div class="modal fade s3-modal" id="notificationModal" tabindex="-1" role="dialog"
        aria-labelledby="notificationModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="notificationModalLabel">通知詳細信息</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="modalContent"></p>
                    <p id="modalTime"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="agreeButton"
                        onclick="handleAction('agree')">同意</button>
                    <button type="button" class="btn btn-secondary" id="disagreeButton"
                        onclick="handleAction('disagree')">不同意</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">關閉</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentNotificationId;
        let currentNotificationType;

        function showModal(content, time, notificationId, notificationType, element, isRead) {
            document.getElementById('modalContent').innerText = "內容：" + content;
            document.getElementById('modalTime').innerText = "時間：" + time;
            currentNotificationId = notificationId; // 記錄當前通知 ID
            currentNotificationType = notificationType; // 記錄通知類型
            $('#notificationModal').modal('show'); // 使用 Bootstrap 的模態框方法

            // 改變圖示顏色
            const icon = element.querySelector('.notification-icon');
            icon.classList.add('read'); // 添加已讀樣式

            // 根據 isRead 和 notificationType 控制按鈕顯示
            const agreeButton = document.getElementById('agreeButton');
            const disagreeButton = document.getElementById('disagreeButton');
            if (isRead || notificationType !== 'binding') {
                agreeButton.style.display = 'none';
                disagreeButton.style.display = 'none';
            } else {
                agreeButton.style.display = 'inline-block';
                disagreeButton.style.display = 'inline-block';
            }

            // 更新為已讀
            $.ajax({
                type: "POST",
                url: "notification.php",
                data: {
                    notification_id: currentNotificationId,
                    action: 'read'
                },
                success: function (response) {
                    console.log("通知已標記為已讀");
                },
                error: function (xhr) {
                    alert("發生錯誤，請稍後再試。"); // 使用 alert 顯示錯誤訊息
                }
            });
        }

        function handleAction(action) {
            $.ajax({
                type: "POST",
                url: "notification.php",
                data: {
                    notification_id: currentNotificationId,
                    action: action
                },
                success: function (response) {
                    alert("綁定成功");
                    $('#notificationModal').modal('hide'); // 隱藏模態框
                    location.reload(); // 重新加載頁面以更新通知
                },
                error: function (xhr) {
                    alert("發生錯誤，請稍後再試。"); // 使用 alert 顯示錯誤訊息
                }
            });
        }
    </script>

</body>

</html>
