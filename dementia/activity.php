<?php
session_start();
include "db.php";

// 檢查用戶是否登入
// $account = isset($_SESSION['account']) ? $_SESSION['account'] : '';
// if (empty($account)) {
//     header("Location: login.php");
//     exit();
// }

// 取得所有活動
$activities = [];

// 檢查是否有GET參數
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$city = isset($_GET['city']) ? trim($_GET['city']) : '';
$start_date = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';

$query = "SELECT a.*, COUNT(j.join_id) AS registered_count FROM activity a LEFT JOIN join_activity j ON a.activity_id = j.activity_id WHERE 1=1";

// 根據輸入的條件進行過濾
if (!empty($keyword)) {
    $query .= " AND (activity_name LIKE '%" . mysqli_real_escape_string($link, $keyword) . "%' OR
                     description LIKE '%" . mysqli_real_escape_string($link, $keyword) . "%' OR
                     location LIKE '%" . mysqli_real_escape_string($link, $keyword) . "%' OR
                     contact_person LIKE '%" . mysqli_real_escape_string($link, $keyword) . "%' OR
                     contact_phone LIKE '%" . mysqli_real_escape_string($link, $keyword) . "%' OR
                     institution_id LIKE '%" . mysqli_real_escape_string($link, $keyword) . "%' OR
                     status LIKE '%" . mysqli_real_escape_string($link, $keyword) . "%')";
}
if (!empty($city)) {
    $query .= " AND address LIKE '%" . mysqli_real_escape_string($link, $city) . "%'";
}

if (!empty($start_date)) {
    $query .= " AND start_time >= '" . mysqli_real_escape_string($link, $start_date) . "'";
}
if (!empty($end_date)) {
    $query .= " AND end_time <= '" . mysqli_real_escape_string($link, $end_date) . "'";
}
// 取得今天的日期
$query = "SELECT a.*, COUNT(j.join_id) AS registered_count FROM activity a LEFT JOIN join_activity j ON a.activity_id = j.activity_id WHERE 1=1";

// 根據輸入的條件進行過濾
if (!empty($keyword)) {
    $query .= " AND (activity_name LIKE '%" . mysqli_real_escape_string($link, $keyword) . "%' OR
                     description LIKE '%" . mysqli_real_escape_string($link, $keyword) . "%' OR
                     location LIKE '%" . mysqli_real_escape_string($link, $keyword) . "%' OR
                     contact_person LIKE '%" . mysqli_real_escape_string($link, $keyword) . "%' OR
                     contact_phone LIKE '%" . mysqli_real_escape_string($link, $keyword) . "%' OR
                     institution_id LIKE '%" . mysqli_real_escape_string($link, $keyword) . "%' OR
                     status LIKE '%" . mysqli_real_escape_string($link, $keyword) . "%')";
}
if (!empty($city)) {
    $query .= " AND address LIKE '%" . mysqli_real_escape_string($link, $city) . "%'";
}
if (!empty($start_date)) {
    $query .= " AND start_time >= '" . mysqli_real_escape_string($link, $start_date) . "'";
}
if (!empty($end_date)) {
    $query .= " AND end_time <= '" . mysqli_real_escape_string($link, $end_date) . "'";
}

// 取得今天的日期
$current_date = date('Y-m-d');

// 加入過濾條件
// $query .= " AND start_time >= '" . mysqli_real_escape_string($link, $current_date) . "'";
// $query .= " AND registration_deadline >= '" . mysqli_real_escape_string($link, $current_date) . "'";
$query .= " AND status != '報名截止'"; // 過濾狀態為報名截止的活動

$query .= " GROUP BY a.activity_id"; // 確保在此時進行分組


$result = mysqli_query($link, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        foreach ($row as $key => $value) {
            if (!empty($value) && mb_strlen($value, 'UTF-8') > 50) {
                $row[$key] = mb_substr($value, 0, 50, 'UTF-8') . '...';
            }
        }
        $activities[] = $row; // 將查詢結果放入陣列
    }
} else {
    echo "查詢錯誤: " . mysqli_error($link);
}
?>


