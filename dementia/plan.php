<?php
session_start();
include "db.php";

// 檢查用戶是否登入
$account = isset($_SESSION['account']) ? $_SESSION['account'] : '';
if (empty($account)) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'add') {
    $date = $_POST['date'];
    $plan_name = $_POST['plan_name'];
    $plan_type = $_POST['plan_type'];
    $plan_time = $_POST['plan_time'];
    $end_time = $_POST['end_time'];
    $remark = $_POST['remark'];

    $sql_plan = "INSERT INTO plan (account, edit_account, plan_type, plan_name, date, plan_time, end_time, remark) 
                 VALUES ('$account', '$account', '$plan_type', '$plan_name', '$date', '$plan_time', '$end_time', '$remark')";

    if (mysqli_query($link, $sql_plan)) {
        echo "<script>alert('新增成功'); window.location.href = 'plan.php';</script>";
    } else {
        echo "<script>alert('新增失敗: " . mysqli_error($link) . "');</script>";
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'edit') {
    if (isset($_POST['plan_id'], $_POST['plan_name'], $_POST['plan_name'], $_POST['plan_time'], $_POST['end_time'], $_POST['remark'])) {
        $plan_id = $_POST['plan_id'];
        $plan_name = $_POST['plan_name'];
        $plan_time = $_POST['plan_time'];
        $end_time = $_POST['end_time'];
        $remark = $_POST['remark'];

        // 使用 prepared statements 增加安全性
        $stmt = $link->prepare("UPDATE `plan` SET `plan_name` = ?, `plan_time` = ?, `end_time` = ?, `remark` = ? WHERE `plan_id` = ?");
        $stmt->bind_param("ssssi", $plan_name, $plan_time, $end_time, $remark, $plan_id);

        if ($stmt->execute()) {
            echo "success";  // 執行成功後回傳 success 給前端
        } else {
            echo "error: " . $stmt->error;  // 若執行失敗，回傳錯誤訊息
        }


        $stmt->close();
    } else {
        echo "error: 欄位遺漏";
    }
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (isset($_POST['plan_id'])) {
        $plan_id = $_POST['plan_id'];

        // 使用 prepared statements 增加安全性
        $stmt = $link->prepare("DELETE FROM `plan` WHERE `plan_id` = ?");
        $stmt->bind_param("i", $plan_id);

        if ($stmt->execute()) {
            echo "success";  // 執行成功後回傳 success 給前端
        } else {
            echo "error: " . $stmt->error;  // 若執行失敗，回傳錯誤訊息
        }

        $stmt->close();
    } else {
        echo "error: 欄位遺漏";
    }
    exit;
}


// 設定日期
if (isset($_POST['date'])) {
    $date = $_POST['date'];
} else {
    $date = date('Y-m-d'); // 使用當前日期作為預設
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 獲取所選日期的活動
    $selected_date = isset($_POST['date']) ? $_POST['date'] : date('Y-m-d'); // 如果沒有選擇日期，則使用今天的日期
    $sql_fetch_events = "SELECT * FROM plan WHERE date = '$selected_date' and account='$account' ORDER BY plan_time";
    $events_result = mysqli_query($link, $sql_fetch_events);

    // 將查詢結果存入 $play 陣列
    $play = [];
    while ($row = mysqli_fetch_assoc($events_result)) {
        $play[] = $row;
    }

    // 設定活動顏色對應表
    $plan_colors = [
        '用藥提醒' => '#F1D3CE',    // 藤紅色
        '預約看診' => '#D1E9F6',     // 淺藍色
        '活動提醒' => '#C9E9D2',     // 淺綠色
        '其他' => '#F6EACB'          // 淺黃色
    ];

    if (!empty($play)) {
        foreach ($play as $event) {
            // 根據活動類型獲取顏色，若沒有設定的類型，預設為白色
            $color = isset($plan_colors[$event['plan_type']]) ? $plan_colors[$event['plan_type']] : '#FFFFFF';

            // 行程顯示區域
            echo '<div class="event-frame" style="position: relative; padding: 20px; margin: 5px 0; border-radius: 5px; background-color: ' . $color . ';">';

            // 顯示行程的其他細節
            echo '<strong>' . htmlspecialchars($event['plan_name']) . '</strong><br>';
            echo htmlspecialchars($event['plan_time']) . ' - ' . htmlspecialchars($event['end_time']) . '<br>';
            echo '<span class="event-remark">' . htmlspecialchars($event['remark']) . '</span>';

            // 編輯圖示按鈕，按下時觸發編輯表單
            echo '<i class="fa-solid fa-pen-to-square edit-icon" style="position: absolute; top: 5px; right: 5px; cursor: pointer;" onclick="populateEditForm(' . $event['plan_id'] . ', \'' . htmlspecialchars($event['plan_name']) . '\', \'' . htmlspecialchars($event['plan_time']) . '\', \'' . htmlspecialchars($event['end_time']) . '\', \'' . htmlspecialchars($event['remark']) . '\')"></i>';

            echo '</div>';
        }
    } else {
        echo '<div class="event-frame" style="padding: 10px; margin: 5px 0; border-radius: 5px;"><strong>此日期無行程</strong></div>';
    }


    exit(); // 結束腳本以防止進一步輸出
}

// 如果不是 POST 請求，則顯示今天的行程
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    $today_date = date('Y-m-d');
    $sql_fetch_events = "SELECT * FROM plan WHERE date = '$today_date' AND account='$account' ORDER BY plan_time";
    $events_result = mysqli_query($link, $sql_fetch_events);

    // 將查詢結果存入 $play 陣列
    $play = [];
    while ($row = mysqli_fetch_assoc($events_result)) {
        $play[] = $row;
    }
    // 設定活動顏色對應表
    $plan_colors = [
        '用藥提醒' => '#F1D3CE',    // 藤紅色
        '預約看診' => '#D1E9F6',     // 淺藍色
        '活動提醒' => '#C9E9D2',     // 淺綠色
        '其他' => '#F6EACB'          // 淺黃色
    ];

}


