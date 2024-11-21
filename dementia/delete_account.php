<?php
include 'db.php'; // 連接資料庫
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 手動引入 PHPMailer 的必要檔案
require 'src/PHPMailer.php';
require 'src/Exception.php';
require 'src/SMTP.php';

// 接收帳號資訊
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $account = $_POST['account'];

    // 檢查帳號是否傳入
    if (empty($account)) {
        echo json_encode(['status' => 'error', 'message' => '帳號為空']);
        exit;
    }

    // 查詢用戶 email 以便發送通知
    $query = "SELECT email, name, user_type FROM user WHERE account = ?";
    $stmt = $link->prepare($query);
    $stmt->bind_param('s', $account);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $email = $user['email'];
        $name = $user['name'];
        $userType = $user['user_type'];

        // 根據 user_type 刪除對應的資料表資料
        if ($userType === 'patient') {
            $deletePatientQuery = "DELETE FROM patient WHERE account = ?";
            $deletePatientStmt = $link->prepare($deletePatientQuery);
            $deletePatientStmt->bind_param('s', $account);
            $deletePatientStmt->execute();
        } elseif ($userType === 'hospital') {
            $deleteHospitalQuery = "DELETE FROM hospital WHERE account = ?";
            $deleteHospitalStmt = $link->prepare($deleteHospitalQuery);
            $deleteHospitalStmt->bind_param('s', $account);
            $deleteHospitalStmt->execute();
        } elseif ($userType === 'caregiver') {
            $deleteCaregiverQuery = "DELETE FROM caregiver WHERE account = ?";
            $deleteCaregiverStmt = $link->prepare($deleteCaregiverQuery);
            $deleteCaregiverStmt->bind_param('s', $account);
            $deleteCaregiverStmt->execute();
        }

        // 刪除 user 表格中的資料
        $deleteUserQuery = "DELETE FROM user WHERE account = ?";
        $deleteUserStmt = $link->prepare($deleteUserQuery);
        $deleteUserStmt->bind_param('s', $account);

        if ($deleteUserStmt->execute()) {
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
                $mail->Subject = "[失智守護系统] 您的帳號已成功刪除";
                $mail->Body = "您好，{$name}，<br>您的帳號({$account})已成功被刪除。如需使用本系統，請重新註冊。";

                $mail->send();
                echo json_encode(['status' => 'success', 'message' => '帳號已成功刪除並已發送郵件通知']);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => '郵件發送失敗：' . $mail->ErrorInfo]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => '刪除用戶資料失敗']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => '找不到該帳號']);
    }
}
?>