<!doctype html>
<html lang="en">

<head>
    <?php include 'head.php'; ?>
</head>

<body>

<?php include "nav.php"; ?>
    <div class="s4-container">
        <div class="filter-section">
            <h2 style="text-align: center;">活動查詢</h2>
            <form action="" method="GET" class="input-group">
                <div class="mb-3" style="width:100%">
                    <input type="text" name="keyword" placeholder="請輸入關鍵字" value="<?php echo htmlspecialchars($keyword); ?>">
                </div>
                <div class="mb-3" style="width:100%">
                    <select name="city" id="city-select">
                        <option value="" disabled <?php echo empty($city) ? 'selected' : ''; ?>>縣市</option>
                        <?php
                        $cities = [
                            "基隆市", "新北市", "台北市", "桃園市", "台中市", "台南市", "高雄市",
                            "新竹市", "嘉義市", "苗栗縣", "彰化縣", "南投縣", "雲林縣", "嘉義縣",
                            "屏東縣", "台東縣", "花蓮縣", "澎湖縣", "金門縣", "連江縣"
                        ];

                        foreach ($cities as $cityOption) {
                            echo "<option value='" . htmlspecialchars($cityOption) . "' " . ($city === $cityOption ? 'selected' : '') . ">" . htmlspecialchars($cityOption) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="mb-3" style="width:100%">
                    <input type="date" name="start_date" placeholder="開始日期" value="<?php echo htmlspecialchars($start_date); ?>">
                </div>
                <div class="mb-3" style="width:100%">
                    <input type="date" name="end_date" placeholder="結束日期" value="<?php echo htmlspecialchars($end_date); ?>">
                </div>
                <div class="mb-3" style="width:100%">
                    <button type="submit" class="btn">
                        <i class="fa fa-search"></i> 搜尋
                    </button>
                </div>
            </form>
        </div>

        <div class="results-section">
            <?php if (!empty($activities)): ?>
                <?php foreach ($activities as $activity): ?>
                    <div class="result-card" style="display: flex; align-items: flex-start; margin-bottom: 20px;">
                        <div style="flex: 0 0 150px; margin-right: 15px;">
                            <img src="<?php echo htmlspecialchars('./activity/' . $activity['activity_image_name']); ?>"
                                 alt="<?php echo htmlspecialchars($activity['activity_name']); ?>"
                                 style="width: 100%; height: auto; border-radius: 5px;">
                        </div>
                        <div style="flex: 1;">
                            <div class="card-header">
                                <h3><?php echo htmlspecialchars($activity['activity_name']); ?></h3>
                                <span class="location"><?php echo htmlspecialchars($activity['registered_count']); ?>/<?php echo htmlspecialchars($activity['max_participants']); ?></span>
                            </div>
                            <p>活動起訖：<?php echo htmlspecialchars($activity['start_time']); ?>~<?php echo htmlspecialchars($activity['end_time']); ?></p>
                            <p>活動地點：<?php echo htmlspecialchars($activity['location']); ?>(地址：<?php echo htmlspecialchars($activity['address']); ?>)</p>
                            <p>報名截止時間：<?php echo htmlspecialchars($activity['registration_deadline']); ?></p>
                            <div style="text-align: right;">
                                <?php if ($activity['registered_count'] >= $activity['max_participants']): ?>
                                    <span class="btn danger" style="color:red;">報名人數已達上限</span>
                                <?php else: ?>
                                    <form action="registration.php" method="POST" style="display: inline;">
                                        <input type="hidden" name="activity_id"
                                               value="<?php echo htmlspecialchars($activity['activity_id']); ?>">
                                        <button type="submit" class="btn">報名</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center">尚無活動可報名</div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>
</body>

</html>