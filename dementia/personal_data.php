<?php
session_start(); // 確保會話已啟動
include "db.php";

$account = isset($_SESSION['account']) ? $_SESSION['account'] : '';
if (!$account) {
    header("Location: login.php");
    exit();
}

// 驗證使用者是否存在
$sql_check = "SELECT * FROM user WHERE account = '$account'";
$result = mysqli_query($link, $sql_check);
if (!$result || !$user = mysqli_fetch_assoc($result)) {
    echo "<p>帳號不存在或查詢錯誤: " . mysqli_error($link) . "</p>";
    exit();
}

// 防止重複提交
if (!isset($_SESSION['last_action'])) {
    $_SESSION['last_action'] = time();
}

// 更新使用者資料
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    if (time() - $_SESSION['last_action'] < 2) { // 設定防止重複提交的時間間隔，例如2秒
        echo "<script>alert('請勿重複提交');</script>";
        exit();
    }
    $_SESSION['last_action'] = time(); // 更新上次操作時間

    if ($_POST['action'] === 'update') {
        // 從 POST 獲取資料
        $new_password = mysqli_real_escape_string($link, $_POST['new_password']);
        $new_email = mysqli_real_escape_string($link, $_POST['email']);
        $name = mysqli_real_escape_string($link, $_POST['name']);
        $phone = mysqli_real_escape_string($link, $_POST['phone']);
        $address = mysqli_real_escape_string($link, $_POST['address']);
        $message = '';

        $error_message = '';
        $password_update = '';

        // 檢查是否需要更新密碼
        if (!empty($new_password)) {
            $password_update = "password = '" . mysqli_real_escape_string($link, $new_password) . "'";
        }

        // 查詢現有的 email
        $sql_email = "SELECT email FROM user WHERE account = '$account'";
        $result = mysqli_query($link, $sql_email);

        if ($result) {
            // 取得現有的 email
            if ($row = mysqli_fetch_assoc($result)) {
                $current_email = $row['email']; // 現有的 email
        
                // 如果 new_email 不等於現有的 email，發送驗證碼
                if ($new_email !== $current_email) {
                    $verification_code = rand(100000, 999999); // 生成一個隨機的驗證碼
                    $_SESSION['verification_code'] = $verification_code; // 保存驗證碼到 session
                
                    // 發送驗證碼的 email
                    $email_send_result = send_verification_email($new_email, $name, $verification_code);  // 使用 $new_email 發送郵件
                
                    if ($email_send_result === true) {  // 邮件发送成功
                
                        $sql_update = "UPDATE user SET email_status = 0 , email ='$new_email' WHERE account = '$account'";
                        if (mysqli_query($link, $sql_update)) {
                            $message = "您已更改 Email，請檢查郵箱並輸入驗證碼";
                            header("Location: email.php");
                            exit();  // 確保重定向後不再繼續執行後續程式
                        } else {
                            // 数据库更新失败
                            $message = "更新資料失敗，請稍後再試。";
                        }
                    } else {
                        // 如果發送失敗，顯示錯誤
                        $message = "發送驗證碼失敗，錯誤原因：" . $email_send_result;  // 显示详细错误消息
                    }
                } else {
                    // 如果 new_email 和 current_email 一樣，則不需要發送驗證碼
                    $message = "您的 Email 沒有更改，無需發送驗證碼。";
                }
                
            }
        }
                
        

        // 如果沒有更改 email，直接更新其他資料（如密碼、電話、姓名、地址）
        if ($new_email === $current_email || empty($new_email)) {
            $sql_update = "UPDATE user SET name = '$name', phone = '$phone', address = '$address'";
            if (!empty($password_update)) {
                $sql_update .= ", $password_update";
            }
            $sql_update .= " WHERE account = '$account'";  // 保持帳號不變

            if (mysqli_query($link, $sql_update)) {
                $message = "資料更新成功";
            } else {
                $message = "資料更新失敗:" . mysqli_error($link);
            }
        }
    } elseif ($_POST['action'] === 'add') {
        // 处理通知提交
        if (!empty($_POST['modalCaregiver'])) {
            $caregiver_account = mysqli_real_escape_string($link, $_POST['modalCaregiver']);

            // 查找照護者的流水號
            $sql_get_caregiver_id = "SELECT caregiver_id FROM caregiver WHERE account = '$caregiver_account'";
            $result_caregiver = mysqli_query($link, $sql_get_caregiver_id);
            if ($result_caregiver && $caregiver = mysqli_fetch_assoc($result_caregiver)) {
                $caregiver_id = $caregiver['caregiver_id'];

                // 查找患者的流水號
                $sql_get_patient_id = "SELECT patient_id FROM patient WHERE account = '$account'";
                $result_patient = mysqli_query($link, $sql_get_patient_id);
                if ($result_patient && $patient = mysqli_fetch_assoc($result_patient)) {
                    $patient_id = $patient['patient_id'];

                    // 檢查是否已經綁定
                    $sql_check_bind = "SELECT * FROM patient_caregiver WHERE patient_id = '$patient_id' AND caregiver_id = '$caregiver_id'";
                    $result_bind = mysqli_query($link, $sql_check_bind);
                    if ($result_bind && mysqli_num_rows($result_bind) > 0) {
                        $alreadyBound = true;
                        $message = "該照護者已經與您綁定，無法再次發送邀請。";
                    }

                    // 如果尚未綁定，才發送邀請
                    if (!$alreadyBound) {
                        $content = "$account 向您發送帳號綁定邀請";
                        $sql_insert = "INSERT INTO notification (account,send_account,notification_type, content, is_read) VALUES ('$caregiver_account','$account','binding','$content', 0)";
                        if (mysqli_query($link, $sql_insert)) {
                            $_SESSION['caregiver_account'] = $caregiver_account; // 儲存照護者帳號
                            $_SESSION['patient_account'] = $account; // 將患者帳號存入 session
                            $message = "已對該帳號為: $caregiver_account 發送邀請";
                        } else {
                            $message = "發送通知錯誤: " . mysqli_error($link);
                        }
                    }
                } else {
                    $message = "無法找到對應的患者帳號";
                }
            } else {
                $message = "無法找到對應的照護者帳號";
            }

        } elseif (!empty($_POST['modalPatient'])) {
            // 發送給患者
            $patient_account = mysqli_real_escape_string($link, $_POST['modalPatient']);

            // 查找患者的流水號
            $sql_get_patient_id = "SELECT patient_id FROM patient WHERE account = '$patient_account'";
            $result_patient = mysqli_query($link, $sql_get_patient_id);
            if ($result_patient && $patient = mysqli_fetch_assoc($result_patient)) {
                $patient_id = $patient['patient_id'];

                // 查找照護者的流水號
                $sql_get_caregiver_id = "SELECT caregiver_id FROM caregiver WHERE account = '$account'";
                $result_caregiver = mysqli_query($link, $sql_get_caregiver_id);
                if ($result_caregiver && $caregiver = mysqli_fetch_assoc($result_caregiver)) {
                    $caregiver_id = $caregiver['caregiver_id'];

                    // 檢查是否已經綁定
                    $sql_check_bind = "SELECT * FROM patient_caregiver WHERE patient_id = '$patient_id' AND caregiver_id = '$caregiver_id'";
                    $result_bind = mysqli_query($link, $sql_check_bind);
                    if ($result_bind && mysqli_num_rows($result_bind) > 0) {
                        $alreadyBound = true;
                        $message = "您已經與該患者綁定，無法再次發送邀請。";
                    }

                    // 如果尚未綁定，才發送邀請
                    if (!$alreadyBound) {
                        $content = "$account 向您發送帳號綁定邀請";
                        $sql_insert = "INSERT INTO notification (account,send_account,notification_type,content, is_read) VALUES ('$patient_account','$account','binding','$content', 0)";
                        if (mysqli_query($link, $sql_insert)) {
                            $_SESSION['patient_account'] = $patient_account; // 儲存患者帳號
                            $_SESSION['caregiver_account'] = $account;
                            $message = "已對該帳號為: $patient_account 發送邀請";
                        } else {
                            $message = "發送通知錯誤: " . mysqli_error($link);
                        }
                    }
                } else {
                    $message = "無法找到對應的照護者帳號";
                }
            } else {
                $message = "無法找到對應的患者帳號";
            }
        } else {
            $message = "請輸入有效的帳號";
        }
        // 跳出視窗顯示結果
        if (!empty($message)) {
            echo "<script type='text/javascript'>alert('$message');</script>";
        }
    } elseif ($_POST['action'] === 'delete') {
        // 处理删除
        $account_to_delete = mysqli_real_escape_string($link, $_POST['account_to_delete']);
        $user_account = $_SESSION['account'];
        $sql_get_user_type = "SELECT user_type FROM user WHERE account = '$user_account'";
        $result_user_type = mysqli_query($link, $sql_get_user_type);

        if ($result_user_type && $user = mysqli_fetch_assoc($result_user_type)) {
            $user_type = $user['user_type'];

            if ($user_type == 'patient') {
                // 获取 patient_id 和 caregiver_id
                $sql_get_patient_id = "SELECT patient_id FROM patient WHERE account = '$user_account'";
                $result_patient = mysqli_query($link, $sql_get_patient_id);
                if ($result_patient && $patient = mysqli_fetch_assoc($result_patient)) {
                    $patient_id = $patient['patient_id'];
                }

                $sql_get_caregiver_id = "SELECT caregiver_id FROM caregiver WHERE account = '$account_to_delete'";
                $result_caregiver = mysqli_query($link, $sql_get_caregiver_id);
                if ($result_caregiver && $caregiver = mysqli_fetch_assoc($result_caregiver)) {
                    $caregiver_id = $caregiver['caregiver_id'];
                }

                // 删除 patient 和 caregiver 的綁定關係
                $sql_delete_binding = "DELETE FROM patient_caregiver WHERE patient_id = '$patient_id' AND caregiver_id = '$caregiver_id'";
                if (mysqli_query($link, $sql_delete_binding)) {
                    // 發送通知給 caregiver
                    $content = "患者帳號 $user_account 已取消與您的綁定。";
                    $sql_notification = "INSERT INTO notification (account, send_account, notification_type, content, is_read) 
                                        VALUES ('$account_to_delete', '$user_account', 'delete binding', '$content', 0)";
                    mysqli_query($link, $sql_notification);

                    $message = "成功删除與照護者的綁定關係。";
                } else {
                    $message = "删除失敗: " . mysqli_error($link);
                }
            } elseif ($user_type == 'caregiver') {
                // 获取 caregiver_id 和 patient_id
                $sql_get_caregiver_id = "SELECT caregiver_id FROM caregiver WHERE account = '$user_account'";
                $result_caregiver = mysqli_query($link, $sql_get_caregiver_id);
                if ($result_caregiver && $caregiver = mysqli_fetch_assoc($result_caregiver)) {
                    $caregiver_id = $caregiver['caregiver_id'];
                }

                $sql_get_patient_id = "SELECT patient_id FROM patient WHERE account = '$account_to_delete'";
                $result_patient = mysqli_query($link, $sql_get_patient_id);
                if ($result_patient && $patient = mysqli_fetch_assoc($result_patient)) {
                    $patient_id = $patient['patient_id'];
                }

                // 删除 patient 和 caregiver 的綁定關係
                $sql_delete_binding = "DELETE FROM patient_caregiver WHERE patient_id = '$patient_id' AND caregiver_id = '$caregiver_id'";
                if (mysqli_query($link, $sql_delete_binding)) {
                    // 發送通知給 patient
                    $content = "照護者帳號 $user_account 已取消與您的綁定。";
                    $sql_notification = "INSERT INTO notification (account, send_account, notification_type, content, is_read) 
                                        VALUES ('$account_to_delete', '$user_account', 'delete binding', '$content', 0)";
                    mysqli_query($link, $sql_notification);

                    $message = "成功删除與患者的綁定關係。";
                } else {
                    $message = "删除失敗: " . mysqli_error($link);
                }
            }
        } else {
            $message = "用戶類型識別錯誤。";
        }
    } elseif ($_POST['action'] === 'logout') {
        $account = $_SESSION['account'];
        $account = mysqli_real_escape_string($link, $account);

        $sql = "DELETE FROM user WHERE account='$account'";

        // 執行刪除操作
        if (mysqli_query($link, $sql)) {
            session_unset();
            $message = "已成功註銷";
        } else {
            // 刪除失敗，顯示錯誤信息
            $message = "註銷失敗: " . mysqli_error($link);
        }
    }// 顯示結果並重定向
  
    echo "<script type='text/javascript'>alert('$message'); window.location.href = 'personal_data.php';</script>";
}

