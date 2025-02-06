<?php
include "db.php";

// 查詢 institution 和 servicetime 表的資料
$sql = "
SELECT i.institution_id, i.institution_name, i.address, i.phone, i.website, 
       GROUP_CONCAT(CONCAT(s.day, ': ', s.open_time, ' - ', s.close_time) 
       ORDER BY FIELD(s.day, '星期一', '星期二', '星期三', '星期四', '星期五', '星期六', '星期日') SEPARATOR '; ') AS servicetime 
FROM institution i
LEFT JOIN servicetime s ON i.institution_id = s.institution_id
GROUP BY i.institution_id";

$result = mysqli_query($link, $sql);

// 將資料存入一個陣列以便於 JavaScript 使用
$institutions = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $institutions[$row['institution_name']] = $row; // 使用 institution_name 作為鍵
    }
}

// 查詢已取消的回報資料
$canceledMistakes = [];
$sql_canceled_mistake = "SELECT * FROM mistake WHERE status = '已取消'";
$result_canceled_mistake = mysqli_query($link, $sql_canceled_mistake);
if ($result_canceled_mistake->num_rows > 0) {
    while ($row = $result_canceled_mistake->fetch_assoc()) {
        $canceledMistakes[] = $row; // 將每一行資料存入陣列
    }
}

// 查詢 mistake 表的資料，過濾狀態
$mistakes = [];
$sql_mistake = "SELECT * FROM mistake WHERE status IN ('待審核', '審核中', '已審核')"; // 只查詢這三種狀態

// 檢查是否有搜尋條件
if (isset($_GET['search_input']) && $_GET['search_input'] !== '') {
    $search_input = mysqli_real_escape_string($link, $_GET['search_input']);
    $sql_mistake .= " AND institution_name LIKE '%$search_input%'";
}

if (isset($_GET['start_date']) && $_GET['start_date'] !== '') {
    $start_date = mysqli_real_escape_string($link, $_GET['start_date']);
    $sql_mistake .= " AND report_datetime >= '$start_date'";
}

if (isset($_GET['end_date']) && $_GET['end_date'] !== '') {
    $end_date = mysqli_real_escape_string($link, $_GET['end_date']);
    $sql_mistake .= " AND report_datetime <= '$end_date'";
}

$sql_mistake .= " ORDER BY report_datetime DESC"; // 根據報告時間排序
$result_mistake = mysqli_query($link, $sql_mistake);

if ($result_mistake->num_rows > 0) {
    while ($row = $result_mistake->fetch_assoc()) {
        $mistakes[] = $row; // 將每一行資料存入陣列
    }
}

