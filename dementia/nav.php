

    <!-- 漢堡選單 -->
    <div class="site-mobile-menu site-navbar-target">
        <div class="site-mobile-menu-header">
            <div class="site-mobile-menu-close">
                <span class="icofont-close js-menu-toggle"></span>
            </div>
        </div>
        <div class="site-mobile-menu-body"></div>
    </div>

    <nav class="site-nav">
        <div class="container">
            <div class="site-navigation">
                <a href="index.php" class="logo m-0">失智守護系統<span class="text-primary">.</span></a>

                <ul class="js-clone-nav d-none d-lg-inline-block site-menu" style="float: right;">
                    <li><a href="index.php">首頁</a></li>
                    <li class="has-children">
                    <a href="activity.php">活動</a>
                    <ul class="dropdown">
                        <li><a href="activity_registration.php">報名紀錄</a></li>
                    </ul>
                </li>
                    <li><a href="activity_management.php">活動管理</a></li>
                    <li><a href="share.php">意見分享區</a></li>
                    <li><a href="chat.php">互動區</a></li>
                    <li><a href="plan.php">行事曆</a></li>
                    <li><a href="diary.php">日記</a></li>
                    <li><a href="notification.php">通知</a></li>
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

                <a href="#"
                    class="burger ml-auto float-right site-menu-toggle js-menu-toggle d-inline-block d-lg-none light"
                    data-toggle="collapse" data-target="#main-navbar" style="float: right;">
                    <span></span>
                </a>

            </div>
        </div>
    </nav>
    <script src="js/custom.js"></script>