?>

<!doctype html>
<html lang="en">

<head>
    <?php include 'head.php'; ?>
</head>

<body>
    <?php include "nav.php"; ?>
    <div class="a1-container">
        <div class="left-panel">
            <h5 id="selectedDate"><?php echo $date; ?> 行程</h5>
            <div class="add-event-container">
                <button id="addEvent">+</button>
                <ul class="add-options">
                    <li>
                        <button id="addSchedule" data-bs-toggle="modal" data-bs-target="#planModal">新增行程</button>
                    </li>
                    <li>
                        <a href="diary.php" class="add-diary-btn">新增日記</a>
                    </li>
                </ul>
            </div>


            <!-- 新增行程的模態框 -->
            <div class="modal fade s3-modal" id="planModal" tabindex="-1" aria-labelledby="planModalLabel"
                aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="" method="post">
                            <div class="modal-header">
                                <h5 class="modal-title" id="planModalLabel">新增行程</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-1">
                                    <label for="recipient-name" class="col-form-label">行程名稱:</label>
                                    <input type="text" class="form-control" name="plan_name" required>
                                </div>
                                <div class="mb-1">
                                    <label for="recipient-name" class="col-form-label">行程類型:</label>
                                    <select class="form-control" name="plan_type" required>
                                        <option>請選擇類型</option>
                                        <option value="用藥提醒">用藥提醒</option>
                                        <option value="預約看診">預約看診</option>
                                        <option value="活動提醒">活動提醒</option>
                                        <option value="其他">其他</option>
                                    </select>
                                </div>
                                <div class="mb-1">
                                    <label for="date" class="col-form-label">日期</label>
                                    <input type="date" name="date" class="form-control" id="eventDate"
                                        value="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="mb-1">
                                    <label for="myTime" class="col-form-label">行程開始時間</label>
                                    <input type="time" name="plan_time" class="form-control" required>
                                </div>
                                <div class="mb-1">
                                    <label for="myTime" class="col-form-label">行程結束時間</label>
                                    <input type="time" name="end_time" class="form-control" required>
                                </div>
                                <div class="mb-1">
                                    <label for="recipient-name" class="col-form-label">備註</label>
                                    <input type="text" class="form-control" name="remark">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary" value="add" name="action">送出</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">關閉</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- 編輯行程的模態框 -->
            <div class="modal fade s3-modal" id="editEventModal" tabindex="-1" role="dialog"
                aria-labelledby="editEventModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editEventModalLabel">編輯行程</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form id="editEventForm" method="POST" action="process_event.php">
                                <!-- 隱藏欄位用來儲存日期 -->
                                <input type="hidden" id="eventDateHidden" name="date">

                                <!-- 行程 ID 隱藏欄位 -->
                                <input type="hidden" id="plan_id" name="plan_id">

                                <div class="mb-3">
                                    <label for="eventName" class="form-label">行程名稱</label>
                                    <input type="text" class="form-control" id="eventName" name="plan_name">
                                </div>
                                <div class="mb-3">
                                    <label for="eventTime" class="form-label">行程開始時間</label>
                                    <input type="time" class="form-control" id="eventTime" name="plan_time">
                                </div>
                                <div class="mb-3">
                                    <label for="endTime" class="form-label">行程結束時間</label>
                                    <input type="time" class="form-control" id="endTime" name="end_time">
                                </div>
                                <div class="mb-3">
                                    <label for="eventRemark" class="form-label">備註</label>
                                    <textarea class="form-control" id="eventRemark" name="remark"></textarea>
                                </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" value="edit" id="saveChangesBtn">儲存變更</button>
                            <button type="button" class="btn btn-danger" value="delete" id="deleteBtn">刪除</button>
                        </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="activities">
                <div id="today-schedule">
                    <div class="activities-list">
                        <?php if (!empty($play)): ?>
                            <?php foreach ($play as $event):
                                $color = isset($plan_colors[$event['plan_type']]) ? $plan_colors[$event['plan_type']] : '#FFFFFF';
                                ?>
                                <div class="event-frame" style="background-color: <?php echo $color; ?>; position: relative;">
                                    <strong><?php echo htmlspecialchars($event['plan_name']); ?></strong><br>
                                    <?php echo htmlspecialchars($event['plan_time']) . ' - ' . htmlspecialchars($event['end_time']); ?><br>
                                    <span class="event-remark"><?php echo htmlspecialchars($event['remark']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="event-frame">
                                <strong>此日期無行程</strong>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="right-panel">
            <div class="calendar-header">
                <div class="nav-arrows">
                    <span class="arrow" id="prevMonth">‹</span>
                    <h2 id="monthYear">October 2024</h2>
                    <span class="arrow" id="nextMonth">›</span>

                    <button class="return-today" id="returnToday">返回今天</button>
                </div>
            </div>


            <div class="calendar-grid">
                <div class="weekdays" style="background: none;">日</div>
                <div class="weekdays" style="background: none;">一</div>
                <div class="weekdays" style="background: none;">二</div>
                <div class="weekdays" style="background: none;">三</div>
                <div class="weekdays" style="background: none;">四</div>
                <div class="weekdays" style="background: none;">五</div>
                <div class="weekdays" style="background: none;">六</div>

                <!-- 這裡的日曆格子是動態生成的 -->
                <?php
                // 在這裡生成日曆格子，省略代碼
                ?>
            </div>
        </div>
    </div>

    <script>
        const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
        let today = new Date();
        let currentYear = today.getFullYear();
        let currentMonth = today.getMonth();
        let selectedDate = today.getDate();

        function updateMonthYear() {
            document.getElementById('monthYear').textContent = `${monthNames[currentMonth]} ${currentYear}`;
        }

        function daysInMonth(year, month) {
            return new Date(year, month + 1, 0).getDate();
        }

        function generateCalendar(year, month, selectedDay) {
            const calendarGrid = document.querySelector('.calendar-grid'); // 假設日曆容器
            const weekdaysRow = document.querySelector('.weekdays'); // 星期標題行

            // // 確保只清空日期部分，不影響星期標題
            if (!weekdaysRow) {
                // 如果星期標題行不存在，生成一次
                const weekdays = ['日', '一', '二', '三', '四', '五', '六'];
                const newWeekdaysRow = document.createElement('div');
                newWeekdaysRow.classList.add('weekdays');
                weekdays.forEach(day => {
                    const weekdayCell = document.createElement('div');
                    weekdayCell.classList.add('weekday');
                    weekdayCell.textContent = day;
                    newWeekdaysRow.appendChild(weekdayCell);
                });
                calendarGrid.appendChild(newWeekdaysRow);
            }

            // 清除日期部分，保留星期標題
            const daysContainer = document.querySelector('.calendar-grid');
            if (daysContainer) {
                daysContainer.innerHTML = ''; // 清空日期部分
            } else {
                // 如果日期部分不存在，創建
                const newDaysContainer = document.createElement('div');
                newDaysContainer.classList.add('calendar-grid');
                calendarGrid.appendChild(newDaysContainer);
                weekdayCell.classList.add('weekday');
                    weekdayCell.textContent = day;
                    newWeekdaysRow.appendChild(weekdayCell);
            }

            const daysInMonth = new Date(year, month + 1, 0).getDate(); // 獲取當月天數

            for (let day = 1; day <= daysInMonth; day++) {
                const dayCell = document.createElement('div');
                dayCell.classList.add('calendar-day');

                const circleDiv = document.createElement('div');
                circleDiv.classList.add('circle');
                circleDiv.textContent = day;

                const date = `${year}-${(month + 1).toString().padStart(2, '0')}-${day.toString().padStart(2, '0')}`;
                circleDiv.setAttribute('data-date', date);

                // 如果是選中的日期，設置樣式
                if (day === selectedDay) {
                    circleDiv.style.backgroundColor = '#007bff';
                    circleDiv.style.color = 'white';
                }

                // 點擊事件
                circleDiv.addEventListener('click', () => {
                    document.getElementById('selectedDate').textContent = `${date} 行程`;
                    document.getElementById('eventDate').value = date;

                    // 清除其他圓圈的選中樣式
                    document.querySelectorAll('.circle').forEach(circle => {
                        circle.style.backgroundColor = '';
                        circle.style.color = '';
                    });

                    // 設置選中的圓圈樣式
                    circleDiv.style.backgroundColor = '#007bff';
                    circleDiv.style.color = 'white';

                    // 顯示或隱藏返回今天按鈕
                    const todayString = `${new Date().getFullYear()}-${(new Date().getMonth() + 1)
                        .toString()
                        .padStart(2, '0')}-${new Date().getDate().toString().padStart(2, '0')}`;
                    if (date !== todayString) {
                        document.getElementById('returnToday').style.display = 'block';
                    } else {
                        document.getElementById('returnToday').style.display = 'none';
                    }

                    // 儲存選中的日期
                    localStorage.setItem('selectedDate', date);

                    // 更新活動列表（模擬 AJAX 請求）
                    const xhr = new XMLHttpRequest();
                    xhr.open("POST", "", true);
                    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                    xhr.onreadystatechange = function () {
                        if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
                            document.querySelector('.activities-list').innerHTML = this.responseText;
                        }
                    };
                    xhr.send(`date=${date}`);
                });

                dayCell.appendChild(circleDiv);
                document.querySelector('.calendar-grid').appendChild(dayCell);
            }
        }


        document.getElementById('prevMonth').addEventListener('click', () => {
            if (currentMonth === 0) {
                currentMonth = 11;
                currentYear--;
            } else {
                currentMonth--;
            }
            updateMonthYear();
            generateCalendar(currentYear, currentMonth);
        });

        document.getElementById('nextMonth').addEventListener('click', () => {
            if (currentMonth === 11) {
                currentMonth = 0;
                currentYear++;
            } else {
                currentMonth++;
            }
            updateMonthYear();
            generateCalendar(currentYear, currentMonth);
        });
        // 返回今天按鈕的點擊事件
        document.getElementById('returnToday').addEventListener('click', () => {
            currentYear = today.getFullYear();
            currentMonth = today.getMonth();
            selectedDate = today.getDate();

            // 更新選中的日期顯示
            document.getElementById('selectedDate').textContent = `${today.toISOString().split('T')[0]} 行程`;

            // 清除 localStorage 中的 selectedDate
            localStorage.removeItem('selectedDate');

            // 更新月份顯示和生成日曆
            updateMonthYear();
            generateCalendar(currentYear, currentMonth, { year: currentYear, month: currentMonth, day: selectedDate });

            // 隱藏返回今天按鈕
            document.getElementById('returnToday').style.display = 'none';

            // 發送 AJAX 請求到 PHP 以獲取今天的行程
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'plan.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function () {
                if (xhr.status === 200) {
                    // 更新行程列表
                    document.querySelector('.activities-list').innerHTML = xhr.responseText;
                }
            };
            xhr.send(`date=${encodeURIComponent(today.toISOString().split('T')[0])}`);
        });
        // 頁面加載時初始化
        window.addEventListener('load', () => {
            const today = new Date();
            const todayString = today.toISOString().split('T')[0]; // 格式：YYYY-MM-DD
            const returnTodayButton = document.getElementById('returnToday');

            // 從 localStorage 中讀取選定的日期
            const savedDate = localStorage.getItem('selectedDate');
            let selectedDateObj;

            // 判斷是否有存儲日期，沒有則使用今天日期
            if (savedDate) {
                selectedDateObj = new Date(savedDate);
            } else {
                selectedDateObj = today;
                localStorage.setItem('selectedDate', todayString); // 將今天日期存到 localStorage
            }

            const selectedYear = selectedDateObj.getFullYear();
            const selectedMonth = selectedDateObj.getMonth();
            const selectedDay = selectedDateObj.getDate();

            // 顯示選定的日期
            document.getElementById('selectedDate').textContent = `${selectedDateObj.toISOString().split('T')[0]} 行程`;
            document.getElementById('eventDate').value = selectedDateObj.toISOString().split('T')[0];

            // 控制返回今天按鈕的顯示
            if (savedDate === todayString) {
                returnTodayButton.style.display = 'none'; // 隱藏按鈕
            } else {
                returnTodayButton.style.display = 'block'; // 顯示按鈕
            }

            // 生成日曆，並高亮選定的日期
            generateCalendar(selectedYear, selectedMonth, selectedDay);

            // 返回今天按鈕事件
            returnTodayButton.addEventListener('click', () => {
                localStorage.setItem('selectedDate', todayString); // 更新選定日期為今天
                location.reload(); // 刷新頁面
            });
        });


        // 點擊 "返回今天" 按鈕的事件
        document.getElementById('returnToday').addEventListener('click', function () {
            const today = new Date();
            const todayString = today.toISOString().split('T')[0]; // 格式：YYYY-MM-DD
            const returnTodayButton = document.getElementById('returnToday');

            // 更新顯示為今天的日期
            document.getElementById('selectedDate').textContent = `${todayString} 行程`;

            // 清除 localStorage 中的選擇日期
            localStorage.removeItem('selectedDate');

            // 隱藏返回今天按鈕
            returnTodayButton.style.display = 'none';

            // 重新生成日曆
            generateCalendar(today.getFullYear(), today.getMonth(), {
                year: today.getFullYear(),
                month: today.getMonth(),
                day: today.getDate()
            });

            // 更新月份和年份顯示
            updateMonthYear();
        });
        document.addEventListener("DOMContentLoaded", function () {
            const addEventButton = document.getElementById("addEvent");
            const addOptions = document.querySelector(".add-options");

            addEventButton.addEventListener("click", function (event) {
                event.stopPropagation();
                const isHidden = addOptions.style.display === "none" || addOptions.style.display === "";
                addOptions.style.display = isHidden ? "block" : "none";
            });

            document.addEventListener("click", function () {
                addOptions.style.display = "none";
            });
        });

        document.getElementById('addSchedule').addEventListener('click', function () {
            const eventForm = document.getElementById('eventForm');
            eventForm.style.display = eventForm.style.display === 'none' ? 'block' : 'none';
        });

        updateMonthYear();
        generateCalendar(currentYear, currentMonth);

        function populateEditForm(plan_id, eventName, eventTime, endTime, eventRemark) {
            const formElements = {
                plan_id: document.getElementById('plan_id'),
                eventName: document.getElementById('eventName'),
                eventTime: document.getElementById('eventTime'),
                endTime: document.getElementById('endTime'),
                eventRemark: document.getElementById('eventRemark')
            };

            formElements.plan_id.value = plan_id;
            formElements.eventName.value = eventName;
            formElements.eventTime.value = eventTime;
            formElements.endTime.value = endTime;
            formElements.eventRemark.value = eventRemark;

            // 顯示編輯模態框
            const editModal = new bootstrap.Modal(document.getElementById('editEventModal'));
            editModal.show();
        }

        document.getElementById('saveChangesBtn').addEventListener('click', function () {
            const selectedDate = document.getElementById('selectedDate').textContent.split(' ')[0]; // 假設 selectedDate 顯示的是 YYYY-MM-DD 格式
            localStorage.setItem('selectedDate', selectedDate); // 儲存選擇的日期

            // 送出表單資料
            var form = document.getElementById('editEventForm');
            var formData = new FormData(form);
            formData.append('action', 'edit');

            var xhr = new XMLHttpRequest();
            xhr.open('POST', '', true);
            xhr.onload = function () {
                if (xhr.status === 200) {
                    if (xhr.responseText.trim() === 'success') {
                        alert('儲存成功');
                       
                    } else {
                        alert('錯誤: ' + xhr.responseText);
                    }
                } else {
                    alert('錯誤: ' + xhr.statusText);
                }
            };
            xhr.send(formData);
        });



        document.getElementById('deleteBtn').addEventListener('click', function () {
            // 顯示確認對話框
            var confirmation = confirm("是否確定刪除此行程？");

            if (confirmation) {
                var form = document.getElementById('editEventForm');
                var formData = new FormData(form); // 收集表單數據
                formData.append('action', 'delete'); // 添加刪除操作標識

                // 使用 getElementsByName 獲取隱藏欄位的值
                var eventDateHiddenCollection = document.getElementsByName('date'); // 返回 HTMLCollection
                if (eventDateHiddenCollection.length > 0) {
                    var eventDate = eventDateHiddenCollection[0].value; // 取得第一個元素的值
                    console.log('刪除的行程日期:', eventDate);
                } else {
                    console.error('找不到任何 date 欄位');
                    return; // 終止操作，避免發送錯誤請求
                }

                // 儲存刪除的日期到 Local Storage
                if (eventDate) {
                    localStorage.setItem('lastDeletedEventDate', eventDate);
                }

                // 發送 AJAX 請求
                var xhr = new XMLHttpRequest();
                xhr.open('POST', '', true); // 假設後端處理刪除的 URL 是空（根據實際情況修改）

                xhr.onload = function () {
                    if (xhr.status === 200) {
                        if (xhr.responseText.trim() === 'success') {
                            alert('刪除成功');
                            location.reload(); // 刷新頁面並停留在刪除的日期
                        } else {
                            alert('錯誤: ' + xhr.responseText);
                        }
                    } else {
                        alert('錯誤: ' + xhr.statusText);
                    }
                };

                xhr.send(formData); // 發送刪除請求
            }
        });


    </script>
</body>

</html>