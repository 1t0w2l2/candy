<?php
session_start();
include "db.php";

// 檢查用戶是否登入
$account = isset($_SESSION['account']) ? $_SESSION['account'] : '';
if (empty($account)) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
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

$play = [];
$today = date('Y-m-d'); // 獲取今天的日期

// 預設查詢今天的活動
$date = isset($_POST['date']) ? $_POST['date'] : $today;

// 查詢該日期的活動
$sql_fetch_events = "SELECT * FROM plan WHERE date = '$date' ORDER BY event_time";
$events_result = mysqli_query($link, $sql_fetch_events);

// 將查詢結果存入 $play 陣列
while ($row = mysqli_fetch_assoc($events_result)) {
    $play[] = $row;
}
?>


<!doctype html>
<html lang="en">

<head>
    <?php include 'head.php'; ?>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f6fa;
            margin: 0;
            padding: 0;
            justify-content: center;
            align-items: flex-start;
            height: 100vh;
        }

        .a1-container {
            display: flex;
            width: 100%;
            max-width: 1200px;
            margin: 20px;
            border-radius: 10px;
            overflow: hidden;
            margin-top: -100px;
        }

        .left-panel {
            width: 25%;
            background-color: #fff;
            padding: 20px;
            border-right: 1px solid #e0e0e0;
            margin-top: 60px;
        }

        .left-panel h2 {
            font-size: 18px;
            color: #333;
            margin-bottom: 20px;
            position: relative;
            top: -15px;
        }

        .right-panel {
            width: 75%;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .calendar-header {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 50px;
            margin-bottom: 30px;
            white-space: nowrap;
        }

        .calendar-header h2 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }

        .nav-arrows {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .arrow {
            font-size: 2rem;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .arrow:hover {
            color: #007bff;
            transform: scale(1.2);
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
            width: 100%;
            margin-top: -30px;
        }

        .calendar-grid div {
            text-align: center;
            padding: 10px;
            color: #333;
            background-color: #f9f9f9;
            font-size: 16px;
            height: 90px;
            width: 90px;
            border-radius: 10px;
            cursor: pointer;
            line-height: 90px;
            position: relative;
        }

        .calendar-grid div .circle {
            position: absolute;
            top: 5%;
            right: 5%;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 16px;
            color: black;
        }

        .calendar-grid div.today {
            background-color: #f9f9f9;
        }

        .calendar-grid div.today .circle {
            border: 2px solid #007bff;
        }

        .weekdays {
            color: #333;
            font-weight: bold;
            margin: 0;
            text-align: center;
        }

        .return-today {
            border: 0;
            background-color: #4A608A;
            color: #fff;
            border-radius: 10px;
            margin-top: 60px;
            display: none;
            position: absolute;
            top: 90px;
            right: 100px;
            padding: 10px 10px;
            font-size: 14px;
        }

        #addEvent {
            font-size: 25px;
            background: none;
            border: none;
            color: black;
            cursor: pointer;
            margin-left: 230px;
            position: relative;
            top: -40px;
        }

        .event-form {
            margin-top: 20px;
            display: none;
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .event-form input,
        .event-form select {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .event-form button {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .event-form button:hover {
            background-color: #0056b3;
        }

        /* 預設隱藏選單選項 */
        .add-options {
            display: none;
            position: absolute;
            top: 170px;
            /* 調整選單顯示在按鈕下方 */
            left: 25%;
            /* 讓選單水平對齊在按鈕的中間 */
            transform: translateX(-50%);
            /* 使選單完全居中 */
            background-color: white;
            border: 1px solid #ccc;
            padding: 5px 10px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            z-index: 10;
            list-style: none;
        }

        .add-options li {
            margin: 5px 0;
        }

        /* 新增行程/日記選項（僅顯示文字） */
        .add-options button {
            background: none;
            border: none;
            color: inherit;
            /* 使用預設文字顏色 */
            padding: 0;
            font: inherit;
            /* 保持與其他文字樣式一致 */
            cursor: pointer;
        }

        .add-diary-btn {
            text-decoration: none;
            /* 去掉底線 */
            color: inherit;
            /* 繼承父元素的文字顏色 */
        }

        .activities-list {
            padding: 0;
            /* 去掉內邊距 */
            margin: 0;
            /* 去掉外邊距 */
        }

        .event-frame {
            background-color: #d9d9d9;
            /* 灰色背景 */
            padding: 10px;
            /* 內邊距 */
            margin: 10px 0;
            /* 活動之間的間距 */
            border-radius: 5px;
            /* 圓角 */
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            /* 可選的陰影效果 */
            text-align: left;
            /* 對齊文字 */
        }
    </style>
</head>

<body>
    <?php include "nav.php"; ?>

    <div class="a1-container">
        <div class="left-panel">
            <h5 id="selectedDate"><?php echo $date; ?> 行程</h5>
            <!-- 新增活動按鈕及選項 -->
            <div class="add-event-container">
                <button id="addEvent">+</button>
                <ul class="add-options">
                    <li><button id="addSchedule" data-bs-toggle="modal" data-bs-target="#planModal">新增行程</button></li>
                    <li>
                        <a href="diary.php" class="add-diary-btn">新增日記</a>
                    </li>
                </ul>
            </div>
            <button class="return-today" id="returnToday">返回今天</button>


            <div class="modal fade s3-modal" id="planModal" tabindex="-1" aria-labelledby="planModalLabel"
                aria-hidden="true">
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
                                    <input type="text" class="form-control" name="event_name">
                                </div>
                                <div class="mb-1">
                                    <label for="recipient-name" class="col-form-label">行程類型:</label>
                                    <select class="form-control" name="event_type">
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
                                    <input type="time" name="event_time" class="form-control">
                                </div>
                                <div class="mb-1">
                                    <label for="myTime" class="col-form-label">行程結束時間</label>
                                    <input type="time" name="end_time" class="form-control">
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
            <div class="activities">
                <div class="activities">
                    <div id="today-schedule">
                        <div class="activities-list">
                            <?php if (!empty($play)): ?>
                                <?php foreach ($play as $event): ?>
                                    <div class="event-frame">
                                        <strong><?php echo $event['event_name']; ?></strong><br>
                                        <?php echo $event['event_time'] . ' - ' . $event['end_time']; ?><br>
                                        <span class="event-remark"><?php echo $event['remark']; ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div>此日期無行程</div>
                            <?php endif; ?>
                        </div>
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
                const emptyDiv = document.createElement('div');
                calendarGrid.appendChild(emptyDiv);
            }

            for (let day = 1; day <= daysInCurrentMonth; day++) {
                const dayDiv = document.createElement('div');
                const circleDiv = document.createElement('div');
                circleDiv.classList.add('circle');
                circleDiv.textContent = day;

                if (year === today.getFullYear() && month === today.getMonth() && day === today.getDate()) {
                    circleDiv.style.backgroundColor = '#007bff';
                    circleDiv.style.color = 'white';
                }

                dayDiv.addEventListener('click', () => {
                    selectedDate = `${year}-${month + 1}-${day < 10 ? '0' + day : day}`;
                    document.getElementById('selectedDate').textContent = `${selectedDate} 行程`;
                    document.getElementById('eventDate').value = selectedDate;

                    document.querySelectorAll('.circle').forEach(circle => {
                        circle.style.backgroundColor = '';
                        circle.style.color = '';
                    });

                    circleDiv.style.backgroundColor = '#007bff';
                    circleDiv.style.color = 'white';

                    if (selectedDate !== `${today.getFullYear()}-${today.getMonth() + 1}-${today.getDate()}`) {
                        document.getElementById('returnToday').style.display = 'block';
                    } else {
                        document.getElementById('returnToday').style.display = 'none';
                    }
                });

                dayDiv.appendChild(circleDiv);
                calendarGrid.appendChild(dayDiv);
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
            document.getElementById('selectedDate').textContent = `${today.toISOString().split('T')[0]} 行程`;
            updateMonthYear();
            generateCalendar(currentYear, currentMonth);
            document.getElementById('returnToday').style.display = 'none';

            // 發送 AJAX 請求到 PHP
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'plan.php', true); // 替換成你的 PHP 文件名
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function () {
                if (xhr.status === 200) {
                    console.log(xhr.responseText); // 輸出 PHP 返回的內容
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

        updateMonthYear();
        generateCalendar(currentYear, currentMonth);

        document.getElementById('addSchedule').addEventListener('click', function () {
            const eventForm = document.getElementById('eventForm');
            eventForm.style.display = eventForm.style.display === 'none' ? 'block' : 'none';
        });
    </script>
</body>

</html>