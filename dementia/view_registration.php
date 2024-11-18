<?php

session_start();
include "db.php"; // 確保 db.php 中包含 mysqli 連線的代碼

// 檢查用戶是否已登錄
$account = isset($_SESSION['account']) ? $_SESSION['account'] : '';
if (empty($account)) {
    header("Location: login.php");
    exit();
}

// 錯誤處理：檢查資料庫連線是否成功
if (!$link) {
    die("資料庫連線失敗: " . mysqli_connect_error());
}

$activity_id = isset($_GET['activity_id']) ? intval($_GET['activity_id']) : 0;

// 處理搜尋請求
$search_input = '';
$registrations = [];

// 取得活動名稱
$activity_name = '';
if ($activity_id > 0) {
    $activity_query = "SELECT activity_name FROM activity WHERE activity_id = '$activity_id'";
    $activity_result = mysqli_query($link, $activity_query);
    if ($activity_result && mysqli_num_rows($activity_result) > 0) {
        $activity_row = mysqli_fetch_assoc($activity_result);
        $activity_name = $activity_row['activity_name'];
    } else {
        die("查詢活動名稱失敗: " . mysqli_error($link));
    }
}



// 搜尋報名資料
if (isset($_POST['action']) && $_POST['action'] === 'search') {
    $search_input = mysqli_real_escape_string($link, $_POST['search_input']);

    // 根據搜尋條件查詢報名資訊
    $query = "
    SELECT 
        r.registration_id, 
        r.account, 
        j.account AS join_account, 
        u.name AS participant_name, 
        r.registration_time, 
        j.attended
    FROM 
        registration r 
    JOIN 
        join_activity j ON r.registration_id = j.registration_id 
    JOIN 
        user u ON j.account = u.account 
    WHERE 
        r.activity_id = '$activity_id' 
        AND (r.account LIKE '%$search_input%' OR u.name LIKE '%$search_input%') 
        AND r.status = '已報名'";

    // 添加状态条件
    if ($search_input === '未到') {
        $query .= " OR j.attended = 0"; // 0 代表未到
    } elseif ($search_input === '有來') {
        $query .= " OR j.attended = 1"; // 1 代表已到
    }
} else {
    // 查詢所有報名資訊
    $query = "SELECT r.registration_id ,r.account,j.account AS join_account, u.name AS participant_name, r.registration_time 
    FROM registration r 
    JOIN join_activity j ON r.registration_id = j.registration_id 
    JOIN user u ON j.account = u.account 
    WHERE r.activity_id = '$activity_id' AND r.status = '已報名';";
}

$result = mysqli_query($link, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        // 使用 account 作為鍵來防止重複
        $registrations[$row['account']] = $row;
    }
} else {
    die("查詢報名資料失敗: " . mysqli_error($link));
}

// 將唯一的報名資料轉換為數值索引陣列
$registrations = array_values($registrations);

