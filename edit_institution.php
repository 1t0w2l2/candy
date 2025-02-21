<?php
include "db.php";

if (empty($_SESSION['account'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['institution_id']) || empty($_GET['institution_id'])) {
    header("Location: landmark.php");
    exit;
}

$institution_id = mysqli_real_escape_string($link, $_GET['institution_id']);

// 查詢機構基本資料
$sql_institution = "SELECT * FROM `institution` 
                    WHERE `institution_id` = '$institution_id'";
$result_institution = mysqli_query($link, $sql_institution);
$institution = mysqli_fetch_assoc($result_institution);

// 查詢醫療機構資料
$sql_hospital = "SELECT `hospital_id`, `account`, `institution_id`, `institution_name`, 
                `institution_address`, `institution_phone`, `institution_img`, `status` 
                FROM `hospital` 
                WHERE `institution_id` = '$institution_id'";
$result_hospital = mysqli_query($link, $sql_hospital);

// 查詢服務時間資料
$sql_service_time = "SELECT `service_hour_id`, `institution_id`, `day`, `open_time`, `close_time` 
                    FROM `servicetime` 
                    WHERE `institution_id` = '$institution_id'";
$result_service_time = mysqli_query($link, $sql_service_time);


$business_hours = []; // 用來存儲營業時間數據

if (!empty($institution_id)) {
    // 準備 SQL 語句
    $stmt = $link->prepare("SELECT * FROM `servicetime` WHERE `institution_id` = ?");
    $stmt->bind_param("s", $institution_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $day = $row["day"]; // 獲取星期
            $open_time = $row["open_time"]; // 營業開始時間
            $close_time = $row["close_time"]; // 營業結束時間

            // 把每一天的營業時間存入對應的索引，變成多筆時間的陣列
            $business_hours[$day][] = [
                'open_time' => $open_time,
                'close_time' => $close_time
            ];
        }
    }
    $stmt->close();
    // 測試輸出：檢查 $business_hours 陣列是否正確
    echo "<script type='text/javascript'>console.log(" . json_encode($business_hours) . ");</script>";
} else {
    // 如果沒有找到任何營業時間
    echo "<script type='text/javascript'>console.log('No service time found');</script>";
}


// 檢查資料是否存在
if (!$institution) {
    echo "找不到指定的機構資料";
    exit();
}

?>

<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <?php include 'head.php'; ?>
    <style>
        body {
            margin-top: 7%;
        }

        .form-container {
            margin: 20px auto;
            padding: 20px;
            width: 70%;
            background-color: #f7f2f0;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            background-color: #fff;
        }

        .form-group textarea {
            resize: vertical;
        }

        .form-group button {
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .form-group button:hover {
            background-color: #45a049;
        }

        .hospital-table,
        .service-time-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0px;
        }

        .hospital-table th,
        .hospital-table td,
        .service-time-table th,
        .service-time-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        .hospital-table th,
        .service-time-table th {
            background-color: #f4f4f4;
        }


        .day {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }


        .left-side {
            display: flex;
            align-items: center;
        }

        .bi-toggle-off,
        .bi-toggle-on {
            font-size: 24px;
            color: #ff6347;
            cursor: pointer;
            margin-left: 10px;
        }

        .bi-toggle-on {
            color: #32cd32;
        }


        .bi-chevron-up,
        .bi-chevron-down {
            margin-right: 10px;
        }


        .time-settings {
            margin-top: 10px;
            margin-bottom: 20px;
        }


        .bi-trash {
            margin-left: 10px;
            cursor: pointer;
        }


        /* 讓 time-slot 的內容顯示在同一行 */
        .time-slot {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }

        /* 調整 time-input 的寬度 */
        .time-slot .time-input {
            width: 120px;
            padding: 5px;
        }

        /* 給 bi-trash 增加樣式 */
        .time-slot .bi-trash {
            color: red;
            cursor: pointer;
            transition: color 0.3s;
        }

        .time-slot .bi-trash:hover {
            color: darkred;
        }

        /* 給其他圖標設置顏色 */
        .day i,
        .day-settings .bi-info-circle,
        .day-settings .bi-chevron-up,
        .day-settings .bi-chevron-down {
            color: #007bff;
            /* 圖標默認藍色 */
            margin-right: 5px;
            /* 增加右邊的間距 */
        }

        /* 鼠標懸停時改變圖標顏色 */
        .day i:hover,
        .day-settings .bi-info-circle:hover {
            color: #0056b3;
            /* 懸停時變深藍 */
        }

        /* 調整新增時段按鈕的樣式 */
        .time-settings .bi-plus {
            color: green;
            /* 新增圖標為綠色 */
            margin-right: 5px;
            /* 新增圖標與文字間距 */
        }

        .time-settings .bi-plus:hover {
            color: darkgreen;
            /* 懸停時變深綠 */
        }

        .action-buttons {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .btn-back {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            cursor: pointer;
        }

        .btn-back:hover {
            background-color: #0056b3;
        }

        .btn-update {
            display: block;
            width: 100%;
            background-color: #4CAF50;
            color: white;
            padding: 15px;
            text-align: center;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
        }

        .btn-update:hover {
            background-color: #45a049;
        }

        .action-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            /* 或者用 flex-start / center 視需求調整 */
            margin-bottom: 20px;
        }

        .btn-back {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }

        .btn-back:hover {
            background-color: #0056b3;
            color: white;
        }

        .action-header h2 {
            margin: 0;
            font-size: 24px;
            flex-grow: 1;
            text-align: center;
        }

        input[readonly] {
            background-color: #f5f5f5;
            border: 1px solid #ccc;
        }
    </style>


