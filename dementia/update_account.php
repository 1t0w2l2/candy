<?php
include 'db.php'; // 連接資料庫
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 手動引入 PHPMailer 的必要檔案
require 'src/PHPMailer.php';
require 'src/Exception.php';
require 'src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $account = $_POST['account'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $sex = $_POST['sex'];
    $address = $_POST['address'];

    // 接收 emergency_name, emergency_phone, birthday 這三個欄位的值
    $emergencyName = isset($_POST['emergency_name']) ? $_POST['emergency_name'] : null;
    $emergencyPhone = isset($_POST['emergency_phone']) ? $_POST['emergency_phone'] : null;
    $birthday = isset($_POST['birthday']) ? $_POST['birthday'] : null;

    // 檢查資料完整性
    if (empty($account) || empty($name) || empty($email) || empty($phone)) {
        echo json_encode(['status' => 'error', 'message' => '資料不完整']);
        exit;
    }

    // 檢查 email 是否已經存在於資料庫中，排除當前使用者的 email
    $checkEmailQuery = "SELECT email FROM user WHERE email = ?";
    $checkEmailStmt = $link->prepare($checkEmailQuery);
    $checkEmailStmt->bind_param('s', $email);
    $checkEmailStmt->execute();
    $emailResult = $checkEmailStmt->get_result();

    if ($emailResult->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => '此 email 已被其他帳戶使用，請選擇不同的 email']);
        exit;
    }

    // 查詢資料庫中的 email，檢查是否需要更新
    $checkEmailQuery = "SELECT email FROM user WHERE email = ? AND account != ?";
    $checkEmailStmt = $link->prepare($checkEmailQuery);
    $checkEmailStmt->bind_param('s', $account);
    $checkEmailStmt->execute();
    $emailResult = $checkEmailStmt->get_result();

    $emailStatusUpdate = false;
    $previousEmail = '';

    if ($emailResult->num_rows > 0) {
        $userRow = $emailResult->fetch_assoc();
        $previousEmail = $userRow['email'];

        // 如果提供的 email 與資料庫中的 email 不同，設置需要更新 email_status
        if ($previousEmail != $email) {
            $emailStatusUpdate = true;
        }
    }

    // 更新 user 表格
    $query = "UPDATE user SET name = ?, email = ?, phone = ?, sex = ?, address = ? WHERE account = ?";
    $stmt = $link->prepare($query);
    $stmt->bind_param('ssssss', $name, $email, $phone, $sex, $address, $account);

    if ($stmt->execute()) {
        // 檢查用戶類型
        $checkUserTypeQuery = "SELECT user_type FROM user WHERE account = ?";
        $userTypeStmt = $link->prepare($checkUserTypeQuery);
        $userTypeStmt->bind_param('s', $account);
        $userTypeStmt->execute();
        $userTypeResult = $userTypeStmt->get_result();

        if ($userTypeResult->num_rows > 0) {
            $userTypeRow = $userTypeResult->fetch_assoc();

            // 如果用戶是 patient 且 email 發生變更
            if ($userTypeRow['user_type'] === 'patient' && $emailStatusUpdate) {
                // 更新 email_status 為 0，表示需要重新驗證
                $updateEmailStatusQuery = "UPDATE user SET email_status = '0' WHERE account = ?";
                $emailStatusStmt = $link->prepare($updateEmailStatusQuery);
                $emailStatusStmt->bind_param('s', $account);

                if ($emailStatusStmt->execute()) {
                    // 更新 patient 表格資料
                    $updatePatientQuery = "UPDATE patient SET birthday = ?, emergency_name = ?, emergency_phone = ? WHERE account = ?";
                    $patientStmt = $link->prepare($updatePatientQuery);
                    $patientStmt->bind_param('ssss', $birthday, $emergencyName, $emergencyPhone, $account);

                    if ($patientStmt->execute()) {
                        // 發送郵件通知
                        $mail = new PHPMailer(true);
                        try {
                            $mail->isSMTP();
                            $mail->Host = 'smtp.gmail.com';
                            $mail->SMTPAuth = true;
                            $mail->Username = 'dementia0920@gmail.com';
                            $mail->Password = 'okos hkzz dzic mobs';
                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                            $mail->Port = 587;
                            $mail->CharSet = 'utf8';

                            $mail->setFrom('dementia0920@gmail.com', '失智守護系统');
                            $mail->addAddress($email);

                            $mail->isHTML(true);
                            $mail->Subject = "[失智守護系统] 您的電子郵件已更新，請重新驗證";
                            $mail->Body = "您好，{$name}，<br>我們注意到您的電子郵件已經由管理員修改。為了確保您的信箱是正確的，請重新登入驗證您的帳號。";

                            $mail->send();
                            echo json_encode(['status' => 'success', 'message' => '病患資料和 email 更新成功，並已發送郵件通知']);
                        } catch (Exception $e) {
                            echo json_encode(['status' => 'error', 'message' => '郵件發送失敗：' . $mail->ErrorInfo]);
                        }
                    } else {
                        echo json_encode(['status' => 'error', 'message' => '更新病患資料失敗', 'error' => $patientStmt->error]);
                    }
                } else {
                    echo json_encode(['status' => 'error', 'message' => '更新 email_status 失敗', 'error' => $emailStatusStmt->error]);
                }
            } elseif ($userTypeRow['user_type'] === 'patient') {
                // 僅更新 patient 表格資料，不需要更新 email_status
                $updatePatientQuery = "UPDATE patient SET birthday = ?, emergency_name = ?, emergency_phone = ? WHERE account = ?";
                $patientStmt = $link->prepare($updatePatientQuery);
                $patientStmt->bind_param('ssss', $birthday, $emergencyName, $emergencyPhone, $account);

                if ($patientStmt->execute()) {
                    echo json_encode(['status' => 'success', 'message' => '病患資料更新成功']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => '更新病患資料失敗', 'error' => $patientStmt->error]);
                }
            } elseif ($emailStatusUpdate) {
                // 僅更新 email_status 並發送郵件
                $updateEmailStatusQuery = "UPDATE user SET email_status = '0' WHERE account = ?";
                $emailStatusStmt = $link->prepare($updateEmailStatusQuery);
                $emailStatusStmt->bind_param('s', $account);

                if ($emailStatusStmt->execute()) {
                    // 發送郵件通知
                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = 'dementia0920@gmail.com';
                        $mail->Password = 'okos hkzz dzic mobs';
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port = 587;
                        $mail->CharSet = 'utf8';

                        $mail->setFrom('dementia0920@gmail.com', '失智守護系统');
                        $mail->addAddress($email);

                        $mail->isHTML(true);
                        $mail->Subject = "[失智守護系统] 您的電子郵件已更新，請重新驗證";
                        $mail->Body = "您好，{$name}，<br>您的電子郵件已經由管理員修改。為了確保您的信箱是正確的，請重新登入驗證您的帳號。";

                        $mail->send();
                        echo json_encode(['status' => 'success', 'message' => 'email 更新成功，並已發送郵件通知']);
                    } catch (Exception $e) {
                        echo json_encode(['status' => 'error', 'message' => '郵件發送失敗：' . $mail->ErrorInfo]);
                    }
                } else {
                    echo json_encode(['status' => 'error', 'message' => '更新 email_status 失敗', 'error' => $emailStatusStmt->error]);
                }
            } else {
                // 無特殊更新需求，僅回傳成功訊息
                echo json_encode(['status' => 'success', 'message' => '資料更新成功']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => '無法查詢到用戶類型']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => '更新用戶資料失敗', 'error' => $stmt->error]);
    }
}
?>