let countdown;
let targetDate;
const timeDisplay = document.getElementById('timeDisplay');
const datetimeInput = document.getElementById('datetimeInput');
const startButton = document.getElementById('startButton');
const resetButton = document.getElementById('resetButton');

function updateTimeDisplay(remainingTime) {
    const hours = Math.floor(remainingTime / (1000 * 60 * 60));
    const minutes = Math.floor((remainingTime % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((remainingTime % (1000 * 60)) / 1000);
    timeDisplay.textContent = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
}

function startCountdown() {
    if (countdown) {
        clearInterval(countdown);
    }
    countdown = setInterval(() => {
        const now = new Date().getTime();
        const remainingTime = targetDate - now;

        if (remainingTime <= 0) {
            clearInterval(countdown);
            timeDisplay.textContent = '00:00:00';
            startButton.textContent = '開始';
            alert('目標日期及時間已到，計時結束！');
        } else {
            updateTimeDisplay(remainingTime);
        }
    }, 1000);
}

function toggleTimer() {
    if (startButton.textContent === '開始') {
        startCountdown();
        startButton.textContent = '暫停';
    } else if (startButton.textContent === '暫停') {
        clearInterval(countdown);
        startButton.textContent = '繼續';
    } else if (startButton.textContent === '繼續') {
        startCountdown();
        startButton.textContent = '暫停';
    }
}

startButton.addEventListener('click', () => {
    const targetDateTime = datetimeInput.value;
    if (!targetDateTime) {
        alert('請設定目標日期及時間。');
        return;
    }

    targetDate = new Date(targetDateTime).getTime();
    const now = new Date().getTime();

    if (targetDate <= now) {
        alert('目標時間已過，請重新設定。');
        return;
    }

    toggleTimer();
});

resetButton.addEventListener('click', () => {
    clearInterval(countdown);
    timeDisplay.textContent = '00:00:00';
    startButton.textContent = '開始';
    datetimeInput.value = '';
});