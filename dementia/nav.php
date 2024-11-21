<?php
include "db.php";
$account = isset($_SESSION['account']) ? $_SESSION['account'] : '';  
$sql = "SELECT user_type FROM user WHERE account = '$account'";  
$result = mysqli_query($link, $sql); 
if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);  
    $user_type = $row['user_type'];
} else {
    $user_type = null; 
}
// echo "<script type='text/javascript'>alert('" . $user_type . "');</script>";

?>

<!-- 漢堡選單 -->
<div class="site-mobile-menu site-navbar-target">
    <div class="site-mobile-menu-header">
        <div class="site-mobile-menu-close">
            <span class="icofont-close js-menu-toggle"></span>
        </div>
    </div>
    <div class="site-mobile-menu-body"></div>
</div>

<?php
if ($user_type == 'admin') {  // 管理員
?>
    <nav class="site-nav">
        <div class="container">
            <div class="site-navigation">
                <a href="index.php" class="logo m-0">失智守護系統<span class="text-primary">.</span></a>
                <ul class="js-clone-nav d-none d-lg-inline-block site-menu" style="float: right;">
                    <li><a href="index.php">資源地圖</a></li>
                    <li><a href="index_management.php">帳號管理</a></li>
                    <li><a href="activity_management.php">活動管理</a></li>
                    <li><a href="landmark.php">地標資料管理</a></li>
                    <li><a href="share.php">意見分享區</a></li>
                    <li><a href="share.php">回報錯誤管理</a></li>
                    <?php if (empty($account)): ?>
                        <li><a href="login.php">登入</a></li>
                    <?php else: ?>
                        <li class="has-children">
                            <a href="#"><?php echo htmlspecialchars($account); ?></a>
                            <ul class="dropdown">
                                <li><a href="personal_data.php">個人資料設定</a></li>
                                <li><a href="logout.php">登出</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
                <a href="#" class="burger ml-auto float-right site-menu-toggle js-menu-toggle d-inline-block d-lg-none light" data-toggle="collapse" data-target="#main-navbar" style="float: right;">
                    <span></span>
                </a>
            </div>
        </div>
    </nav>
<?php
} elseif ($user_type == 'caregiver' || $user_type == 'patient') {  // 照護者或病人
?>
    <nav class="site-nav">
        <div class="container">
            <div class="site-navigation">
                <a href="index.php" class="logo m-0">失智守護系統<span class="text-primary">.</span></a>
                <ul class="js-clone-nav d-none d-lg-inline-block site-menu" style="float: right;">
                    <li><a href="index.php">資源地圖</a></li>
                    <li class="has-children">
                        <a href="activity.php">活動</a>
                        <ul class="dropdown">
                            <li><a href="activity_registration.php">報名紀錄</a></li>
                        </ul>
                    </li>
                    <li class="has-children">
                        <a href="plan.php">行事曆</a>
                        <ul class="dropdown">
                            <li><a href="activity_registration.php">日記</a></li>
                        </ul>
                    </li>
                    <li class="has-children">
                        <a href="announcement.php">公告</a>
                        <ul class="dropdown">
                            <li><a href="chat.php">聊天室</a></li>
                        </ul>
                    </li>
                    <li><a href="share.php">意見分享區</a></li>
                    <li><a href="notification.php">通知</a></li>
                    <li><a href="notification.php">回報錯誤</a></li>
                    <?php if (empty($account)): ?>
                        <li><a href="login.php">登入</a></li>
                    <?php else: ?>
                        <li class="has-children">
                            <a href="#"><?php echo htmlspecialchars($account); ?></a>
                            <ul class="dropdown">
                                <li><a href="personal_data.php">個人資料設定</a></li>
                                <li><a href="logout.php">登出</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
                <a href="#" class="burger ml-auto float-right site-menu-toggle js-menu-toggle d-inline-block d-lg-none light" data-toggle="collapse" data-target="#main-navbar" style="float: right;">
                    <span></span>
                </a>
            </div>
        </div>
    </nav>
<?php
} else if($user_type == 'hospital') {  // 如果是其他類型用戶或未登入，則顯示簡單選單
?>
    <nav class="site-nav">
        <div class="container">
            <div class="site-navigation">
                <a href="index.php" class="logo m-0">失智守護系統<span class="text-primary">.</span></a>
                <ul class="js-clone-nav d-none d-lg-inline-block site-menu" style="float: right;">
                    <li><a href="index.php">資源地圖</a></li>
                    <li><a href="activity_management.php">活動管理</a></li>
                    <li class="has-children">
                        <a href="announcement.php">公告</a>
                        <ul class="dropdown">
                            <li><a href="chat.php">聊天室</a></li>
                        </ul>
                    </li>
                    <li><a href="share.php">意見分享區</a></li>
                    <li><a href="share.php">回報錯誤</a></li>
                    <?php if (empty($account)): ?>
                        <li><a href="login.php">登入</a></li>
                    <?php else: ?>
                        <li class="has-children">
                            <a href="#"><?php echo htmlspecialchars($account); ?></a>
                            <ul class="dropdown">
                                <li><a href="personal_data.php">個人資料設定</a></li>
                                <li><a href="logout.php">登出</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
                <a href="#" class="burger ml-auto float-right site-menu-toggle js-menu-toggle d-inline-block d-lg-none light" data-toggle="collapse" data-target="#main-navbar" style="float: right;">
                    <span></span>
                </a>
            </div>
        </div>
    </nav>
<?php
} else {
?>
   <nav class="site-nav">
        <div class="container">
            <div class="site-navigation">
                <a href="index.php" class="logo m-0">失智守護系統<span class="text-primary">.</span></a>
                <ul class="js-clone-nav d-none d-lg-inline-block site-menu" style="float: right;">
                    <li><a href="index.php">資源地圖</a></li>
                    <li><a href="activity.php">活動</a></li>
                    <li><a href="share.php">意見分享區</a></li>
                    <?php if (empty($account)): ?>
                        <li><a href="login.php">登入</a></li>
                    <?php else: ?>
                        <li class="has-children">
                            <a href="#"><?php echo htmlspecialchars($account); ?></a>
                            <ul class="dropdown">
                                <li><a href="personal_data.php">個人資料設定</a></li>
                                <li><a href="logout.php">登出</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
                <a href="#" class="burger ml-auto float-right site-menu-toggle js-menu-toggle d-inline-block d-lg-none light" data-toggle="collapse" data-target="#main-navbar" style="float: right;">
                    <span></span>
                </a>
            </div>
        </div>
    </nav>
<?php
}
?>
<script src="js/custom.js"></script>
<script src="js/bootstrap.min.js"></script>
