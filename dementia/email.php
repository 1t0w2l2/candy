<?php
session_start();
include "db.php";

// 確保 new_email 和 user_type 已經設置
if (!isset($_SESSION['email']) || !isset($_SESSION['user_type'])) {
    echo "<script>alert('未找到用戶類型或電子郵件');</script>";
    exit();
}

// 檢查表單提交並驗證用戶輸入的驗證碼
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['resend_code'])) {
    if (isset($_POST['code']) && is_array($_POST['code'])) {
        $user_input_code = implode("", $_POST['code']); // 把用戶輸入的數字合併為一個字串
        if ($user_input_code == $_SESSION['verification_code']) {
            // 驗證碼正確，更新用戶的電子郵件狀態
            $email = mysqli_real_escape_string($link, $_SESSION['email']);
            $sql = "UPDATE user SET email_status = 1 WHERE email = '$email'";

            if (!mysqli_query($link, $sql)) {
                error_log("更新 email.status 失敗: " . mysqli_error($link)); // 錯誤日誌
                echo "<script>alert('更新 email.status 失敗');</script>";
                exit();
            }

            // 獲取用戶類型
            $user_type = $_SESSION['user_type'];
            if ($user_type == 'patient') {
                // 檢查患者資料是否存在
                $account = $_SESSION['account'];
                $sql_check_patient = "SELECT * FROM patient WHERE account='$account'";
                $result = mysqli_query($link, $sql_check_patient);

                if (mysqli_num_rows($result) > 0) {
                    // 如果患者資料存在，跳轉到個人資料頁面
                    echo "<script>alert('email驗證成功');  window.location.href = 'personal_data.php';</script>";
                    exit();
                } else {
                    // 找不到患者資料，顯示錯誤
                    echo "<script> window.location.href = 'patient_information.php';</script>";
                    exit();
                }
            } elseif ($user_type == 'caregiver' || $user_type == 'admin' || $user_type == 'hospital') {
                // 其他用戶類型，跳轉到首頁
                echo "<script>alert('驗證成功'); window.location.href = 'index.php';</script>";
                exit();
            } else {
                echo "<script>alert('無效的用戶類型');</script>";
                exit();
            }
        } else {
            echo "<script>alert('驗證碼錯誤，請重新輸入');</script>";
            exit();
        }
    } else {
        echo "<script>alert('請輸入驗證碼');</script>";
        exit();
    }
} elseif (isset($_POST['resend_code'])) {
    $new_verification_code = rand(100000, 999999);  // 生成新的驗證碼
    $_SESSION['verification_code'] = $new_verification_code;

    // 確保 new_email 已經設置
    if (isset($_SESSION['new_email'])) {
        $email = $_SESSION['new_email'];
        $name = isset($_SESSION['name']) ? $_SESSION['name'] : '用戶'; // 如果 session 中沒有 name，使用預設值 '用戶'
        send_verification_email($email, $name, $new_verification_code);
        echo "<script>alert('新驗證碼已發送，請檢查您的信箱');</script>";
        exit(); // 確保後續程式碼不再執行
    }

    // 如果找不到 email
    echo "<script>alert('未找到電子郵件地址');</script>";
    exit();
}




function send_verification_email($email, $name, $new_verification_code)
{
    // 加载 PHPMailer 的文件
    require 'src/Exception.php';
    require 'src/PHPMailer.php';
    require 'src/SMTP.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $message1 = "您好，我是失智守護系統的管理員，為了確保您的信箱是正確的，請用以下驗證碼，在註冊頁輸入「{$new_verification_code}」數字，即可完成註冊";
        $title = "這是您的驗證信";

        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'dementia0920@gmail.com';
        $mail->Password = 'okos hkzz dzic mobs'; // 確保這是正確的密碼
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
    } catch (Exception $e) {

    }
}
?>


<!doctype html>
<html lang="en">

<head>
   <?php include "head.php";?>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }

        .container {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            padding: 30px;
            max-width: 400px;
            width: 100%;
            text-align: center;
        }

        h1 {
            font-size: 24px;
            margin-bottom: 20px;
            color: #333;
        }



        .checkedCode {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .checkedCode input {
            width: 40px;
            height: 50px;
            font-size: 24px;
            text-align: center;
            border: 2px solid #ddd;
            border-radius: 5px;
            transition: border-color 0.3s;
        }

        .checkedCode input:focus {
            border-color: #007bff;
            outline: none;
        }

        .btn {
            display: inline-block;
            background-color: #007bff;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            font-size: 16px;
        }

        btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        .btn:hover {
            background-color: #0056b3;
        }

        .resend-link {
            margin-top: 15px;
            font-size: 14px;
            color: #007bff;
            cursor: pointer;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>驗證碼輸入</h1>
        <form method="POST">
            <div class="checkedCode">
                <input type="tel" maxlength="1" name="code[]" pattern="[0-9]*" required />
                <input type="tel" maxlength="1" name="code[]" pattern="[0-9]*" required />
                <input type="tel" maxlength="1" name="code[]" pattern="[0-9]*" required />
                <input type="tel" maxlength="1" name="code[]" pattern="[0-9]*" required />
                <input type="tel" maxlength="1" name="code[]" pattern="[0-9]*" required />
                <input type="tel" maxlength="1" name="code[]" pattern="[0-9]*" required />
            </div>
            <input type="submit" value="驗證" class="btn">
        </form>
        <div class="resend-link">
            <button type="button" id="btn" class="btn">重新發送驗證碼</button>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        document.getElementById('btn').addEventListener('click', function () {
            var btn = document.getElementById('btn');
            var times = 60;

            if (!btn.disabled) {
                $.post('', { resend_code: true }, function (data) {
                    alert(data);
                    btn.disabled = true; 
                    btn.style.backgroundColor = '#ccc';
                    var clear = setInterval(function () {
                        if (times == 0) {
                            times = 60;
                            btn.disabled = false; 
                            btn.textContent = "重新發送驗證碼";
                            btn.style.backgroundColor = '#007bff'; 
                            clearInterval(clear);
                        } else {
                            btn.textContent = times + "秒後重新發送";
                            times--;
                        }
                    }, 1000);
                });
            }
        });
        //輸入完一個數字會跳下一個輸入框
        document.querySelectorAll('.checkedCode input').forEach(function (input, index, inputs) {
            input.addEventListener('keyup', function (event) {
                if (event.keyCode === 46 || event.keyCode === 8) { 
                    if (index > 0) inputs[index - 1].focus(); 
                } else if (input.value.length === 1) { 
                    if (index < inputs.length - 1) inputs[index + 1].focus();
                }
            });
        });

        //可以貼上驗證碼
        document.querySelector('.checkedCode').addEventListener('paste', function (event) {
            var clipboardData = event.clipboardData || window.clipboardData;
            var pastedData = clipboardData.getData('Text');

            // 確保只處理數字且不超過6個
            if (/^\d{1,6}$/.test(pastedData)) {
                var inputs = document.querySelectorAll('.checkedCode input');
                inputs.forEach(function (input, index) {
                    if (index < pastedData.length) {
                        input.value = pastedData[index];
                        input.dispatchEvent(new Event('keyup')); // 觸發keyup事件以便跳轉
                    } else {
                        input.value = ''; // 清空多餘的輸入框
                    }
                });
            }
        });
    </script>
</body>

</html>