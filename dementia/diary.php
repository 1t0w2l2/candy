<?php
session_start();
include "db.php";

// 檢查用戶是否登入
$account = isset($_SESSION['account']) ? $_SESSION['account'] : '';
if (empty($account)) {
    header("Location: login.php");
    exit();
}
$account = $_SESSION['account'];
$activities = [];
$sql_all_activities = "SELECT * FROM activity";
$result_all_activities = mysqli_query($link, $sql_all_activities);
if ($result_all_activities) {
    while ($row = mysqli_fetch_assoc($result_all_activities)) {
        foreach ($row as $key => $value) {
            if (!empty($value) && mb_strlen($value, 'UTF-8') > 50) {
                $row[$key] = mb_substr($value, 0, 50, 'UTF-8') . '...';
            }
        }
        $activities[] = $row;  // 不再保存到 session
    }
}

$sql = "SELECT a.activity_name, a.start_time, r.status 
FROM registration r 
JOIN activity a ON r.activity_id = a.activity_id 
WHERE r.account = '$account'";


// 定義顏色
$colors = ['#E7FAF8', '#D6E4FF'];
?>


<!doctype html>
<html lang="en">

<head>
    <?php include 'head.php'; ?>
</head>

<body>
   <?php include "nav.php";?>

    <!-- 原始代碼結束，以下是日記分享區表單的部分 -->
    <div class="container" style="margin-top: 80px;">
        <h2 >日記分享區</h2>

        <form action="diary_upload.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="diary_date">日記日期</label>
                <input type="date" id="diary_date" name="diary_date" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="diary_time">日記時間</label>
                <input type="time" id="diary_time" name="diary_time" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="mood_description">心情描述</label>
                <textarea id="mood_description" name="mood_description" class="form-control" rows="3" placeholder="描述您的心情..." required></textarea>
            </div>

            <div class="form-group">
                <label for="health_description">健康狀況描述</label>
                <textarea id="health_description" name="health_description" class="form-control" rows="3" placeholder="描述您的健康狀況..." required></textarea>
            </div>

            <div class="form-group">
                <label for="behavior_description">行為變化描述</label>
                <textarea id="behavior_description" name="behavior_description" class="form-control" rows="3" placeholder="描述您的行為變化..." required></textarea>
            </div>

            <div class="form-group">
                <label for="diary_photo">上傳照片</label>
                <input type="file" id="diary_photo" name="diary_photo" class="form-control-file" accept="image/*">
            </div>

            <button type="submit" class="btn btn-primary">提交日記</button>
        </form>
    </div>

</body>

</html>