// 回傳活動的問題與選項
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['activity_id']) && isset($_POST['account'])) {
    $activity_id = intval($_POST['activity_id']);
    $account = mysqli_real_escape_string($link, $_POST['account']);

    // 根據活動ID和帳號查詢問題
    $sql_questions = "SELECT * FROM questions WHERE activity_id = '$activity_id'";
    $result_questions = mysqli_query($link, $sql_questions);

    if (!$result_questions) {
        echo json_encode(['success' => false, 'error' => '問題查詢錯誤: ' . mysqli_error($link)]);
        exit();
    }

    $questions = [];
    while ($row = mysqli_fetch_assoc($result_questions)) {
        $questions_id = $row['questions_id'];

        // 查詢該問題的選項
        $sql_options = "SELECT * FROM options WHERE questions_id = '$questions_id'";
        $result_options = mysqli_query($link, $sql_options);
        $options = [];
        while ($options_row = mysqli_fetch_assoc($result_options)) {
            $options[] = [
                'options_id' => $options_row['options_id'],
                'options_text' => $options_row['options_text'],
                'options_type' => $options_row['options_type'],
            ];
        }

        // 查詢使用者的回答（針對單選、多選與簡答題）
        $sql_answers = "SELECT options_id, answer_text FROM responses WHERE questions_id = '$questions_id' AND account = '$account' AND activity_id = '$activity_id'";
        $result_answers = mysqli_query($link, $sql_answers);
        $user_answer = null;

        if ($result_answers && mysqli_num_rows($result_answers) > 0) {
            // 如果是多選，獲取所有選項的 ID
            $user_answer_ids = [];
            while ($answer_row = mysqli_fetch_assoc($result_answers)) {
                if (!empty($answer_row['options_id'])) {
                    // 如果是選擇題，加入選項 ID
                    $user_answer_ids[] = $answer_row['options_id'];
                } elseif (!empty($answer_row['answer_text'])) {
                    // 簡答題，返回 answer_text
                    $user_answer = [
                        'type' => 'text',
                        'answer' => $answer_row['answer_text']
                    ];
                }
            }

            // 將選擇的 ID 整理到 user_answer 中
            if (!empty($user_answer_ids)) {
                $user_answer = [
                    'type' => 'multiple', // 表示是多選
                    'options_ids' => $user_answer_ids
                ];
            }
        }

        // 將問題、選項及使用者的回答整理到返回數據中
        $questions[] = [
            'questions_text' => $row['questions_text'],
            'options' => $options,
            'user_answer' => $user_answer,  // 加入使用者的回答
        ];
    }

    // 設置返回的內容類型為 JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'questions' => $questions,
    ]);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['join_account']) && isset($_POST['attended']) && isset($_POST['activity_id'])) {
    $registration_id = $_POST['registration_id'];
    $join_account = $_POST['join_account'];
    $attended_status = $_POST['attended'];
    $activity_id = $_POST['activity_id'];

    // 更新參加狀態
    $sql_update = "UPDATE join_activity SET attended = '$attended_status' WHERE registration_id = '$registration_id' AND account = '$join_account' AND activity_id = '$activity_id'";

    if (mysqli_query($link, $sql_update)) {
        // 回傳 JSON 成功訊息
        echo json_encode(['status' => 'success', 'message' => '參加狀態已更新']);
    } else {
        // 回傳 JSON 失敗訊息
        echo json_encode(['status' => 'error', 'message' => '更新失敗: ' . mysqli_error($link)]);
    }
    exit();  // 確保結束腳本運行，避免回傳多餘的 HTML 內容
}

//匯出excel
require 'vendor/autoload.php';
use Shuchkin\SimpleXLSXGen;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['activity_id'])) {
    $activity_id = intval($_POST['activity_id']); // 確保 activity_id 是整數
    exportToExcel($activity_id, $link);
}

function exportToExcel($activity_id, $link) {
   
    // 使用 mysqli_query 查詢資料
    $query = "
    SELECT 
        r.account AS 報名帳號, 
        u.name AS 參加姓名, 
        q.questions_text AS 問題, 
        CASE 
            WHEN o.options_type = 'checkbox' THEN GROUP_CONCAT(DISTINCT o.options_text SEPARATOR ', ')
            WHEN o.options_type = 'radio' THEN MAX(o.options_text)  -- 只取 radio 的一個選項
            ELSE MAX(r.answer_text)  -- 只取其他回答的最大值
        END AS 答案, 
        re.registration_time AS 報名時間 
    FROM questions q 
    JOIN responses r ON r.questions_id = q.questions_id 
    LEFT JOIN options o ON r.options_id = o.options_id 
    JOIN activity a ON a.activity_id = q.activity_id 
    JOIN registration re ON re.activity_id = a.activity_id 
    JOIN join_activity j ON j.registration_id = re.registration_id 
    JOIN user u ON u.account = j.account 
    WHERE a.activity_id = '$activity_id' AND re.status = '已報名' 
    GROUP BY r.account, u.name, re.registration_time, q.questions_text 
    ORDER BY r.account, q.questions_id ASC;";

    $result = mysqli_query($link, $query);

    // 檢查查詢是否成功
    if (!$result) {
        die('查詢失敗: ' . mysqli_error($link));
    }

    // 儲存數據
    $data = [];
    $questions = [];

    while ($row = mysqli_fetch_assoc($result)) {
        // 將問題添加到問題數組中
        if (!in_array($row['問題'], $questions)) {
            $questions[] = $row['問題'];
        }
        
        // 儲存每行數據
        $data[$row['報名帳號']][] = [
            '參加姓名' => $row['參加姓名'],
            '問題' => $row['問題'],
            '答案' => $row['答案'],
            '報名時間' => $row['報名時間']
        ];
    }

    // 設置表頭
    $header = ['報名帳號', '參加姓名', '報名時間'];
    foreach ($questions as $question) {
        $header[] = $question;
    }

    // 將表頭添加到數據數組中
    $finalData = [];
    $finalData[] = $header;

    // 遍歷每個報名帳號的答案
    foreach ($data as $account => $answers) {
        $row = [$account, $answers[0]['參加姓名'], $answers[0]['報名時間']];
        
        foreach ($questions as $question) {
            $answerFound = false;
            $answersForQuestion = [];
            foreach ($answers as $answer) {
                if ($answer['問題'] === $question) {
                    // 只有當問題類型為 checkbox 時，才用逗號分隔
                    if ($answer['答案'] !== '') {
                        $answersForQuestion[] = $answer['答案'];
                    }
                    $answerFound = true;
                }
            }
            if ($answerFound) {
                if (count($answersForQuestion) > 0) {
                    $row[] = implode(', ', array_unique($answersForQuestion)); // 用逗號分隔的唯一答案
                } else {
                    $row[] = ''; // 沒有答案則留空
                }
            } else {
                $row[] = ''; // 沒有答案則留空
            }
        }
        $finalData[] = $row;
    }

    // 關閉數據庫連接
    $link->close();

    // 生成並下載 Excel 文件
    $xlsx = SimpleXLSXGen::fromArray($finalData);
    $xlsx->downloadAs('活動表單回答.xlsx');
    exit; // 退出以防止繼續執行後續代碼
}

