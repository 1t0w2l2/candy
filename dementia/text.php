function generateCalendar(year, month, selectedDay) {
    const calendarGrid = document.querySelector('.calendar-grid'); // 假設有這個容器
    calendarGrid.innerHTML = ''; // 清空現有的日曆

    const daysInMonth = new Date(year, month + 1, 0).getDate(); // 當月天數
    for (let day = 1; day <= daysInMonth; day++) {
        const dayCell = document.createElement('div');
        dayCell.classList.add('calendar-day');

        const circleDiv = document.createElement('div');
        circleDiv.classList.add('circle');
        circleDiv.textContent = day;

        // 設置日期屬性
        const date = `${year}-${(month + 1).toString().padStart(2, '0')}-${day.toString().padStart(2, '0')}`;
        circleDiv.setAttribute('data-date', date);

        // 判斷是否為選定日期並設置高亮樣式
        if (day === selectedDay) {
            circleDiv.style.backgroundColor = '#007bff';
            circleDiv.style.color = 'white';
        }

        // 點擊事件
        circleDiv.addEventListener('click', () => {
            document.getElementById('selectedDate').textContent = `${date} 行程`;
            document.getElementById('eventDate').value = date;

            // 清除其他圓形的高亮樣式
            document.querySelectorAll('.circle').forEach(circle => {
                circle.style.backgroundColor = '';
                circle.style.color = '';
            });

            // 高亮當前點擊的日期
            circleDiv.style.backgroundColor = '#007bff';
            circleDiv.style.color = 'white';

            // 顯示返回今天按鈕
            if (date !== todayString) {
                returnTodayButton.style.display = 'block';
            } else {
                returnTodayButton.style.display = 'none';
            }

            // 儲存到 localStorage 並發送 AJAX 請求
            localStorage.setItem('selectedDate', date);

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

        // 將圓形元素添加到日曆格
        dayCell.appendChild(circleDiv);
        calendarGrid.appendChild(dayCell);
    }
}