// 處理表單提交
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['cancel_registration'])) {
        // 取消回報的邏輯
        $mistake_id = mysqli_real_escape_string($link, $_POST['mistake_id']);

        // 更新 mistake 表中的狀態為「已取消」
        $update_sql = "UPDATE mistake SET status = '已取消' WHERE mistake_id = '$mistake_id'";
        if (mysqli_query($link, $update_sql)) {
            echo "<script>alert('回報已取消！'); window.location.href='mistake.php';</script>";
            exit();
        } else {
            echo "<script>alert('取消回報失敗： " . mysqli_error($link) . "');</script>";
        }
    } else {
        // 新增回報的邏輯
        $institution_id = mysqli_real_escape_string($link, $_POST['institution_id']);
        $institution_name = mysqli_real_escape_string($link, $_POST['institution_name']);
        $address = mysqli_real_escape_string($link, $_POST['address']);
        $phone = mysqli_real_escape_string($link, $_POST['phone']);
        $website = mysqli_real_escape_string($link, $_POST['website']);

        // 插入回報資料到 mistake 表，並記錄回報時間，狀態預設為「待審核」
        $insert_sql = "INSERT INTO mistake (institution_id, institution_name, address, phone, website, report_datetime, status) 
                       VALUES ('$institution_id', '$institution_name', '$address', '$phone', '$website', NOW(), '待審核')";

        if (mysqli_query($link, $insert_sql)) {
            // 獲取剛插入的 mistake_id
            $mistake_id = mysqli_insert_id($link);

            // 確保 servicetime 是一個陣列
            if (isset($_POST['servicetime']) && is_array($_POST['servicetime'])) {
                foreach ($_POST['servicetime'] as $day => $time) {
                    // 預設變數值
                    $open_time = '00:00:00';
                    $close_time = '00:00:00';

                    // 檢查是否勾選24小時營業
                    if (isset($time['24hours']) && $time['24hours'] == 'on') {
                        // 24小時營業設定為 00:00:00 - 00:00:00
                        $open_time = '00:00:00';
                        $close_time = '00:00:00';
                    } else {
                        // 正常處理修改的時間
                        if (isset($time['open']) && isset($time['close'])) {
                            $open_time = mysqli_real_escape_string($link, $time['open']);
                            $close_time = mysqli_real_escape_string($link, $time['close']);
                        }
                    }

                    // 插入營業時間
                    $insert_servicetime_sql = "
                        INSERT INTO mistake_servicetime (mistake_id, day, open_time, close_time) 
                        VALUES ('$mistake_id', '$day', '$open_time', '$close_time')";
                    if (!mysqli_query($link, $insert_servicetime_sql)) {
                        echo "<script>alert('插入營業時間失敗： " . mysqli_error($link) . "');</script>";
                    }
                }
            }

            // 提示成功訊息並重定向回 mistake.php
            echo "<script>alert('回報成功！'); window.location.href='mistake.php';</script>";
            exit();
        } else {
            echo "<script>alert('回報失敗： " . mysqli_error($link) . "');</script>";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <?php include "head.php"; ?>
    <script>
        // 將 PHP 陣列轉換為 JavaScript 物件
        const institutions = <?php echo json_encode($institutions); ?>;

        function fillInstitutionData() {
            const institutionName = document.getElementById('institution_name').value;
            const timeSettingsContainer = document.getElementById('day-settings');
            timeSettingsContainer.innerHTML = ''; // 清空現有內容

            if (institutions[institutionName]) {
                const data = institutions[institutionName];
                document.getElementById('address').value = data.address;
                document.getElementById('phone').value = data.phone;
                document.getElementById('website').value = data.website;
                document.getElementById('institution_id').value = data.institution_id; // 填寫機構代碼

                // 確保營業時間的每一天都有輸入框
                const daysOfWeek = ['星期一', '星期二', '星期三', '星期四', '星期五', '星期六', '星期日'];
                const servicetime = data.servicetime ? data.servicetime.split('; ') : [];

                daysOfWeek.forEach(day => {
                    const timeEntry = servicetime.find(entry => entry.startsWith(`${day}: `));
                    const timeRange = timeEntry ? timeEntry.split(': ')[1] : null;
                    const [openTime, closeTime] = timeRange ? timeRange.split(' - ') : ['', ''];

                    // 檢查是否為 24 小時
                    const is24Hours = (openTime.trim() === '00:00:00' && closeTime.trim() === '24:00:00');

                    // 建立輸入框
                    const timeSlotDiv = document.createElement('div');
                    timeSlotDiv.className = 'time-slot';
                    timeSlotDiv.innerHTML = `
                        <div style="margin-bottom: 10px;">
                            <label>${day}</label>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <input type="time" name="servicetime[${day}][open]" value="${is24Hours ? '' : openTime.trim()}" ${is24Hours ? 'disabled' : ''} required>
                                <span>至</span>
                                <input type="time" name="servicetime[${day}][close]" value="${is24Hours ? '' : closeTime.trim()}" ${is24Hours ? 'disabled' : ''} required>
                                <label style="margin-left: 10px;">
                                    <input type="checkbox" name="servicetime[${day}][24hours]" onchange="toggle24Hours(this)" ${is24Hours ? 'checked' : ''}>
                                    24 小時營業
                                </label>
                            </div>
                        </div>
                    `;
                    timeSettingsContainer.appendChild(timeSlotDiv);
                });
            } else {
                // 無資料時，生成空的輸入框
                const daysOfWeek = ['星期一', '星期二', '星期三', '星期四', '星期五', '星期六', '星期日'];
                daysOfWeek.forEach(day => {
                    const timeSlotDiv = document.createElement('div');
                    timeSlotDiv.className = 'time-slot';
                    timeSlotDiv.innerHTML = `
                        <div style="margin-bottom: 10px;">
                            <label>${day}</label>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <input type="time" name="servicetime[${day}][open]" value="" required>
                                <span>至</span>
                                <input type="time" name="servicetime[${day}][close]" value="" required>
                                <label style="margin-left: 10px;">
                                    <input type="checkbox" name="servicetime[${day}][24hours]" onchange="toggle24Hours(this)">
                                    24 小時營業
                                </label>
                            </div>
                        </div>
                    `;
                    timeSettingsContainer.appendChild(timeSlotDiv);
                });

                // 清空其他欄位
                document.getElementById('address').value = '';
                document.getElementById('phone').value = '';
                document.getElementById('website').value = '';
                document.getElementById('institution_id').value = ''; // 清空機構代碼
            }
        }

        // 切換 24 小時營業
        function toggle24Hours(checkbox) {
            const timeInputs = checkbox.closest('div').querySelectorAll('input[type="time"]');
            if (checkbox.checked) {
                timeInputs.forEach(input => {
                    input.disabled = true;
                    input.value = ''; // 清空時間
                });
            } else {
                timeInputs.forEach(input => {
                    input.disabled = false;
                });
            }
        }
    </script>
</head>

<body>
    <?php include "nav.php"; ?>

    <div class="s4-container">
        <div class="filter-section">
            <h2>回報錯誤</h2>
            <form action="" method="GET">
                <div class="mb-3" style="width:100%">
                    <input type="text" name="search_input" placeholder="請輸入關鍵字"
                        value="<?php echo isset($_GET['search_input']) ? htmlspecialchars($_GET['search_input']) : ''; ?>">
                    <input type="date" name="start_date" placeholder="活動日期"
                        value="<?php echo isset($_GET['start_date']) ? htmlspecialchars($_GET['start_date']) : ''; ?>">
                </div>
                <div class="mb-3" style="width:100%">
                    <button type="submit" class="btn" name="action" value="search" style="background-color:#9dc7c9;">
                        <i class="fa fa-search"></i> 搜尋
                    </button>
                </div>
            </form>

            <?php
            $account = isset($_SESSION['account']) ? $_SESSION['account'] : '';
            if ($account): ?>
                <div class="mb-3">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"
                        style="background-color:#cebca6;">
                        <i class="fa-solid fa-plus"></i> 新增回報錯誤
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <div id="registrationRecords">
            <div class="container">
                <div class="main-container">

                    <!-- 新增回報錯誤的 Modal -->
                    <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel"
                        aria-hidden="true" data-bs-backdrop="static">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="addModalLabel">新增回報錯誤</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form action="" method="POST">
                                        <div class="mb-3">
                                            <label for="institution_name" class="form-label">醫療機構名稱</label>
                                            <input type="text" class="form-control" list="options0"
                                                id="institution_name" name="institution_name" placeholder="輸入機構名稱"
                                                required onchange="fillInstitutionData()">
                                            <datalist id="options0">
                                                <?php
                                                // 將資料庫中的資料填入 <option>
                                                foreach ($institutions as $name => $data) {
                                                    echo '<option>' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</option>';
                                                }
                                                ?>
                                            </datalist>
                                        </div>
                                        <input type="hidden" id="institution_id" name="institution_id">
                                        <div class="mb-3">
                                            <label for="address" class="form-label">地址</label>
                                            <input type="text" class="form-control" id="address" name="address"
                                                required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="phone" class="form-label">電話</label>
                                            <input type="tel" class="form-control" id="phone" name="phone" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="website" class="form-label">網站</label>
                                            <input type="url" class="form-control" id="website" name="website">
                                        </div>
                                        <div class="mb-3">
                                            <label for="servicetime" class="form-label">營業時間</label>
                                            <div id="day-settings" class="day-settings">
                                                <!-- 動態生成的營業時間輸入框 -->
                                            </div>
                                        </div>

                                        <div class="modal-footer">
                                            <button type="submit" class="btn btn-primary">送出回報</button>
                                            <button type="button" class="btn btn-secondary"
                                                data-bs-dismiss="modal">取消</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <ul class="nav nav-tabs" id="tab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="tab1" data-bs-toggle="tab" data-bs-target="#content1"
                                type="button" role="tab" aria-controls="content1" aria-selected="true">回報成功</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab2" data-bs-toggle="tab" data-bs-target="#canceledReports"
                                type="button" role="tab" aria-controls="canceledReports"
                                aria-selected="false">取消回報</button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="content1" role="tabpanel" aria-labelledby="tab1">
                            <div id="successfulReports">
                                <?php if (!empty($mistakes)): ?>
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>機構名稱</th>
                                                <th>回報時間</th>
                                                <th>審核狀態</th>
                                                <th>操作</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($mistakes as $mistake): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($mistake['institution_name'], ENT_QUOTES, 'UTF-8'); ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($mistake['report_datetime'], ENT_QUOTES, 'UTF-8'); ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($mistake['status'], ENT_QUOTES, 'UTF-8'); ?>
                                                    </td>
                                                    <td>
                                                        <form action="" method="POST" style="display:inline;">
                                                            <input type="hidden" name="mistake_id"
                                                                value="<?php echo htmlspecialchars($mistake['mistake_id'], ENT_QUOTES, 'UTF-8'); ?>">
                                                            <button type="submit" name="cancel_registration" class="s3-button"
                                                                onclick="return confirm('確定要取消回報');">
                                                                <i class="fa-regular fa-square-minus"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <p>目前尚無回報成功的資料。</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="canceledReports" role="tabpanel" aria-labelledby="tab2">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>機構名稱</th>
                                        <th>回報時間</th>
                                        <th>狀態</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($canceledMistakes)): ?>
                                        <?php foreach ($canceledMistakes as $mistake): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($mistake['institution_name'], ENT_QUOTES, 'UTF-8'); ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($mistake['report_datetime'], ENT_QUOTES, 'UTF-8'); ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($mistake['status'], ENT_QUOTES, 'UTF-8'); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="2">沒有已取消的回報。</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- 引入 Bootstrap 的 JS -->
                <script
                    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

            </div>
        </div>
    </div>
</body>

</html>