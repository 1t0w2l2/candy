<?php
session_start();
include "db.php";

// 檢查用戶是否登入
$account = isset($_SESSION['account']) ? $_SESSION['account'] : '';
if (empty($account)) {
    header("Location: login.php");
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


            <!-- 新增行程的模態框 -->
            <div class="modal fade" id="planModal" tabindex="-1" aria-labelledby="planModalLabel" aria-hidden="true"
                data-bs-backdrop="static">
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
                </div>
                <button class="return-today" id="returnToday">返回今天</button>
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

        // 儲存變更
        document.getElementById('saveChangesBtn').addEventListener('click', function () {
            var form = document.getElementById('editEventForm');
            var formData = new FormData(form);

            // 添加 action 欄位來指定操作類型
            formData.append('action', 'edit');

            var xhr = new XMLHttpRequest();
            xhr.open('POST', '', true);
            xhr.onload = function () {
                if (xhr.status === 200) {
                    if (xhr.responseText.trim() === 'success') {
                        alert('儲存成功');
                        location.reload();  // 刷新頁面以顯示最新資料
                    } else {
                        alert('錯誤: ' + xhr.responseText);  // 顯示後端錯誤訊息
                    }
                } else {
                    alert('錯誤: ' + xhr.statusText);
                }
            };
            xhr.send(formData);  // 送出表單資料
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


//頁面加載時檢查是否有儲存的刪除日期
window.addEventListener('load', function () {
    var lastDeletedEventDate = localStorage.getItem('lastDeletedEventDate');

    if (lastDeletedEventDate) {
        console.log('已儲存的刪除日期:', lastDeletedEventDate);
        // 設置當前顯示的日期並在該日期顯示圓圈
        setCurrentDate(lastDeletedEventDate);

    } else {
        console.log('沒有儲存的刪除日期');
    }
});




    </script>

</body>

</html>