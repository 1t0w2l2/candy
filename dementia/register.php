<?php
session_start();
include "db.php";
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $account = $_POST['account'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];
    $email = $_POST['email'];
    $name = $_POST['name'];
    $sex = $_POST['sex'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $userType = $_POST['userType'];
    $institution_name = $_POST['institution_name'];
    $_SESSION['account'] = $account;

    // 產生六位數的亂數
    $r = rand(100000, 999999);
    $_SESSION['verification_code'] = $r;
    $_SESSION['email'] = $email;
    $_SESSION['name'] = $name;

    // 檢查帳號是否存在
    $sql_check_account = "SELECT * FROM user WHERE account = '$account'";
    $account_row = sqling($link, $sql_check_account);

    if (!empty($account_row['account'])) {
        echo "<script>alert('該帳號已被使用');</script>";
    } else {
        $sql_check_email = "SELECT * FROM user WHERE email = '$email'";
        $email_row = sqling($link, $sql_check_email);

        if (!empty($email_row['email'])) {
            echo "<script>alert('您的電子郵件已被使用'); window.location.href = 'register.php';</script>";
        } else {
            if ($password == $confirmPassword) {
                $_SESSION['user_type'] = $userType;
                // 如果用戶類型是醫療機構，插入 hospital 表的資料
                if ($userType == 'hospital') {
                    $sql_hospital = "SELECT * FROM `institution` WHERE `institution_name` = '$institution_name'";
                    $hospital_row = sqling($link, $sql_hospital);

                    // 如果查到 institution 資料
                    if (!empty($hospital_row)) {
                        // 獲取機構的 institution_id
                        $institution_id = $hospital_row['institution_id'];

                        // 查詢 hospital 表，檢查 institution_id 是否已經存在
                        $sql_check_hospital = "SELECT * FROM `hospital` WHERE `institution_id` = '$institution_id'";
                        $hospital_check_row = sqling($link, $sql_check_hospital);

                        // 如果查到結果，說明該機構代碼已存在於 hospital 表中
                        if (!empty($hospital_check_row)) {
                            echo "<script>alert('該機構已有註冊帳號，請嘗試登入或聯絡管理員'); window.location.href = 'register.php';</script>";
                            exit();
                        } else {
                            // 儲存機構資料到 session
                            $_SESSION['hospital_data'] = $hospital_row;

                            // 顯示表單並隱藏提交
                            echo "<form id='hospitalForm' action='register_hospital.php' method='post'>";

                            // 把已經查詢到的機構資料帶過去
                            foreach ($hospital_row as $key => $value) {
                                echo "<input type='hidden' name='$key' value='$value'>";
                            }

                            // 將 HTML 表單中的其他欄位也傳遞到 register_hospital.php
                            echo "<input type='hidden' name='account' value='$account'>";
                            echo "<input type='hidden' name='password' value='$password'>";
                            echo "<input type='hidden' name='email' value='$email'>";
                            echo "<input type='hidden' name='name' value='$name'>";
                            echo "<input type='hidden' name='sex' value='$sex'>";
                            echo "<input type='hidden' name='phone' value='$phone'>";
                            echo "<input type='hidden' name='addressuser' value='$address'>";
                            echo "<input type='hidden' name='userType' value='$userType'>";

                            // 如果還有其他必要欄位，也一併隱藏傳遞過去

                            echo "</form>";
                            echo "<script>document.getElementById('hospitalForm').submit();</script>";
                            exit();
                        }
                    } else {
                        // 機構資料不存在，直接提交機構名稱
                        echo "<form id='hospitalForm' action='register_hospital.php' method='post'>";
                        echo "<input type='hidden' name='institution_name' value='$institution_name'>";

                        // 同樣把其他表單資料傳遞到 register_hospital.php
                        echo "<input type='hidden' name='account' value='$account'>";
                        echo "<input type='hidden' name='password' value='$password'>";
                        echo "<input type='hidden' name='email' value='$email'>";
                        echo "<input type='hidden' name='name' value='$name'>";
                        echo "<input type='hidden' name='sex' value='$sex'>";
                        echo "<input type='hidden' name='phone' value='$phone'>";
                        echo "<input type='hidden' name='addressuser' value='$address'>";
                        echo "<input type='hidden' name='userType' value='$userType'>";

                        echo "</form>";
                        echo "<script>document.getElementById('hospitalForm').submit();</script>";
                        exit();
                    }
                } elseif ($userType == 'caregiver') {
                    // 處理照護者類型
                    $sql_user = "INSERT INTO user (account, password, email, name, sex, phone, address, user_type) 
                                 VALUES ('$account', '$password', '$email', '$name', '$sex', '$phone', '$address', '$userType')";
                    sqling($link, $sql_user);

                    $sql_caregiver = "INSERT INTO caregiver (account) VALUES ('$account')";
                    sqling($link, $sql_caregiver);
                } else {
                    // 處理其他類型
                    $sql_user = "INSERT INTO user (account, password, email, name, sex, phone, address, user_type) 
                                 VALUES ('$account', '$password', '$email', '$name', '$sex', '$phone', '$address', '$userType')";
                    sqling($link, $sql_user);
                }

                // 發送驗證郵件
                send_verification_email($email, $name, $r);
                echo "<script>alert('感謝您！註冊完成，請到信箱查收驗證碼！'); window.location.href = 'email.php';</script>";
                exit();
            } else {
                echo "<script>alert('密碼不匹配'); window.location.href = 'register.php';</script>";

            }

        }
    }
    $link->close();
}