</head>

<body>
    <?php include "nav.php"; ?>

    <div class="form-container">
        <div class="action-header">
            <a class="btn-back" href="#" onclick="if (confirmBack()) { history.back(); } return false;">返回</a>

            <h2>編輯機構資料</h2>
        </div>


        <form method="POST" action="update_institution.php">
        <div class="form-group">
            <label for="institution_id">醫療機構代碼</label>
            <input type="text" name="institution_id" id="institution_id"
                value="<?php echo htmlspecialchars($institution['institution_id']); ?>" readonly>
        </div>
        <div class="form-group">
            <label for="institution_name">機構名稱</label>
            <input type="text" name="institution_name" id="institution_name"
                value="<?php echo htmlspecialchars($institution['institution_name']); ?>" required>
        </div>

        <div class="form-group">
            <label for="county">縣市</label>
            <input type="text" name="county" id="county" value="<?php echo htmlspecialchars($institution['county']); ?>"
                required>
        </div>

        <div class="form-group">
            <label for="town">區域</label>
            <input type="text" name="town" id="town" value="<?php echo htmlspecialchars($institution['town']); ?>"
                required>
        </div>

        <div class="form-group">
            <label for="address">地址</label>
            <input type="text" name="address" id="address"
                value="<?php echo htmlspecialchars($institution['address']); ?>" required>
        </div>

        <div class="form-group">
            <label for="phone">電話</label>
            <input type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($institution['phone']); ?>"
                required>
        </div>

        <div class="form-group">
            <label for="person_charge">負責人</label>
            <input type="text" name="person_charge" id="person_charge"
                value="<?php echo htmlspecialchars($institution['person_charge']); ?>">
        </div>

        <div class="form-group">
            <label for="website">網站</label>
            <input type="url" name="website" id="website"
                value="<?php echo htmlspecialchars($institution['website']); ?>">
        </div>

        <div class="form-group">
            <label for="lat">緯度</label>
            <input type="text" name="lat" id="lat" value="<?php echo htmlspecialchars($institution['lat']); ?>"
                required>
        </div>

        <div class="form-group">
            <label for="lng">經度</label>
            <input type="text" name="lng" id="lng" value="<?php echo htmlspecialchars($institution['lng']); ?>"
                required>
        </div>

        <?php
        // 查詢醫療機構資料
        $sql_hospital = "SELECT `hospital_id`, `account`, `institution_id`, `institution_name`, 
                `institution_address`, `institution_phone`, `institution_img`, `status` 
                FROM `hospital` 
                WHERE `institution_id` = '$institution_id'";
        $result_hospital = mysqli_query($link, $sql_hospital);

        // 檢查是否有查詢結果
        if (mysqli_num_rows($result_hospital) > 0): ?>
            <h3>醫療機構使用者帳號資料</h3>
            <table class="hospital-table">
                <thead>
                    <tr>
                        <th>帳號</th>
                        <th>機構名稱</th>
                        <th>機構地址</th>
                        <th>機構電話</th>
                        <th>醫療機構審核狀態</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($hospital = mysqli_fetch_assoc($result_hospital)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($hospital['account']); ?></td>
                            <td><?php echo htmlspecialchars($hospital['institution_name']); ?></td>
                            <td><?php echo htmlspecialchars($hospital['institution_address']); ?></td>
                            <td><?php echo htmlspecialchars($hospital['institution_phone']); ?></td>
                            <td>
                                <?php
                                echo ($hospital['status'] == 1)
                                    ? '審核通過'
                                    : (($hospital['status'] == 2)
                                        ? '待審核'
                                        : '未知狀態');
                                ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div style="margin: 30px 0px;">
                <h3>醫療機構使用者帳號資料</h3>
                <p>目前該機構尚未註冊帳號。</p>
            </div>

        <?php endif; ?>


        <h3>服務時間資料</h3>
        <div class="form-group mb-4">
            <div style="display: flex; align-items: center;">
                <label for="institution_phone" style="margin-right: 8px;">
                    <span style="color: red;">*</span> 營業時間
                </label>
                <i class="bi bi-info-circle" tabindex="0" role="button" data-bs-toggle="popover" data-bs-trigger="focus"
                    title="24小時營業-設定說明" data-bs-html="true"
                    data-bs-content='請設定該天營業時間為<b>上午12:00 - 下午11:59</b>，以表示為24小時營業。' style="cursor: pointer;"></i>
            </div>

            <div class="day-settings" id="day-settings">
                <?php
                // 定義一個包含所有天的陣列
                $days = [
                    '星期一' => '星期一',
                    '星期二' => '星期二',
                    '星期三' => '星期三',
                    '星期四' => '星期四',
                    '星期五' => '星期五',
                    '星期六' => '星期六',
                    '星期日' => '星期日'
                ];


                // 遍歷每一天，動態生成 HTML
                foreach ($days as $key => $label) {
                    $day_business_hours = isset($business_hours[$key]) ? $business_hours[$key] : [];
                    // 檢查是否有營業時間
                    $is_open = false;
                    foreach ($day_business_hours as $time) {
                        if ($time['open_time'] !== '00:00:00' || $time['close_time'] !== '00:00:00') {
                            $is_open = true;
                            break;
                        }
                    }
                    ?>
                    <div class="day">
                        <div class="left-side">
                            <i class="bi <?php echo $is_open ? 'bi-chevron-up' : 'bi-chevron-down'; ?>"
                                id="chevron-<?php echo $key; ?>"></i>
                            <label><?php echo $label; ?></label>
                        </div>
                        <i class="bi bi-toggle-<?php echo $is_open ? 'on' : 'off'; ?>"
                            id="toggle-<?php echo htmlspecialchars($label); ?>"
                            onclick="toggleBusiness('<?php echo htmlspecialchars($key); ?>')"></i>
                    </div>

                    <div class="time-settings" id="time-settings-<?php echo $key; ?>"
                        style="<?php echo $is_open ? '' : 'display: none;'; ?>">
                        <div id="time-slots-<?php echo $key; ?>">
                            <?php foreach ($day_business_hours as $index => $time) {
                                // 處理 close_time 顯示為 23:59 的情況
                                $open_time = $time['open_time'];
                                $close_time = ($time['close_time'] === '24:00:00') ? '23:59' : $time['close_time'];
                                ?>
                                <?php if ($open_time !== '00:00:00' || $close_time !== '00:00:00') { ?>
                                    <div class="time-slot" id="time-slot-<?php echo $key; ?>-<?php echo $index; ?>">
                                        <input type="time"
                                            name="business_hours[<?php echo $key; ?>][<?php echo $index; ?>][open_time]"
                                            value="<?php echo $open_time; ?>" class="time-input">
                                        -
                                        <input type="time"
                                            name="business_hours[<?php echo $key; ?>][<?php echo $index; ?>][close_time]"
                                            value="<?php echo $close_time; ?>" class="time-input">
                                        <i class="bi bi-trash"
                                            onclick="removeTimeSlot('<?php echo $key; ?>', '<?php echo $index; ?>')"
                                            style="cursor: pointer;"></i>
                                    </div>
                                <?php } ?>
                            <?php } ?>
                        </div>
                        <span onclick="addTimeSlot('<?php echo $key; ?>')" style="cursor: pointer;">
                            <i class="bi bi-plus"></i> 新增時段
                        </span>
                    </div>
                    <hr>
                <?php } ?>
            </div>
        </div>

        <button type="submit" class="btn-update">更新資料</button>
        <!-- <button type="button" id="updatebt" class="btn-update">更新資料</button> -->

        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {

            const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            popoverTriggerList.forEach(function (popoverTriggerEl) {
                new bootstrap.Popover(popoverTriggerEl);
            });

            document.querySelector('form').addEventListener('submit', function (event) {
                // 檢查是否有營業時間，且營業開始時間小於結束時間
                const openTimes = document.querySelectorAll('input[name*="[open_time]"]');
                const closeTimes = document.querySelectorAll('input[name*="[close_time]"]');

                for (let i = 0; i < openTimes.length; i++) {
                    const openTime = openTimes[i].value;
                    const closeTime = closeTimes[i].value;

                    if (!openTime || !closeTime) {
                        alert('請填寫所有的營業時間');
                        event.preventDefault(); // 阻止表單送出
                        return;
                    }
                    console.log('開始', openTime)
                    console.log('結束', closeTime)

                    if (openTime > closeTime) {
                        alert('營業開始時間必須小於結束時間');
                        event.preventDefault(); // 阻止表單送出
                        return;
                    }
                }
            });



            // const updateButton = document.getElementById('updatebt');

            // updateButton.addEventListener('click', function () {
            //     // 獲取所有包含 `time-settings-{key}` 的容器
            //     const timeSettingsContainers = document.querySelectorAll('[id^="time-settings-"]');

            //     timeSettingsContainers.forEach(container => {
            //         const dayKey = container.id.replace('time-settings-', '');

            //         // 獲取這一天內的所有時段
            //         const timeSlots = container.querySelectorAll('.time-slot');

            //         timeSlots.forEach((slot, index) => {
            //             // 獲取每一時段的開放和關閉時間
            //             const openInput = slot.querySelector('input[name*="open_time"]');
            //             const closeInput = slot.querySelector('input[name*="close_time"]');

            //             const openTime = openInput.value;
            //             const closeTime = closeInput.value;

            //             // 輸出到控制台
            //             console.log(`【${dayKey}】的第 ${index + 1} 個時段: 開始時間=${openTime}, 結束時間=${closeTime}`);

            //             // 額外檢查，若未填寫或時間不正確則提示
            //             if (!openTime || !closeTime) {
            //                 console.log(`【${dayKey}】的第 ${index + 1} 個時段未填寫完整！`);
            //             } else if (openTime >= closeTime) {
            //                 console.log(`【${dayKey}】的第 ${index + 1} 個時段時間不正確: 開始時間不能晚於或等於結束時間。`);
            //             }
            //         });
            //     });
            // });

        });
        function toggleBusiness(day) {
            var toggle = document.getElementById("toggle-" + day);
            var timeSettings = document.getElementById("time-settings-" + day);

            var chevron = document.getElementById("chevron-" + day);  // 獲取對應的 chevron 圖標
            if (toggle.classList.contains("bi-toggle-off")) {
                // 切換到開啟狀態
                toggle.classList.remove("bi-toggle-off");
                toggle.classList.add("bi-toggle-on");
                timeSettings.style.display = "block";
                // 切換 chevron 向上的箭頭
                chevron.classList.remove("bi-chevron-down");
                chevron.classList.add("bi-chevron-up");
            } else {
                // 切換到關閉狀態
                toggle.classList.remove("bi-toggle-on");
                toggle.classList.add("bi-toggle-off");
                timeSettings.style.display = "none";
                // 切換 chevron 向下的箭頭
                chevron.classList.remove("bi-chevron-up");
                chevron.classList.add("bi-chevron-down");

                // 找到所有該日的時間段，並移除它們
                const timeSlots = document.getElementById("time-slots-" + day);

                // 隱藏所有時間段元素，而不是刪除它們
                const children = timeSlots.children;
                // for (let i = 0; i < children.length; i++) {
                //     children[i].style.display = 'none';  // 設置每個子元素為隱藏
                // }
                while (children.length > 0) {
                    timeSlots.removeChild(children[0]);
                }

            }
        }
        function addTimeSlot(day) {
            var timeSlots = document.getElementById("time-slots-" + day);
            var timeSlot = document.createElement("div");
            timeSlot.className = "time-slot";

            // 使用 Date().getTime() 作為唯一索引，避免重複
            var index = new Date().getTime();

            // 增加新的時間段輸入框，並增加垃圾桶圖標
            timeSlot.innerHTML = `
        <div id="time-slot-${day}-${index}">
            <input type="time" name="business_hours[${day}][${index}][open_time]" class="time-input open-time"> - 
            <input type="time" name="business_hours[${day}][${index}][close_time]" class="time-input close-time">
            <i class="bi bi-trash" onclick="removeTimeSlot('${day}', '${index}')" style="cursor: pointer;"></i>
        </div>
    `;

            timeSlots.appendChild(timeSlot);
        }

        function removeTimeSlot(day, index) {
            // 找到對應的時間段 div 並移除
            var timeSlot = document.getElementById('time-slot-' + day + '-' + index);
            if (timeSlot) {
                timeSlot.remove();
            }
        }
        function confirmBack() {
            return confirm("確定要返回嗎？未儲存的資料將會遺失。");
        }





    </script>


</body>

</html>