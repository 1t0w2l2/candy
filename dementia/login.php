<?php
include "db.php";
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $account = mysqli_real_escape_string($link, $_POST['account']);
  $password = mysqli_real_escape_string($link, $_POST['password']);

  $sql_check_user = "SELECT * FROM user WHERE account = '$account' AND password = '$password'";
  $result_user = mysqli_query($link, $sql_check_user);

  if (mysqli_num_rows($result_user) > 0) {
    $user = mysqli_fetch_assoc($result_user);
    //echo "<script type='text/javascript'>alert('" . json_encode($user) . "');</script>";

    if ($user['email_status'] == 0) {
      header("Location: email.php");
      exit();
    }
    $_SESSION['account'] = $account;
    $_SESSION['user_type'] = $user['user_type'];


    if ($user['user_type'] == 'patient' || $user['user_type'] == 'caregiver') {
      header("Location: index.php");
    } else if ($user['user_type'] == 'hospital') {
      $sql = "SELECT * FROM `hospital` WHERE `account`='$account'";
      $rel = mysqli_query($link, $sql);


      if (mysqli_num_rows($rel) > 0) {
        $hospital = mysqli_fetch_assoc($rel);
        $_SESSION['institution_id'] = $hospital['institution_id'];
        header("Location: index_management.php");
      } else {
        echo "<script type='text/javascript'>alert('帳號錯誤，請聯絡管理員');window.location.href = 'login.php';</script>";

      }

    } else if ($user['user_type'] == 'admin') {
      header( "Location: index_management.php");
    } else {
      $error_message = "無效的用戶類型";
    }

    exit();
  } else {
    $error_message = "帳號或密碼錯誤";
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
          <div class="col-lg-5">
            <div class="card p-4 shadow-sm">
              <h1 class="mb-4 text-center" style="color: #000;">登入</h1>
              <form action="login.php" method="post">
                <div class="form-group mb-3"> <!-- Added mb-3 for space below this div -->
                  <label for="account">帳號</label>
                  <input type="text" class="form-control" id="account" name="account" required>
                </div>
                <div class="form-group mb-4"> <!-- Added mb-4 for more space below this div -->
                  <label for="password">密碼</label>
                  <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">登入</button>
              </form>
              <?php
              if ($error_message) {
                echo "<p class='mt-3 text-center' style='color: red;'>$error_message</p>";
              }
              ?>
              <p class="mt-3 text-center"><a href="forgetpassword.php">忘記密碼</a></p>
              <p class="mt-4 text-center"><a href="register.php">註冊帳號</a></p>
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
                <p>Far far away, behind the word mountains, far from the countries Vokalia and Consonantia, there live
                  the
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
                love by <a href="https://untree.co" class="link-highlight">Untree.co</a>
                <!-- License information: https://untree.co/license/ --> Distributed By <a href="https://themewagon.com"
                  target="_blank">ThemeWagon</a>
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