function sqling($link, $sql)
{
    $result = mysqli_query($link, $sql);
    if (!$result) {
        die("SQL Error: " . mysqli_error($link));
    }
    if (is_bool($result)) {
        return $result;
    } else {
        return mysqli_fetch_array($result);
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
        $message1 = "您好，我是失智守護系統的管理員，為了確保您的信箱是正確的，請用以下驗證碼，在註冊頁輸入「{$r}」數字，即可完成註冊";
        $title = "這是您的驗證信";

        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'dementia0920@gmail.com';
        $mail->Password = 'okos hkzz dzic mobs';
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

    <head>
        <?php
        include 'head.php';
        ?>
        <style>
            .form-control {
                padding-right: 2.5rem;
            }

            .password-toggle-icon {
                position: absolute;
                right: 10px;
                top: 55%;
                transform: translateY(-50%);
                cursor: pointer;
                color: #888;
            }

            .input-group {
                position: relative;

            }
        </style>
    </head>

<body>
    <?php include "nav.php"; ?>

    <div class="hero hero-inner">
        <div class="container">
            <div class="row align-items-center justify-content-center">
                <div class="col-lg-8">
                    <div class="card p-4 shadow-sm">
                        <h1 class="mb-4 text-center" style="color: #000;">註冊帳號</h1>
                        <form action="" method="post" id="a" name="form">
                            <div class="form-group mb-4">
                                <label for="username">帳號</label>
                                <input type="text" class="form-control" id="account" name="account" required>
                            </div>
                            <div class="form-group mb-4">
                                <label for="password" class="form-label">密碼</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password">
                                    <i id="passwordToggleIcon" class="fas fa-eye password-toggle-icon"
                                        onclick="togglePasswordVisibility('password', 'passwordToggleIcon')"></i>
                                </div>
                            </div>

                            <div class="form-group mb-4">
                                <label for="confirmPassword" class="form-label">確認密碼</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="confirmPassword"
                                        name="confirmPassword" required>
                                    <i id="confirmPasswordToggleIcon" class="fas fa-eye password-toggle-icon"
                                        onclick="togglePasswordVisibility('confirmPassword', 'confirmPasswordToggleIcon')"></i>
                                </div>
                            </div>


                            <div class="form-group mb-4">
                                <label for="email">信箱</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="form-group mb-4">
                                <label for="name">姓名</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="form-group mb-4">
                                <label for="sex">性別</label>
                                <select class="form-control" id="sex" name="sex" required>
                                    <option value="M">男</option>
                                    <option value="F">女</option>
                                </select>
                            </div>
                            <div class="form-group mb-4">
                                <label for="phone">聯絡電話</label>
                                <input type="text" class="form-control" id="phone" name="phone" required>
                            </div>
                            <div class="form-group mb-4">
                                <label for="address">地址</label>
                                <input type="text" class="form-control" id="address" name="address" required>
                            </div>
                            <div class="form-group mb-4">
                                <label for="userType">使用者類型</label>
                                <select class="form-control" id="userType" name="userType" required
                                    onchange="toggleProofSection()">
                                    <option value="">請選擇你的使用類型</option>
                                    <option value="patient">患者</option>
                                    <option value="caregiver">照護者</option>
                                    <option value="hospital">醫療機構</option>
                                </select>
                            </div>
                            <div id="proofSection" style="display: none;">
                                <div class="form-group mb-4">
                                    <label for="institution_name">醫療機構名稱</label>
                                    <input type="text" class="form-control" id="institution_name"
                                        name="institution_name"
                                        value="<?php echo isset($_POST['institution_name']) ? htmlspecialchars($_POST['institution_name']) : ''; ?>">
                                    <div style="color: gray; margin-top: 5px;">
                                        *小提醒：一個醫療機構僅能申請一個帳號，請確認資料再申請!
                                    </div>
                                </div>
                            </div>
                            <div class="text-center mt-4">
                                <input type="submit" value="註冊" class="btn btn-primary w-100">
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePasswordVisibility(inputId, iconId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = document.getElementById(iconId);

            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                toggleIcon.classList.remove("fa-eye");
                toggleIcon.classList.add("fa-eye-slash");
            } else {
                passwordInput.type = "password";
                toggleIcon.classList.remove("fa-eye-slash");
                toggleIcon.classList.add("fa-eye");
            }
        }

        document.getElementById("confirmPassword").addEventListener("input", function () {
            const password = document.getElementById("password").value;
            const confirmPassword = this.value;

            if (confirmPassword === password) {
                // 移除無效類別並添加有效類別
                this.classList.remove("is-invalid");
                this.classList.add("is-valid");
            } else {
                // 移除有效類別並添加無效類別
                this.classList.remove("is-valid");
                this.classList.add("is-invalid");
            }
        });
        function toggleProofSection() {
            const userType = document.getElementById('userType').value;
            const proofSection = document.getElementById('proofSection');
            if (userType === 'hospital') {
                proofSection.style.display = 'block';
            } else {
                proofSection.style.display = 'none';
            }
        }

        window.onload = function () {
            document.getElementById('a').reset();

        };


        // 表單提交前的驗證
        document.getElementById('a').onsubmit = function () {
            const userType = document.getElementById('userType').value;
            if (userType === 'hospital') {
                const institutionName = document.getElementById('institution_name').value;
                if (!institutionName) {
                    alert('請填寫所有醫療機構的資訊。');
                    return false; // 取消提交
                }
            }
            return true; // 允許提交
        };
    </script>
</body>

</html>