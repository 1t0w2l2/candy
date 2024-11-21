<?php
include 'db.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 手動引入 PHPMailer 的必要檔案
require 'src/PHPMailer.php';
require 'src/Exception.php';
require 'src/SMTP.php';

if (isset($_POST['institution_id']) && isset($_POST['action'])) {
    $institution_id = $_POST['institution_id'];
    $action = $_POST['action']; // 從前端接收到的 action (approve 或 reject)


    // 查詢 hospital 表來找到對應的 account 和 institution_name
    $sql_account = "SELECT account, institution_name FROM hospital WHERE institution_id = ?";
    $stmt_account = $link->prepare($sql_account);
    $stmt_account->bind_param("s", $institution_id);
    $stmt_account->execute();
    $result_account = $stmt_account->get_result();

    if ($result_account->num_rows > 0) {
        $row_account = $result_account->fetch_assoc();
        $account = $row_account['account'];
        $institution_name = $row_account['institution_name'];
        $stmt_account->close(); // 關閉 stmt_account


        // 查詢 user 表來找到對應的 email
        $sql_email = "SELECT email FROM user WHERE account = ?";
        $stmt_email = $link->prepare($sql_email);
        $stmt_email->bind_param("s", $account);
        $stmt_email->execute();
        $result_email = $stmt_email->get_result();

        // 根據 action 執行不同的操作
        if ($action === 'approve') {
            // 審核通過邏輯：更新 hospital 表中的 status
            $sql = "UPDATE hospital SET status = 1 WHERE institution_id = ?";
            $stmt = $link->prepare($sql);
            $stmt->bind_param("s", $institution_id);

            if ($stmt->execute()) {
                $stmt->close(); // 更新成功後關閉 stmt
            } else {
                echo json_encode(['status' => 'error', 'message' => '更新 hospital 狀態失敗']);
                exit;
            }
        } elseif ($action === 'reject') {
            // 退回申請邏輯：刪除 user 表中的該 account
            $sql_delete_user = "DELETE FROM user WHERE account = ?";
            $stmt_delete_user = $link->prepare($sql_delete_user);
            $stmt_delete_user->bind_param("s", $account);

            if (!$stmt_delete_user->execute()) {
                echo json_encode(['status' => 'error', 'message' => '刪除 user 帳號失敗']);
                exit;
            }
            $stmt_delete_user->close(); // 關閉刪除用戶的 stmt
        } else {
            echo json_encode(['status' => 'error', 'message' => '無效的操作']);
            exit;
        }



        if ($result_email->num_rows > 0) {
            $row_email = $result_email->fetch_assoc();
            $email = $row_email['email'];
            $stmt_email->close(); // 關閉 stmt_email

            // 發送通知 Email 給用戶
            $mail = new PHPMailer(true);

            try {
                // 伺服器設定
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com'; // Gmail 的 SMTP 伺服器
                $mail->SMTPAuth = true;
                $mail->Username = 'dementia0920@gmail.com';
                $mail->Password = 'okos hkzz dzic mobs';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                $mail->CharSet = 'utf8';

                // 收件者
                $mail->setFrom('dementia0920@gmail.com', '失智守護系统');
                $mail->addAddress($email); // 收件人的 email

                $mail->isHTML(true);

                // 根據操作動作決定郵件內容
                if ($action === 'approve') {
                    // 審核通過郵件內容
                    $mail->Subject = '[失智守護系统] 醫療機構審核通過通知';
                    $mail->Body = '您好：<br>您的醫療機構 ' . $institution_name . ' 帳號審核已通過，現在可以登入使用系統了！';
                } elseif ($action === 'reject') {
                    // 退回申請郵件內容
                    $mail->Subject = '[失智守護系统] 醫療機構審核未通過通知';
                    $mail->Body = '您好：<br>很抱歉，您的醫療機構 ' . $institution_name . ' 帳號申請未能通過審核，請重新註冊或直接連絡管理員。';
                }

                $mail->send();
                echo json_encode(['status' => 'success', 'message' => '操作成功並發送郵件通知']);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => '郵件發送失敗：' . $mail->ErrorInfo]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => '未找到用戶郵箱']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => '未找到醫療機構帳號']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => '缺少必要的參數']);
}

$link->close();
?>