<?php
include "db.php";
$account = isset($_SESSION['account']) ? $_SESSION['account'] : '';
if (!$account) {
    header("Location: login.php");
    exit();
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
                                    readonly">
                            </div>
                            <div class="form-group mb-4">
                                <label for="new_password">密碼</label>
                                <input type="text" class="form-control" id="new_password" name="new_password"
                                    value="<?php echo isset($user['password']) ? htmlspecialchars($user['password']) : ''; ?>"
                                    readonly">
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
                            <div>
                                <button type="submit" class="btn btn-primary w-100" name="action"
                                    value="update">更新資料</button>
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
                            <p>Far far away, behind the word mountains, far from the countries
                                Vokalia and Consonantia, there live the blind texts.</p>
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
                                <li><a href="#"><span class="icon-vimeo"></span></a></li>
                                <li><a href="#"><span class="icon-youtube"></span></a></li>
                                <li><a href="#"><span class="icon-tiktok"></span></a></li>
                            </ul>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4 pl-lg-5">
                        <div class="widget">
                            <h3 class="heading">Contact</h3>
                            <ul class="list-unstyled quick-info links">
                                <li class="email"><a href="#">mail@example.com</a></li>
                                <li class="phone"><a href="#">+1 242 4942 290</a></li>
                                <li class="address"><a href="#">43 Raymouth Rd. Baltemoer, London 3910</a></li>
                            </ul>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="widget">
                            <h3 class="heading">Quick Links</h3>
                            <ul class="list-unstyled quick-info links">
                                <li><a href="#">About Us</a></li>
                                <li><a href="#">Terms of Use</a></li>
                                <li><a href="#">Disclaimers</a></li>
                                <li><a href="#">Contact</a></li>
                            </ul>
                        </div>
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
    <script>
        function clearInput(inputId) {
            document.getElementById(inputId).value = '';
        }
    </script>
</body>

</html>