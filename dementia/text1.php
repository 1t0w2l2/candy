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
    $event_name = $_POST['event_name'];
    $event_type = $_POST['event_type'];
    $event_time = $_POST['event_time'];
    $end_time = $_POST['end_time'];
    $remark = $_POST['remark'];

    $sql_plan = "INSERT INTO plan (account, edit_account, event_type, event_name, date, event_time, end_time, remark) 
                 VALUES ('$account', '$account', '$event_type', '$event_name', '$date', '$event_time', '$end_time', '$remark')";

    if (mysqli_query($link, $sql_plan)) {
        echo "<script>alert('新增成功'); window.location.href = 'plan.php';</script>";
    } else {
        echo "<script>alert('新增失敗: " . mysqli_error($link) . "');</script>";
    }
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
    $sql_fetch_events = "SELECT * FROM plan WHERE date = '$selected_date' and account='$account' ORDER BY event_time";
    $events_result = mysqli_query($link, $sql_fetch_events);

    // 將查詢結果存入 $play 陣列
    $play = [];
    while ($row = mysqli_fetch_assoc($events_result)) {
        $play[] = $row;
    }

    // 設定活動顏色對應表
    $event_colors = [
        '用藥提醒' => '#F1D3CE',    // 藤紅色
        '預約看診' => '#D1E9F6',     // 淺藍色
        '活動提醒' => '#C9E9D2',     // 淺綠色
        '其他' => '#F6EACB'          // 淺黃色
    ];

    if (!empty($play)) {
        foreach ($play as $event) {
            // 根據活動類型獲取顏色，若沒有設定的類型，預設為白色
            $color = isset($event_colors[$event['event_type']]) ? $event_colors[$event['event_type']] : '#FFFFFF';

            // 行程顯示區域，加入編輯圖標
            echo '<div class="event-frame" style="position: relative; padding: 20px; margin: 5px 0; border-radius: 5px; background-color: ' . $color . ';">';

            echo '<i class="fa-solid fa-pen-to-square edit-icon" style="position: absolute; top: 5px; right: 5px; cursor: pointer;" onclick="editEventModal(' . $event['plan_id'] . ', \\"' . htmlspecialchars($event['event_name']) . '\\", \\"' . htmlspecialchars($event['event_time']) . '\\", \\"' . htmlspecialchars($event['end_time']) . '\\", \\"' . htmlspecialchars($event['remark']) . '\\")"></i>';

            // 顯示行程的其他細節
            echo '<strong>' . htmlspecialchars($event['event_name']) . '</strong><br>';
            echo htmlspecialchars($event['event_time']) . ' - ' . htmlspecialchars($event['end_time']) . '<br>';
            echo '<span class="event-remark">' . htmlspecialchars($event['remark']) . '</span>';
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
    $sql_fetch_events = "SELECT * FROM plan WHERE date = '$today_date' AND account='$account' ORDER BY event_time";
    $events_result = mysqli_query($link, $sql_fetch_events);

    // 將查詢結果存入 $play 陣列
    $play = [];
    while ($row = mysqli_fetch_assoc($events_result)) {
        $play[] = $row;
    }
    // 設定活動顏色對應表
    $event_colors = [
        '用藥提醒' => '#F1D3CE',    // 藤紅色
        '預約看診' => '#D1E9F6',     // 淺藍色
        '活動提醒' => '#C9E9D2',     // 淺綠色
        '其他' => '#F6EACB'          // 淺黃色
    ];

}


// 檢查必要的欄位是否存在
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $plan_id = intval($_POST['plan_id']); // 使用 edit_plan_id
    $event_name = mysqli_real_escape_string($link, $_POST['event_name']);
    $event_time = mysqli_real_escape_string($link, $_POST['event_time']);
    $end_time = mysqli_real_escape_string($link, $_POST['end_time']);
    $remark = mysqli_real_escape_string($link, $_POST['remark']);

    // 更新活動信息
    $sql_update = "UPDATE plan SET event_name = '$event_name', event_time ='$event_time', end_time='$end_time', remark='$remark' WHERE plan_id = '$plan_id'";

    // 執行活動信息的更新
    if (mysqli_query($link, $sql_update)) {
        echo json_encode(['success' => true]); // 返回成功信息
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($link)]); // 返回錯誤信息
    }
    exit();
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
            <button class="return-today" id="returnToday">返回今天</button>

            <!-- 新增行程的模態框 -->
            <div class="modal fade" id="planModal" tabindex="-1" aria-labelledby="planModalLabel" aria-hidden="true">
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
                                    <input type="text" class="form-control" name="event_name" required>
                                </div>
                                <div class="mb-1">
                                    <label for="recipient-name" class="col-form-label">行程類型:</label>
                                    <select class="form-control" name="event_type" required>
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
                                    <input type="time" name="event_time" class="form-control" required>
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
                            <form id="editEventForm" action="" method="POST">
                                <input type="hidden" id="edit_plan_id" name="edit_plan_id">
                                <div class="mb-3">
                                    <label for="edit_event_name" class="form-label">行程名稱</label>
                                    <input type="text" class="form-control" id="edit_event_name" name="edit_event_name">
                                </div>
                                <div class="mb-3">
                                    <label for="edit_event_time" class="form-label">開始時間</label>
                                    <input type="text" class="form-control" id="edit_event_time" name="edit_event_time">
                                </div>
                                <div class="mb-3">
                                    <label for="edit_end_time" class="form-label">結束時間</label>
                                    <input type="text" class="form-control" id="edit_end_time" name="edit_end_time">
                                </div>
                                <div class="mb-3">
                                    <label for="edit_remark" class="form-label">備註</label>
                                    <textarea class="form-control" id="edit_remark" name="edit_remark"></textarea>
                                </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">關閉</button>
                            <button type="submit" class="btn btn-primary">儲存變更</button>
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
                                $color = isset($event_colors[$event['event_type']]) ? $event_colors[$event['event_type']] : '#FFFFFF';
                                ?>
                                <div class="event-frame" style="background-color: <?php echo $color; ?>; position: relative;">
                                    <button class="edit-button"
                                        data-id="<?php echo htmlspecialchars($event['plan_id']); ?>"
                                        data-bs-toggle="modal" data-bs-target="#editEventModal"><i
                                            class="fa-solid fa-pen-to-square edit-icon"></i></button>
                                    <strong><?php echo htmlspecialchars($event['event_name']); ?></strong><br>
                                    <?php echo htmlspecialchars($event['event_time']) . ' - ' . htmlspecialchars($event['end_time']); ?><br>
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

        function generateCalendar(year, month) {
            const calendarGrid = document.querySelector('.calendar-grid');
            document.querySelectorAll('.calendar-grid div:not(.weekdays)').forEach(e => e.remove());

            const firstDay = new Date(year, month, 1).getDay();
            const daysInCurrentMonth = daysInMonth(year, month);

            for (let i = 0; i < firstDay; i++) {
                const emptyCell = document.createElement('div');
                calendarGrid.appendChild(emptyCell);
            }

            for (let day = 1; day <= daysInCurrentMonth; day++) {
                const dayCell = document.createElement('div');
                const circleDiv = document.createElement('div'); // 創建圓形元素
                circleDiv.className = 'circle'; // 設置圓形的類名
                circleDiv.textContent = day; // 將日期添加到圓形中

                // 設置圓形的樣式
                circleDiv.style.width = '40px'; // 根據需要調整圓形大小
                circleDiv.style.height = '40px'; // 根據需要調整圓形大小
                circleDiv.style.borderRadius = '50%'; // 設置為圓形
                circleDiv.style.display = 'flex'; // 使用 Flexbox 進行居中
                circleDiv.style.alignItems = 'center'; // 垂直居中
                circleDiv.style.justifyContent = 'center'; // 水平居中
                circleDiv.style.margin = '10px'; // 設置邊距
                circleDiv.style.cursor = 'pointer'; // 鼠標指針樣式

                // 設置當前日期的顏色
                if (year === today.getFullYear() && month === today.getMonth() && day === today.getDate()) {
                    circleDiv.style.backgroundColor = '#007bff';
                    circleDiv.style.color = 'white';
                }

                // 點擊事件
                circleDiv.addEventListener('click', () => {
                    const date = `${year}-${(month + 1).toString().padStart(2, '0')}-${day.toString().padStart(2, '0')}`;
                    document.getElementById('selectedDate').textContent = `${date} 行程`;
                    document.getElementById('eventDate').value = date;

                    // 清除之前選中的圓形顏色
                    document.querySelectorAll('.circle').forEach(circle => {
                        circle.style.backgroundColor = '';
                        circle.style.color = '';
                    });

                    // 設置選中圓形的顏色
                    circleDiv.style.backgroundColor = '#007bff';
                    circleDiv.style.color = 'white';

                    if (date !== `${today.getFullYear()}-${(today.getMonth() + 1).toString().padStart(2, '0')}-${today.getDate().toString().padStart(2, '0')}`) {
                        document.getElementById('returnToday').style.display = 'block';
                    } else {
                        document.getElementById('returnToday').style.display = 'none';
                    }

                    // AJAX 請求來獲取該日期的活動
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
                // 將圓形元素添加到日格
                dayCell.appendChild(circleDiv);
                calendarGrid.appendChild(dayCell);
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

        document.getElementById('returnToday').addEventListener('click', () => {
            currentYear = today.getFullYear();
            currentMonth = today.getMonth();
            selectedDate = today.getDate();

            // 更新選中的日期顯示
            document.getElementById('selectedDate').textContent = `${today.toISOString().split('T')[0]} 行程`;

            // 更新月份顯示和生成日曆
            updateMonthYear();
            generateCalendar(currentYear, currentMonth);

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

       
        document.querySelectorAll('.edit-button').forEach(button => {
            button.addEventListener('click', function () {
                const plan_id = this.getAttribute('data-id');  // 確保從這裡獲得活動 ID

                // 發起請求以獲取活動詳細信息
                fetch('plan.php', {  // 設定正確的 PHP 文件
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        'action': 'fetch',
                        'plan_id': plan_id  // 使用活動 ID
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // 等待 modal 顯示後再填充表單數據
                            $('#editEventModal').on('shown.bs.modal', function () {
                                document.getElementById('edit_plan_id').value = data.plan.plan_id;
                                document.getElementById('edit_event_name').value = data.plan.event_name;
                                document.getElementById('edit_event_time').value = data.plan.event_time;
                                document.getElementById('edit_end_time').value = data.plan.end_time;
                                document.getElementById('edit_remark').value = data.plan.remark;
                            });

                            // 顯示 modal
                            $('#editEventModal').modal('show');
                        } else {
                            alert(data.error);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('發生錯誤');
                    });
            });
        });


    </script>
</body>

</html>