// 查詢已綁定的帳號
$bound_accounts = [];
if ($user['user_type'] == 'patient') {
    $sql_get_caregivers = "SELECT c.account 
                           FROM patient_caregiver pc 
                           INNER JOIN caregiver c ON pc.caregiver_id = c.caregiver_id
                           WHERE pc.patient_id = (SELECT patient_id FROM patient WHERE account = '$account')";
    $result_caregivers = mysqli_query($link, $sql_get_caregivers);
    if ($result_caregivers) {
        while ($row = mysqli_fetch_assoc($result_caregivers)) {
            $bound_accounts[] = $row['account'];
        }
    }
} elseif ($user['user_type'] == 'caregiver') {
    $sql_get_patients = "SELECT p.account 
                         FROM patient_caregiver pc 
                         INNER JOIN patient p ON pc.patient_id = p.patient_id
                         WHERE pc.caregiver_id = (SELECT caregiver_id FROM caregiver WHERE account = '$account')";
    $result_patients = mysqli_query($link, $sql_get_patients);
    if ($result_patients) {
        while ($row = mysqli_fetch_assoc($result_patients)) {
            $bound_accounts[] = $row['account'];
        }
    }
}

function send_verification_email($email, $name, $verification_code)
{
    // 加载 PHPMailer 的文件
    require 'src/Exception.php';
    require 'src/PHPMailer.php';
    require 'src/SMTP.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $message1 = "您好，為了確保您的信箱正確，請用以下驗證碼「{$verification_code}」進行驗證。";
        $title = "這是您的驗證信";

        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'dementia0920@gmail.com';  // 發送郵件的帳戶
        $mail->Password = 'okos hkzz dzic mobs';    // 郵件發送帳戶的密碼
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS; // 'ssl'
        $mail->Port = 465;
        $mail->CharSet = "utf8";
        $mail->setFrom('dementia0920@gmail.com', '失智守護系統');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = $title;
        $mail->Body = $message1;
        $mail->AltBody = strip_tags($message1);

        $mail->send();
        return true;  // 发送成功
    } catch (Exception $e) {
        // 捕获异常并返回错误信息
        return "郵件發送失敗：" . $mail->ErrorInfo;
    }
}