?>

<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <?php include "head.php"; ?>
</head>

<body>
    <?php include "nav.php"; ?>
    <div class="s4-container">
        <div class="filter-section">
            <h2 style="text-align: center;">報名查詢</h2>
            <form action="" method="POST" class="input-group">
                <div class="mb-3" style="width:100%">
                    <input type="text" name="search_input" placeholder="請輸入查詢內容"
                        value="<?php echo htmlspecialchars($search_input); ?>" class="form-control">
                </div>
                <div class="mb-3" style="width:100%">
                    <button type="submit" class="s7-btn" name="action" value="search">
                        <i class="fa fa-search"></i> 搜尋
                    </button>
                </div>
            </form>
        </div>
        <div class="table-responsive" style="width:70%;">
            <h3>活動名稱：<?php echo htmlspecialchars($activity_name); ?></h3>
            <form method="POST">
                <input type="hidden" name="activity_id" value="<?php echo htmlspecialchars($activity_id); ?>">
                <input type="submit" class="s6-btn-c" value="匯出Excel">
            </form>
            <table class="s4-table" id="registration_table">
                <thead>
                    <tr>
                        <th style="font-size:15px;">#</th>
                        <th style="font-size:15px;">報名帳號</th>
                        <th style="font-size:15px;">參加者姓名</th>
                        <th style="font-size:15px;">報名時間</th>
                        <th style="font-size:15px;">操作</th>
                        <th style="font-size:15px;">參加狀態</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($registrations)): ?>
                        <tr>
                            <td colspan="6">查無資料</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($registrations as $index => $registration): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($registration['account']); ?></td>
                                <td><?php echo htmlspecialchars($registration['participant_name']); ?></td>
                                <td><?php echo htmlspecialchars($registration['registration_time']); ?></td>
                                <td>
                                    <button class="s5-btn-b"
                                        onclick="setActivityName('<?php echo htmlspecialchars($registration['account']); ?>')">
                                        查看表單
                                    </button>
                                </td>
                                <td style="font-size: 15px;">
                                    <span class="status-text" id="status-text">
                                        <?php
                                        // 查詢參加狀態
                                        $attended_query = "SELECT attended FROM join_activity WHERE registration_id = '" . $registration['registration_id'] . "' AND account = '" . $registration['join_account'] . "' AND activity_id = '$activity_id'";
                                        $attended_result = mysqli_query($link, $attended_query);
                                        if ($attended_result && mysqli_num_rows($attended_result) > 0) {
                                            $attended_row = mysqli_fetch_assoc($attended_result);
                                            $attended = $attended_row['attended'];

                                            // 檢查 attended 欄位是否為 NULL
                                            if (is_null($attended)) {
                                                // 如果為 NULL，顯示按鈕
                                                echo '<button class="s6-btn" data-registration_id="' . $registration['registration_id'] . '" data-join_account="' . $registration['join_account'] . '" data-activity-id="' . $activity_id . '" onclick="confirmAttendance(true, this)">
                                                        <i class="fa-solid fa-square-check"></i>
                                                        </button>
                                                        <button class="s6-btn" data-registration_id="' . $registration['registration_id'] . '" data-join_account="' . $registration['join_account'] . '" data-activity-id="' . $activity_id . '" onclick="confirmAttendance(false, this)">
                                                        <i class="fa-solid fa-square-xmark"></i>
                                                       </button>';
                                            } else {
                                                // 如果不為 NULL，顯示狀態
                                                echo $attended === '1' ? '有來' : '未到';
                                            }
                                        } else {
                                            echo "查詢狀態失敗: " . mysqli_error($link);
                                        }
                                        ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 模態對話框 -->
    <div class="modal fade s3-modal" id="exampleModalLabel" tabindex="-1" role="dialog"
        aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog custom-modal-dialog modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">報名詳細資訊</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="question-container"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">關閉</button>
                </div>

            </div>
        </div>
    </div>

    <script>
        function confirmAttendance(attended, btnElement) {
            const registration_id = btnElement.getAttribute('data-registration_id');
            const join_account = btnElement.getAttribute('data-join_account');
            const activity_id = btnElement.getAttribute('data-activity-id');

            const formData = new FormData();
            formData.append('registration_id', registration_id);
            formData.append('join_account', join_account);
            formData.append('activity_id', activity_id);
            formData.append('attended', attended ? '1' : '0');

            fetch('view_registration.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())  // 解析回傳的 JSON
                .then(data => {
                    if (data.status === 'success') {
                        // 更新狀態文字
                        const statusText = attended ? '有來' : '未到';
                        btnElement.closest('td').querySelector('.status-text').innerText = statusText;

                        // 隱藏按鈕
                        const buttons = btnElement.closest('td').querySelectorAll('.s6-btn');
                        buttons.forEach(btn => btn.style.display = 'none');
                    } else {
                        // 顯示錯誤訊息
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('錯誤:', error);
                });
        }

        function setActivityName(account) {
            const activity_id = '<?php echo $activity_id; ?>'; // 獲取 activity_id
            fetch('view_registration.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'account': account,
                    'activity_id': activity_id
                })
            })
                .then(response => response.json())
                .then(data => {
                    console.log(data);
                    if (data.success) {
                        if (Array.isArray(data.questions) && data.questions.length > 0) {
                            let questionsHTML = '';
                            data.questions.forEach(question => {
                                questionsHTML += `
                            <div class="mb-4 p-3 border rounded shadow-sm bg-light">
                                <p class="font-weight-bold questions-text" style="font-size:20px;">${question.questions_text}</p>`;

                                // 儲存用戶選擇的 options_id
                                const userSelectedOptions = question.user_answer && question.user_answer.type === 'multiple' ? question.user_answer.options_ids : [];

                                // 循環選項並檢查用戶回答
                                question.options.forEach(option => {
                                    const checked = userSelectedOptions.includes(option.options_id) ? 'checked' : '';
                                    if (option.options_type === 'radio') {
                                        questionsHTML += `
                                    <div class="form-check">
                                        <input type="radio" class="form-check-input" name="answer[${question.questions_text}]" value="${option.options_text}" ${checked} readonly>
                                        <label class="form-check-label">${option.options_text}</label>
                                    </div>`;
                                    } else if (option.options_type === 'checkbox') {
                                        questionsHTML += `
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="answer[${question.questions_text}][]" value="${option.options_text}" ${checked} readonly>
                                        <label class="form-check-label">${option.options_text}</label>
                                    </div>`;
                                    } else if (option.options_type === 'text') {
                                        questionsHTML += `
                                    <div class="form-group">
                                        <input type="text" class="form-control" name="answer[${question.questions_text}]" value="${question.user_answer ? question.user_answer.answer : ''}" placeholder="請輸入您的回答" readonly>
                                    </div>`;
                                    }
                                });
                                questionsHTML += `</div>`;
                            });
                            document.querySelector('.question-container').innerHTML = questionsHTML;
                        }
                    }
                });

            $('#exampleModalLabel').modal('show'); // 顯示模態對話框
        }





    </script>

</body>

</html>