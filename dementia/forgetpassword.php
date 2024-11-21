<?php
include "db.php";
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $input = mysqli_real_escape_string($link, $_POST['account']);
  $_SESSION['input'] = $input;

  // 判斷輸入是否是 email
  if (filter_var($input, FILTER_VALIDATE_EMAIL)) {
    // 輸入的是 email
    $sql_account = "SELECT * FROM user WHERE email = '$input'";
  } else {
    // 輸入的是帳號
    $sql_account = "SELECT * FROM user WHERE account = '$input'";
  }

  $result_account = mysqli_query($link, $sql_account);

  if (mysqli_num_rows($result_account) > 0) {
    // 取得使用者資料
    $user = mysqli_fetch_assoc($result_account);
    $account = $user['account'];
    $password = $user['password'];
    $email = $user['email'];
    $name = $user['name'];

    // 發送帳號密碼訊息
    send_verification_email($email, $name, $account, $password);

    // 可以在這裡存儲驗證碼，便於後續驗證，這裡簡單的儲存到 session
    $_SESSION['email'] = $email;
    $_SESSION['account'] = $account;
    $_SESSION['password'] = $password;
    header("Location: login.php");
    exit();
  } else {
    $error_message = "帳號或郵件不存在";
  }
}


function send_verification_email($email, $name, $account, $password)
{
  require 'src/Exception.php';
  require 'src/PHPMailer.php';
  require 'src/SMTP.php';

  $mail = new PHPMailer\PHPMailer\PHPMailer(true);

  try {
    $message1 = "{$name} 您好！<br>感謝您使用我們的系統。以下是您的帳號資訊：<br>帳號：{$account}<br>密碼：{$password}<br>請妥善保管這些資訊。如果您有任何問題或需要協助，請隨時聯繫我們。";
    $title = "您的帳號資訊";

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
    echo "郵件發送失敗：" . $mail->ErrorInfo;
  }
}
?>
<!doctype html>
<html lang="en">

<head>
  <?php
  include 'head.php';
  ?>
</head>

<body>

<?php include "nav.php"; ?>

  <div class="hero hero-inner">
    <div class="container">
      <div class="row align-items-center justify-content-center">
        <div class="col-lg-6">
          <div class="card p-4 shadow-sm">
            <h1 class="mb-4 text-center" style="color: #000;">忘記帳號密碼</h1>
            <form action="forgetpassword.php" method="post" class="mb-4">
              <div class="form-group mb-4">
                <label for="account">帳號或email</label>
                <input type="text" class="form-control" id="account" name="account" required>
              </div>
              <button type="submit" class="btn btn-primary w-100">傳送訊息</button>
            </form>
            <?php
            if ($error_message) {
              echo "<p class='mt-3 text-center' style='color: red;'>$error_message</p>";
            }
            ?>
          </div>
        </div>
      </div>
    </div>
  </div>


  <div class="site-footer">
    <div class="inner first">
      <div class="container">
        <div class="row">
          <div class="col-md-6 col-lg-4">
            <div class="widget">
              <h3 class="heading">About Tour</h3>
              <p>Far far away, behind the word mountains, far from the countries Vokalia and Consonantia, there live the
                blind texts.</p>
            </div>
            <div class="widget">
              <ul class="list-unstyled social">
                <li><a href="#"><span class="icon-twitter"></span></a></li>
                <li><a href="#"><span class="icon-instagram"></span></a></li>
                <li><a href="#"><span class="icon-facebook"></span></a></li>
                <li><a href="#"><span class="icon-linkedin"></span></a></li>
                <li><a href="#"><span class="icon-dribbble"></span></a></li>
                <li><a href="#"><span class="icon-pinterest"></span></a></li>
                <li><a href="#"><span class="icon-apple"></span></a></li>
                <li><a href="#"><span class="icon-google"></span></a></li>
              </ul>
            </div>
          </div>
          <div class="col-md-6 col-lg-2 pl-lg-5">
            <div class="widget">
              <h3 class="heading">Pages</h3>
              <ul class="links list-unstyled">
                <li><a href="#">Blog</a></li>
                <li><a href="#">About</a></li>
                <li><a href="#">Contact</a></li>
              </ul>
            </div>
          </div>
          <div class="col-md-6 col-lg-2">
            <div class="widget">
              <h3 class="heading">Resources</h3>
              <ul class="links list-unstyled">
                <li><a href="#">Blog</a></li>
                <li><a href="#">About</a></li>
                <li><a href="#">Contact</a></li>
              </ul>
            </div>
          </div>
          <div class="col-md-6 col-lg-4">
            <div class="widget">
              <h3 class="heading">Contact</h3>
              <ul class="list-unstyled quick-info links">
                <li class="email"><a href="#">mail@example.com</a></li>
                <li class="phone"><a href="#">+1 222 212 3819</a></li>
                <li class="address"><a href="#">43 Raymouth Rd. Baltemoer, London 3910</a></li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="inner dark">
      <div class="container">
        <div class="row text-center">
          <div class="col-md-8 mb-3 mb-md-0 mx-auto">
            <p>Copyright &copy;
              <script>document.write(new Date().getFullYear());</script>. All Rights Reserved. &mdash; Designed with
              love by <a href="https://untree.co" class="link-highlight">Untree.co</a> Distributed By <a
                href="https://themewagon.com" target="_blank">ThemeWagon</a>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div id="overlayer"></div>
  <div class="loader">
    <div class="spinner-border" role="status">
      <span class="sr-only"></span>
    </div>
  </div>

  <script src="js/jquery-3.4.1.min.js"></script>
  <script src="js/popper.min.js"></script>
  <script src="js/bootstrap.min.js"></script>
  <script src="js/owl.carousel.min.js"></script>
  <script src="js/jquery.animateNumber.min.js"></script>
  <script src="js/jquery.waypoints.min.js"></script>
  <script src="js/jquery.fancybox.min.js"></script>
  <script src="js/aos.js"></script>
  <script src="js/moment.min.js"></script>
  <script src="js/daterangepicker.js"></script>
  <script src="js/typed.js"></script>
  <script src="js/custom.js"></script>

</body>

</html>