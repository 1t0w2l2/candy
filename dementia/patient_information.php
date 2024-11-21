<?php
session_start();
include "db.php";
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 获取 POST 数据
    $account = isset($_SESSION['account']) ? $_SESSION['account'] : null;
    $birthday = $_POST['birthday'];
    $emergency_name = $_POST['emergency_name'];
    $emergency_phone = $_POST['emergency_phone'];
    $user_type = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : null;

    if ($account && $user_type) {
        // 檢查帳戶是否存在並且用戶類型匹配
        $sql_check = "SELECT * FROM user WHERE account = '$account' AND user_type = '$user_type'";
        $result = mysqli_query($link, $sql_check);

        if ($result && mysqli_num_rows($result) > 0) {
            // 插入患者資料
            $sql_patient = "INSERT INTO patient (account, birthday, emergency_name, emergency_phone) 
                            VALUES ('$account', '$birthday', '$emergency_name', '$emergency_phone')";
            if (mysqli_query($link, $sql_patient)) {
                // 插入成功，清理會話數據並重定向
                unset($_SESSION['account']);
                unset($_SESSION['usertype']);
                echo "<script>alert('註冊成功');window.location.href = 'index.php';</script>";
                exit();
            } else {
                $message = '插入患者資料失敗：' . mysqli_error($link);
            }
        } else {
            $message = '沒有此帳戶或帳戶類型不匹配，請註冊帳戶';
        }
    } else {
        $message = '帳戶尚未註冊';
    }
    $link->close();
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
                <div class="col-lg-8">
                    <div class="card p-4 shadow-sm">
                        <h1 class="mb-4 text-center" style="color: #000;">患者資料</h1>
                        <form action="" method="post" id="form" name="form">
                            <div class="form-group mb-4">
                                <label for="account">帳號：</label>
                                <input type="text" class="form-control" id="account" name="account"
                                    value="<?php echo htmlspecialchars($_SESSION['account']); ?>" readonly>
                            </div>
                            <div class="form-group mb-4">
                                <label for="birthday">生日</label>
                                <input type="date" class="form-control" id="birthday" name="birthday" required>
                            </div> 
                            <div class="form-group mb-4">
                                <label for="emergency_name">緊急聯絡人</label>
                                <input type="text" class="form-control" id="emergency_name" name="emergency_name"
                                    required>
                            </div>
                            <div class="form-group mb-4">
                                <label for="emergency_phone">緊急聯絡人電話</label>
                                <input type="text" class="form-control" id="emergency_phone" name="emergency_phone"
                                    required>
                            </div>
                            <div class="text-center mb-4">
                                <input type="submit" value="送出" class="btn btn-primary w-100">
                            </div>
                        </form>
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
                            <p>Far far away, behind the word mountains, far from the countries Vokalia and Consonantia,
                                there live the blind texts.</p>
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
                            <script>document.write(new Date().getFullYear());</script>. All Rights Reserved. &mdash;
                            Designed with love by <a href="https://untree.co" class="link-highlight">Untree.co</a>
                            <!-- License information: https://untree.co/license/ -->Distributed By <a
                                href="https://themewagon.com" target="_blank">ThemeWagon</a>
                        </p>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script src="js/jquery-3.4.1.min.js"></script>
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
