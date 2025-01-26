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

// 處理表單提交
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $institution_id = mysqli_real_escape_string($link, $_POST['institution_id']);
    $institution_name = mysqli_real_escape_string($link, $_POST['institution_name']);
    $address = mysqli_real_escape_string($link, $_POST['address']);
    $phone = mysqli_real_escape_string($link, $_POST['phone']);
    $website = mysqli_real_escape_string($link, $_POST['website']);

    // 插入回報資料到 mistake 表，並記錄回報時間
    $insert_sql = "INSERT INTO mistake (institution_id, institution_name, address, phone, website, report_datetime) 
                   VALUES ('$institution_id', '$institution_name', '$address', '$phone', '$website', NOW())";

    if (mysqli_query($link, $insert_sql)) {
        // 獲取剛插入的 mistake_id
        $mistake_id = mysqli_insert_id($link);  // 獲取插入的 mistake_id

        // 確保 servicetime 是一個陣列
        if (isset($_POST['servicetime']) && is_array($_POST['servicetime'])) {
            foreach ($_POST['servicetime'] as $day => $time) {
                // 預設變數值
                $open_time = null;
                $close_time = null;

                // 檢查是否勾選24小時營業
                if (isset($_POST['is_24h']) && $_POST['is_24h'] == 1) {
                    // 24小時營業設定為 00:00 - 00:00
                    $open_time = '00:00';
                    $close_time = '00:00';
                } else {
                    // 正常處理修改的時間
                    if (isset($time['open']) && isset($time['close'])) {
                        $open_time = mysqli_real_escape_string($link, $time['open']);
                        $close_time = mysqli_real_escape_string($link, $time['close']);
                    }
                }

                // 如果沒有設置開放時間或關閉時間，則跳過該天的更新
                if ($open_time === null || $close_time === null) {
                    continue;  // 這裡跳過不處理的日期
                }

                // 查詢現有的營業時間
                $check_sql = "SELECT * FROM mistake_servicetime WHERE mistake_id = '$mistake_id' AND day = '$day'";
                $check_result = mysqli_query($link, $check_sql);

                // 如果查詢到該紀錄，執行更新
                if (mysqli_num_rows($check_result) > 0) {
                    // 營業時間已存在，檢查是否需要更新
                    $existing_row = mysqli_fetch_assoc($check_result);
                    if ($existing_row['open_time'] !== $open_time || $existing_row['close_time'] !== $close_time) {
                        // 如果營業時間已經改變，更新該行
                        $update_sql = "
                            UPDATE mistake_servicetime 
                            SET open_time = '$open_time', close_time = '$close_time' 
                            WHERE mistake_id = '$mistake_id' AND day = '$day'";
                        if (!mysqli_query($link, $update_sql)) {
                            echo "<script>alert('更新營業時間失敗： " . mysqli_error($link) . "');</script>";
                        }
                    }
                } else {
                    // 營業時間不存在，插入新的紀錄
                    $insert_sql = "
                        INSERT INTO mistake_servicetime (mistake_id, day, open_time, close_time) 
                        VALUES ('$mistake_id', '$day', '$open_time', '$close_time')";
                    if (!mysqli_query($link, $insert_sql)) {
                        echo "<script>alert('插入營業時間失敗： " . mysqli_error($link) . "');</script>";
                    }
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
                        value="<?php echo htmlspecialchars($start_date); ?>">
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
                                            <input type="text" class="form-control" id="address" name="address" required>
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

                    <!-- 引入 Bootstrap 的 JS -->
                    <script
                        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

                </div>
            </div>
        </div>
    </div>

</body>

</html>
