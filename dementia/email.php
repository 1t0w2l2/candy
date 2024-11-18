<?php
session_start();
include "db.php";

if (isset($_SESSION['verification_code'])) {
    $verification_code = $_SESSION['verification_code'];
}

// 檢查表單提交並驗證用戶輸入的驗證碼
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['resend_code'])) {
    if (isset($_POST['code']) && is_array($_POST['code'])) {
        $user_input_code = implode("", $_POST['code']); // 將用戶輸入的數字合併為一個字符串
        if ($user_input_code == $verification_code) {
            if (isset($_SESSION['email']) && isset($_SESSION['user_type'])) {
                $email = mysqli_real_escape_string($link, $_SESSION['email']);
                $sql = "UPDATE user SET email_status = 1 WHERE email = '$email'";
                if (!mysqli_query($link, $sql)) {
                    echo "更新 email.status 失敗";
                    exit();
                }
                // 獲取用戶類型
                $user_type = $_SESSION['user_type'];
                if ($user_type == 'patient') {
                    header("Location: patient_information.php?account=" . urlencode($_SESSION['account']));
                } elseif ($user_type == 'caregiver' || $user_type == 'admin') {
                    echo "<script>alert('註冊成功');window.location.href = 'index_pc.php';</script>";
                } elseif ($user_type == 'hospital') {
                    echo "<script>alert('註冊成功');window.location.href = 'index_h.php';</script>";
                } else {
                    echo "<script>alert('無效的用戶類型');</script>";
                }
                exit();
            } else {
                echo "<script>alert('未找到用戶類型');</script>";
            }
        } else {
            echo "<script>alert('驗證碼錯誤，請重新輸入');</script>";
        }
    } else {
        echo "<script>alert('請輸入驗證碼');</script>";
    }
} elseif (isset($_POST['resend_code'])) {
    // 檢查是否是重登入頁面，若 session 中沒有 email 或 name，仍然可以重新發送驗證碼
    if (isset($_POST['resend_code'])) {
        $new_verification_code = rand(100000, 999999);  // 生成6位隨機數字作為新的驗證碼
        $_SESSION['verification_code'] = $new_verification_code;

        // 檢查 session 中是否存在 email 和 name
        if (isset($_SESSION['email'])) {
            $email = $_SESSION['email'];
            $name = isset($_SESSION['name']) ? $_SESSION['name'] : '用戶'; // 若姓名不存在則設置為 '用戶'
            send_verification_email($email, $name, $new_verification_code);
            echo "<script>alert('新驗證碼已發送，請檢查您的信箱');</script>";
        } else {
            // 這裡不再顯示錯誤提示，根據需要可以選擇記錄日誌或其他操作
            echo "<script>alert('驗證碼已發送，請檢查您的信箱');</script>";
        }
        exit();
    }

}
function send_verification_email($email, $name, $r)
{
    // 加载 PHPMailer 的文件
    require 'src/Exception.php';
    require 'src/PHPMailer.php';
    require 'src/SMTP.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $message1 = "您好，我是失智守護系统的管理員，為了確保您的信箱是正確的，請用以下驗證碼，在註冊頁輸入「{$r}」数字，即可完成註冊";
        $title = "這是您的驗證信";

        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'dementia0920@gmail.com';
        $mail->Password = 'okos hkzz dzic mobs';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS; // 'ssl'
        $mail->Port = 465;
        $mail->CharSet = "utf8";
        $mail->setFrom('dementia0920@gmail.com', '失智守護系统');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = $title;
        $mail->Body = $message1;
        $mail->AltBody = strip_tags($message1);

        $mail->send();
    } catch (Exception $e) {
        echo "郵件發送失敗：" . $mail->ErrorInfo;
    }
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>驗證碼輸入</title>
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
                    btn.disabled = true;  // Disable the button after clicking
                    btn.style.backgroundColor = '#ccc'; // Apply gray color
                    var clear = setInterval(function () {
                        if (times == 0) {
                            times = 60;
                            btn.disabled = false; // Re-enable the button after the countdown
                            btn.textContent = "重新發送驗證碼";
                            btn.style.backgroundColor = '#007bff'; // Reset to the original color
                            clearInterval(clear);
                        } else {
                            btn.textContent = times + "秒後重新發送";
                            times--;
                        }
                    }, 1000);
                });
            }
        });

        document.querySelectorAll('.checkedCode input').forEach(function (input, index, inputs) {
            input.addEventListener('keyup', function (event) {
                if (event.keyCode === 46 || event.keyCode === 8) { // If Delete or Backspace is pressed
                    if (index > 0) inputs[index - 1].focus(); // Focus on the previous input
                } else if (input.value.length === 1) { // If one character is entered
                    if (index < inputs.length - 1) inputs[index + 1].focus(); // Focus on the next input
                }
            });
        });

    </script>
</body>

</html>