?>





<head>
    <?php
    include 'head.php';
    ?>

<body>
    <?php include "nav.php"; ?>

    <div class="hero hero-inner">
        <div class="container">
            <div class="row align-items-start justify-content-center">
                <!-- 第一個框框: 個人資料 -->
                <div class="col-lg-8 mb-4">
                    <div class="card p-4 shadow-sm" style="max-width: 100%;">
                        <h1 class="mb-4 text-center" style="color: #000;">個人資料</h1>
                        <form action="" method="post" id="form" name="form">
                            <div class="form-group mb-4">
                                <label for="new_account">帳號</label>
                                <input type="text" class="form-control" id="new_account" name="new_account"
                                    value="<?php echo isset($user['account']) ? htmlspecialchars($user['account']) : ''; ?>"
                                    readonly>
                            </div>
                            <div class="form-group mb-4">
                                <label for="new_password">密碼</label>
                                <div class="position-relative">
                                    <input type="password" class="form-control" id="new_password" name="new_password"
                                        value="<?php echo isset($user['password']) ? htmlspecialchars($user['password']) : ''; ?>"
                                        required>
                                    <i class="fa fa-eye position-absolute" id="togglePassword"
                                        style="top: 50%; right: 10px; transform: translateY(-50%); cursor: pointer;"></i>
                                </div>
                            </div>
                            <div class="form-group mb-4">
                                <label for="email">信箱</label>
                                <input type="email" class="form-control" id="email" name="email"
                                    value="<?php echo isset($user['email']) ? htmlspecialchars($user['email']) : ''; ?>"
                                    required>
                            </div>
                            <div class="form-group mb-4">
                                <label for="name">姓名</label>
                                <input type="text" class="form-control" id="name" name="name"
                                    value="<?php echo isset($user['name']) ? htmlspecialchars($user['name']) : ''; ?>"
                                    required>
                            </div>
                            <div class="form-group mb-4">
                                <label for="phone">聯絡電話</label>
                                <input type="text" class="form-control" id="phone" name="phone"
                                    value="<?php echo isset($user['phone']) ? htmlspecialchars($user['phone']) : ''; ?>"
                                    required>
                            </div>
                            <div class="form-group mb-4">
                                <label for="address">地址</label>
                                <input type="text" class="form-control" id="address" name="address"
                                    value="<?php echo isset($user['address']) ? htmlspecialchars($user['address']) : ''; ?>"
                                    required>
                            </div>
                            <div class="d-flex justify-content-between">
                                <button type="submit" class="btn btn-danger w-50 me-3" name="action" value="logout"
                                    onclick="return confirm('確定要註銷帳號嗎？')">註銷帳號</button>
                                <button type="submit" class="btn btn-primary w-50 " name="action"
                                    value="update">更新資料</button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php if ($user['user_type'] != 'hospital'): ?>
                    <div class="col-lg-4">
                        <div class="card p-4 shadow-sm" style="max-width: 100%;">
                            <h2 class="text-center" style="color: #000;">綁定帳號</h2>
                            <?php if ($user['user_type'] == 'patient'): ?>
                                <div class="form-group mb-4">
                                    <div class="input-group">
                                        <table class="table"
                                            style="background-color: #f2f2f2; border-radius: 8px; border-collapse: separate; border-spacing: 0; overflow: hidden;">
                                            <thead>
                                                <tr style="background-color: #dcdcdc; color: #333; text-align: left;">
                                                    <th style="padding: 10px; border-bottom: 1px solid #ccc;">已綁定的照護者帳號</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($bound_accounts)): ?>
                                                    <?php foreach ($bound_accounts as $account): ?>
                                                        <tr>
                                                            <td
                                                                style="padding: 10px; border-bottom: 1px solid #ccc; display: flex; align-items: center; justify-content: space-between;">
                                                                <span><?php echo htmlspecialchars($account); ?></span>
                                                                <form method="POST" action="" style="margin: 0;">
                                                                    <input type="hidden" name="account_to_delete"
                                                                        value="<?php echo htmlspecialchars($account); ?>">
                                                                    <button class="btn-danger delete-button" type="submit" name="action"
                                                                        value="delete"
                                                                        style="background-color: #d9534f; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">
                                                                        刪除
                                                                    </button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td style="padding: 10px;">尚未綁定帳號</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                        <div class="w-100 text-center mt-3">
                                            <button class="btn btn-secondary" type="button" data-bs-toggle="modal"
                                                data-bs-target="#caregiverModal">新增照護者</button>
                                        </div>
                                    </div>
                                </div>

                            <?php elseif ($user['user_type'] == 'caregiver'): ?>
                                <div class="form-group mb-4">
                                    <div class="input-group">
                                        <table class="table"
                                            style="background-color: #f2f2f2; border-radius: 8px; border-collapse: separate; border-spacing: 0; overflow: hidden;">
                                            <thead>
                                                <tr style="background-color: #dcdcdc; color: #333; text-align: left;">
                                                    <th style="padding: 10px; border-bottom: 1px solid #ccc;">已綁定的患者帳號</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($bound_accounts)): ?>
                                                    <?php foreach ($bound_accounts as $account): ?>
                                                        <tr>
                                                            <td
                                                                style="padding: 10px; border-bottom: 1px solid #ccc; display: flex; align-items: center; justify-content: space-between;">
                                                                <span><?php echo htmlspecialchars($account); ?></span>
                                                                <form method="POST" action="" style="margin: 0;">
                                                                    <input type="hidden" name="account_to_delete"
                                                                        value="<?php echo htmlspecialchars($account); ?>">
                                                                    <button class="btn-danger delete-button" type="submit" name="action"
                                                                        value="delete"
                                                                        style="background-color: #d9534f; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">
                                                                        刪除
                                                                    </button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td style="padding: 10px;">尚未綁定帳號</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                        <div class="w-100 text-center">
                                            <button class="btn btn-secondary" type="button" data-bs-toggle="modal"
                                                data-bs-target="#patientModal">新增患者</button>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>


                <!-- Caregiver Modal -->
                <div class="modal fade s3-modal" id="caregiverModal" tabindex="-1" aria-labelledby="caregiverModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form action="" method="post">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="caregiverModalLabel">照護者帳號</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="text" class="form-control" id="modalCaregiver" name="modalCaregiver">
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-secondary" value="add"
                                        name="action">發送通知</button>
                                    <button type="button" class="btn btn-primary"
                                        onclick="clearInput('modalCaregiver')">清除</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="modal fade s3-modal" id="patientModal" tabindex="-1" aria-labelledby="patientModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form action="" method="post">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="patientModalLabel">患者帳號</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="text" class="form-control" id="modalPatient" name="modalPatient">
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-secondary" value="add"
                                        name="action">發送通知</button>
                                    <button type="button" class="btn btn-primary"
                                        onclick="clearInput('modalPatient')">清除</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <script>
        function clearInput(inputId) {
            document.getElementById(inputId).value = '';
        }
        const togglePassword = document.querySelector('#togglePassword');
        const passwordField = document.querySelector('#new_password');

        togglePassword.addEventListener('click', function () {
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);

            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>

</html>