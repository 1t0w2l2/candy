<?php include "db.php";
//檢查是否有登入
$account = isset($_SESSION['account']) ? $_SESSION['account'] : '';
if (!$account) {
    header("Location: login.php");
    exit();
}

// 查詢使用者類型
$sql_user_type = "SELECT user_type FROM user WHERE account='$account'";
$result_user_type = mysqli_query($link, $sql_user_type);
if (!$result_user_type) {
    echo "<script>alert('查詢失敗: " . mysqli_error($link) . "');</script>";
    exit();
}
$user_type = mysqli_fetch_assoc($result_user_type)['user_type'];

//對不同使用類型讀取互動資訊
if ($user_type === 'hospital') {
    $sql_institution_id = "SELECT institution_id FROM hospital WHERE account = '$account'";
    $result_institution_id = mysqli_query($link, $sql_institution_id);

    if (!$result_institution_id || mysqli_num_rows($result_institution_id) === 0) {
        echo "<script>alert('找不到醫療機構。');</script>";
        exit();
    }

    $institution_id = mysqli_fetch_assoc($result_institution_id)['institution_id'];

    // 獲取所有綁定的帳號並存入陣列
    $sql_pc = "SELECT u.name, p.account FROM patientcarelink p JOIN user u ON u.account = p.account WHERE institution_id = '$institution_id'";
    $result_pc = mysqli_query($link, $sql_pc);

    if (!$result_pc) {
        echo "<script>alert('查詢失敗: " . mysqli_error($link) . "');</script>";
        exit();
    }

    $accounts = [];
    $names = []; // 初始化陣列
    while ($pc_data = mysqli_fetch_assoc($result_pc)) {
        $names[] = htmlspecialchars($pc_data['name']);
        $accounts[] = htmlspecialchars($pc_data['account']);
    }
} elseif ($user_type === 'caregiver') {
    $sql_pc = "SELECT h.institution_id, h.institution_name, h.account FROM patientcarelink p 
                JOIN hospital h ON h.institution_id = p.institution_id
                WHERE p.account='$account' AND p.user_type='caregiver'";
    $result_pc = mysqli_query($link, $sql_pc);

    if (!$result_pc) {
        echo "<script>alert('查詢失敗: " . mysqli_error($link) . "');</script>";
        exit();
    }

    $accounts = [];
    $names = [];

    // 只需一個變數存儲 institution_id
    $institution_id = null; // 初始化變數

    while ($pc_data = mysqli_fetch_assoc($result_pc)) {
        $names[] = htmlspecialchars($pc_data['institution_name']);
        $accounts[] = htmlspecialchars($pc_data['account']);

        // 取得 institution_id 只在第一次迴圈時存儲
        if ($institution_id === null) {
            $institution_id = htmlspecialchars($pc_data['institution_id']);
        }
    }

} elseif ($user_type === 'patient') {
    $sql_pc = "SELECT h.institution_id,h.institution_name, h.account FROM patientcarelink p 
                JOIN hospital h ON h.institution_id = p.institution_id
                WHERE p.account='$account' and p.user_type='patient'";
    $result_pc = mysqli_query($link, $sql_pc);

    if (!$result_pc) {
        echo "<script>alert('查詢失敗: " . mysqli_error($link) . "');</script>";
        exit();
    }

    $accounts = [];
    $names = []; // 初始化陣列
    $institution_id = null; // 初始化變數

    while ($pc_data = mysqli_fetch_assoc($result_pc)) {
        $names[] = htmlspecialchars($pc_data['institution_name']); // 儲存名稱
        $accounts[] = htmlspecialchars($pc_data['account']);       // 儲存帳號
        // 取得 institution_id 只在第一次迴圈時存儲
        if ($institution_id === null) {
            $institution_id = htmlspecialchars($pc_data['institution_id']);
        }
    }
} else {
    echo "<script>alert('此用戶無法訪問聊天功能。');</script>";
    exit();
}

//讀取使用者個人資料
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['showAccount'])) {
    $showAccount = mysqli_real_escape_string($link, $_POST['showAccount']);
    if (!$showAccount) {
        echo "<p>帳號無效</p>";
        exit();
    }

    // 查詢使用者的詳細資訊
    $sql = "SELECT name, email, phone, address FROM user WHERE account = '$showAccount'";
    $result = mysqli_query($link, $sql);


    if ($result) {
        $user_info = mysqli_fetch_assoc($result);
        if ($user_info) {
            // 獲取使用者類型
            $user_type = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : '';
            echo "
            <div class='info'>
                <div class='info-item'>👤 姓名: <span id='userName'>" . htmlspecialchars($user_info['name']) . "</span></div>
                <div class='info-item'>📧 電子信箱: <span id='userEmail'>" . htmlspecialchars($user_info['email']) . "</span></div>
                <div class='info-item'>📞 電話: <span id='userPhone'>" . htmlspecialchars($user_info['phone']) . "</span></div>
                <div class='info-item'>🏠 地址: <span id='userAddress'>" . htmlspecialchars($user_info['address']) . "</span></div>
                <div class='button-container'>";
            // 根據使用類型顯示或隱藏刪除按鈕
            if ($user_type !== 'patient' && $user_type !== 'caregiver') {
                echo "<button class='deleteButton'>刪除綁定</button>";
            }
            echo "
                </div>
            </div>";
            exit();
        } else {
            echo "<p>找不到該使用者</p>";
            exit();
        }
    } else {
        echo "<p>查詢失敗: " . mysqli_error($link) . "</p>";
        exit();
    }
}


if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// 初始化選擇的帳號變數
$accountDisplay = isset($_SESSION['selectedAccount']) ? $_SESSION['selectedAccount'] : '';
$send_account = isset($_SESSION['account']) ? $_SESSION['account'] : '';

// 處理 POST 請求
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['selectedAccount'])) {
    $pc_account = mysqli_real_escape_string($link, $_POST['selectedAccount']);
    $errorMessages = []; // 用來儲存錯誤信息
    // echo "<script type='text/javascript'>alert('" . $pc_account . "');</script>";

    // 取得患者关联 ID
    $sql_patientcarelink = "SELECT patientcarelink_id FROM patientcarelink WHERE account = '$send_account' OR account = '$pc_account'";
    $result = mysqli_query($link, $sql_patientcarelink);
    if ($result) {
        $patientcarelink_id = null; // 初始化变量
        if ($row = mysqli_fetch_assoc($result)) {
            $patientcarelink_id = $row['patientcarelink_id'];
        } else {
            $errorMessages[] = "未找到患者關聯 ID";
        }
    } else {
        $errorMessages[] = "資料庫查詢失敗： " . mysqli_error($link);
    }
    // 检查是否有消息
    if (!empty($_POST['message'])) {
        $message_content = mysqli_real_escape_string($link, $_POST['message']);
        $sql_insert_message = "INSERT INTO message (account, send_account, institution_id, patientcarelink_id, content, created_at) 
                               VALUES ('$pc_account', '$send_account', '$institution_id', '$patientcarelink_id', '$message_content', NOW())";
        if (!mysqli_query($link, $sql_insert_message)) {
            $errorMessages[] = '發送消息時發生錯誤： ' . mysqli_error($link);
        }
    }
    // 檢查是否有圖片上傳
    if (isset($_FILES['images']) && count($_FILES['images']['name']) > 0) {
        // 確保上傳目錄存在
        $uploadDir = './message/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        // 先插入消息以獲取 messageId
        $sql_insert_message = "INSERT INTO message (account, send_account, institution_id, patientcarelink_id, content, created_at) 
                               VALUES ('$pc_account', '$send_account', '$institution_id', '$patientcarelink_id', '圖片', NOW())";

        if (!mysqli_query($link, $sql_insert_message)) {
            $errorMessages[] = '發送消息時發生錯誤： ' . mysqli_error($link);
        } else {
            $messageId = mysqli_insert_id($link); // 獲取插入的消息 ID

            // 上傳圖片
            for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
                $fileName = basename($_FILES['images']['name'][$i]);
                $fileTmpPath = $_FILES['images']['tmp_name'][$i];
                $fileError = $_FILES['images']['error'][$i];

                if ($fileError === UPLOAD_ERR_OK) {
                    $filePath = $uploadDir . uniqid('img_', true) . '_' . $fileName;
                    if (move_uploaded_file($fileTmpPath, $filePath)) {
                        // 將圖片路徑與 message_id 關聯
                        $sqlInsertImage = "INSERT INTO message_picture (message_id, image_name) VALUES ('$messageId', '$filePath')";
                        if (!mysqli_query($link, $sqlInsertImage)) {
                            $errorMessages[] = '插入圖片路徑時發生錯誤： ' . mysqli_error($link);
                        }
                    } else {
                        $errorMessages[] = '無法移動上傳的文件。';
                    }
                } else {
                    $errorMessages[] = '圖片上傳錯誤： ' . $fileError;
                }
            }
        }
    }
    // 顯示錯誤或成功消息显
    if (!empty($errorMessages)) {
        echo "<script>alert('" . implode('\\n', $errorMessages) . "');</script>";
    } else {
        echo "<script>window.location.href = 'chat.php';</script>";
    }
    exit();
}


//新增綁定
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'add') {
    // 從表單中取得輸入的帳號及當前使用者帳號
    $selectedAccount = $_POST['modalPatient'];
    $currentAccount = $_SESSION['account'];

    // 構建通知內容
    $notificationContent = "已發送綁定通知";

    // 首先檢查通知是否已存在
    $check_sql = "SELECT * FROM notification WHERE account='$selectedAccount' AND send_account='$currentAccount' 
                  AND notification_type='binding' AND content='$notificationContent' AND is_read=0";
    $check_result = mysqli_query($link, $check_sql);

    if (mysqli_num_rows($check_result) == 0) {
        // 如果不存在，則插入通知
        $sql = "INSERT INTO notification (account, send_account, notification_type, content, is_read) 
                VALUES ('$selectedAccount', '$currentAccount', 'binding', '$notificationContent', 0)";

        // 執行 SQL 語句
        if (mysqli_query($link, $sql)) {
            echo "<script>alert('綁定通知發送成功！');</script>";
        } else {
            echo "<script>alert('發送通知失敗，請重試');</script>";
        }
    } else {
        // 如果已存在，顯示提示
        echo "<script>alert('通知已經發送過，無需重複發送！');</script>";
    }
}

// 讀取聊天歷史的邏輯
if (isset($_GET['selectedAccount'])) {
    $selectedAccount = mysqli_real_escape_string($link, $_GET['selectedAccount']);

    $sql_messages = "
    SELECT m.content, m.created_at, m.send_account, mp.image_name, m.is_read
    FROM message m 
    LEFT JOIN message_picture mp ON m.message_id = mp.message_id 
    WHERE (m.account = '$selectedAccount' AND m.send_account = '$account') 
       OR (m.account = '$account' AND m.send_account = '$selectedAccount')
    ORDER BY m.created_at ASC";

    $result_messages = mysqli_query($link, $sql_messages);

    $messages = []; // 用來儲存訊息的陣列

    if ($result_messages) {
        while ($message = mysqli_fetch_assoc($result_messages)) {
            // 處理圖片名稱
            if ($message['image_name']) {
                $message['image'] = htmlspecialchars($message['image_name']);
                $message['content'] = null; // 如果有圖片，設置內容為 null
            } else {
                $message['content'] = nl2br(htmlspecialchars($message['content']));
                $message['image'] = null; // 如果沒有圖片，設置為 null
            }

            // 如果 content 是 '圖片'，也設置為 null
            if ($message['content'] === '圖片') {
                $message['content'] = null;
            }

            $messages[] = $message; // 將訊息添加到陣列中
        }
    }

    // 顯示聊天歷史
    foreach ($messages as $msg) {
        // 判斷發送者以設置正確的類別
        $class = (isset($msg['send_account']) && $msg['send_account'] === $_SESSION['account']) ? 'sent' : 'received';
        echo "<div class='message {$class}'>";

        if ($msg['image']) {
            echo "<img src='" . $msg['image'] . "' alt='Image' class='image-preview' ondblclick='openImage(this.src)' />";
        }
        if ($msg['content']) {
            echo "<p>" . $msg['content'] . "</p>";
        }

        // 解析時間
        $time = new DateTime($msg['created_at']);
        $period = $time->format('A') === 'AM' ? '上午' : ($time->format('A') === 'PM' && $time->format('H') < 18 ? '下午' : '晚上');
        if ($msg['send_account'] === $_SESSION['account']) {
            $timeSpan = "<span class='time2'>{$period} " . $time->format('H:i') . ($msg['is_read'] == 1 ? ' 已讀' : ' 未讀') . "</span>";
        } else {
            $timeSpan = "<span class='time2'>" . ($msg['is_read'] == 1 ? '已讀' : '未讀') . " {$period} " . $time->format('H:i') . "</span>";
        }
        echo $timeSpan; // 顯示時間
        echo "</div>";
    }
    exit();
}

//刪除綁定
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['deleteAccount'])) {
    // 從 POST 請求中獲取並轉義 `deleteAccount`
    $deleteAccount = mysqli_real_escape_string($link, $_POST['deleteAccount']);
    $sql = "DELETE FROM patientcarelink WHERE account='$deleteAccount';";
    if (mysqli_query($link, $sql)) {
        if (mysqli_affected_rows($link) > 0) {
            echo "刪除成功";
        } else {
            // 帳號不存在
            echo "找不到該使用者";
        }
        exit();
    } else {
        echo "刪除失敗: " . mysqli_error($link);
        exit();
    }
}

//查詢好友
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search'])) {
    $searchValue = trim($_POST['search']); // 獲取並清理輸入的值

    if (!empty($searchValue)) {
        // 防止 SQL 注入
        $likeSearchValue = "%" . mysqli_real_escape_string($link, $searchValue) . "%";

        // 根據 user_type 分開查詢
        $sql_check_user_type = "SELECT user_type FROM user WHERE account = '$account'";
        $result_user_type = mysqli_query($link, $sql_check_user_type);

        // 默認為空的 SQL 查詢
        $sql_search = "";

        // 如果能夠查詢到 user_type
        if ($row_user_type = mysqli_fetch_assoc($result_user_type)) {
            $user_type = $row_user_type['user_type'];

            // 如果是醫療機構（hospital）
            if ($user_type == 'hospital') {
                $sql_search = "
                    SELECT u.name, m.account, m.content, m.created_at
                    FROM message m
                    JOIN user u ON m.account = u.account
                    JOIN patientcarelink p ON p.institution_id = m.institution_id
                    WHERE 
                        (m.account LIKE '$likeSearchValue' 
                         OR u.name LIKE '$likeSearchValue'
                         OR m.content LIKE '$likeSearchValue') 
                        AND p.institution_id = '$institution_id'
                    ORDER BY m.created_at DESC
                    LIMIT 1;
                ";
            }
            // 如果是患者或照護者（patient/caregiver）
            else if ($user_type == 'patient' || $user_type == 'caregiver') {
                $sql_search = "
                    SELECT u.name,m.account, m.content, m.created_at, h.institution_name 
                    FROM message m 
                    JOIN user u ON m.account = u.account 
                    JOIN hospital h ON u.account = h.account 
                    WHERE (m.account LIKE '$likeSearchValue' 
                    OR h.institution_name LIKE '$likeSearchValue' 
                    OR m.content LIKE '$likeSearchValue') 
                    ORDER BY m.created_at DESC LIMIT 1;
                ";
            }
        }

        // 如果有設定 SQL 查詢
        if ($sql_search !== "") {
            if ($result = mysqli_query($link, $sql_search)) {
                $output = ""; // 初始化輸出變數
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $name = htmlspecialchars($row['name']);
                        $pc_account = htmlspecialchars($row['account']);
                        $content = htmlspecialchars($row['content']);
                        $recent_message_time = $row['created_at'];
                        $institution_name = htmlspecialchars($row['institution_name']); // 取得醫療機構名稱

                        // 格式化時間
                        $time = date('H:i', strtotime($recent_message_time));
                        $hours = date('H', strtotime($recent_message_time));
                        $period = ($hours < 12) ? '上午' : (($hours < 18) ? '下午' : '晚上');

                        // 建立結果 HTML
                        $output .= "<div class='custom-box' onclick=\"showAccount('$pc_account', '$institution_name')\">";
                        $output .= "    <div class='account-time-row'>";

                        // 判斷是醫療機構還是患者/照護者，顯示對應的名稱
                        if ($user_type == 'hospital') {
                            $output .= "        <p class='account'>$name ($pc_account)</p>";
                        } else {
                            $output .= "        <p class='account'>$institution_name ($pc_account)</p>"; // 顯示醫療機構名稱
                        }

                        $output .= "        <p class='time1'>$period $time</p>";
                        $output .= "    </div>";
                        $output .= "        <p class='p2'>$content</p>";
                        $output .= "</div>";
                    }
                    echo $output; // 直接輸出查詢結果
                } else {
                    echo "<div class='custom-box'><p>未找到任何相關結果。</p></div>";
                }
                mysqli_free_result($result);
            } else {
                echo "<div class='custom-box'><p>查詢錯誤: " . mysqli_error($link) . "</p></div>";
            }
        }
    }
    exit();
}


//是否已讀
if (isset($_SESSION['account'], $_POST['pc_account'])) {
    $account = mysqli_real_escape_string($link, $_SESSION['account']);  // 當前用戶
    $pc_account = mysqli_real_escape_string($link, $_POST['pc_account']);  // 目標帳號

    // 查詢未讀消息數量
    $sql_check_unread = "
        SELECT COUNT(*) as unread_count
        FROM message 
        WHERE (account = '$pc_account' AND send_account = '$account' AND is_read = 0) 
           OR (account = '$account' AND send_account = '$pc_account' AND is_read = 0)";

    $result_check = mysqli_query($link, $sql_check_unread);
    $row = mysqli_fetch_assoc($result_check);

    // 如果有未讀消息，則更新為已讀
    if ($row['unread_count'] > 0) {
        $sql_update = "
            UPDATE message 
            SET is_read = 1 
            WHERE (account = '$pc_account' AND send_account = '$account' AND is_read = 0) 
               OR (account = '$account' AND send_account = '$pc_account' AND is_read = 0)";
        mysqli_query($link, $sql_update);
    }

    // 查詢更新後的聊天記錄，返回最新聊天紀錄給前端
    $sql_messages = "
        SELECT * FROM message
        WHERE (account = '$pc_account' AND send_account = '$account') 
           OR (account = '$account' AND send_account = '$pc_account')
        ORDER BY created_at DESC";

    $result_messages = mysqli_query($link, $sql_messages);
    $messages = [];
    while ($message = mysqli_fetch_assoc($result_messages)) {
        $messages[] = [
            'send_account' => $message['send_account'],
            'content' => htmlspecialchars($message['content']),
            'created_at' => $message['created_at'],
            'is_read' => $message['is_read']
        ];
    }

    // 回傳未讀數量和聊天記錄
    echo json_encode([
        'unread_count' => $row['unread_count'],
        'messages' => $messages
    ]);
    exit();
}


?>


<!DOCTYPE html>
<html lang="en">

<head>
    <?php include "head.php"; ?>
    <style>
        h2 {
            font-size: 36px;
            color: #333333;
            text-align: center;
        }

        .info-item {
            margin: 5px 0;
        }

        .icon {
            margin-right: 8px;
            font-size: 20px;
        }

        .info {
            font-size: 15px;
        }

        .unread-count {
            display: inline-block;
            background-color: #ff4d4d;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            text-align: center;
            line-height: 20px;
            font-size: 12px;
            margin-left: 10px;
            margin-top: -20px;
        }

        .message-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>
</head>

<body>
    <?php include "nav.php"; ?>
    <div class="chat-container d-flex" id="chatContainer" style="flex: 1;">
        <!-- 聊天列表 -->
        <div class="chat-list">
            <div class="p-3">
                <h5 style="color:#652126;">所有訊息</h5>
            </div>
            <!-- 查詢 -->
            <form id="search-form" class="s1-search">
                <div class="input-container">
                    <input type="text" name="search" placeholder="Search" required>
                    <button type="submit" class="s1-search-button">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                    <?php if ($user_type === 'hospital'): ?>
                        <button type="button" class="add-btn" data-bs-toggle="modal" data-bs-target="#addModal">
                            <i class="fa-solid fa-user-plus"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </form>
            <!-- 好友區 -->
            <div class="n1-contacts">
                <?php if (!empty($accounts) && !empty($names) && count($accounts) === count($names)): ?>
                    <?php foreach ($accounts as $index => $pc_account): ?>
                        <?php
                        $clean_account = mysqli_real_escape_string($link, $account);
                        $clean_pc_account = mysqli_real_escape_string($link, $pc_account);

                        // 查詢最近一條消息
                        $sql_recent_message = "SELECT content, created_at
                                   FROM message 
                                   WHERE (account = '$clean_pc_account' AND send_account = '$clean_account') 
                                   OR (account = '$clean_account' AND send_account = '$clean_pc_account')
                                   ORDER BY created_at DESC
                                   LIMIT 1";
                        $result_recent_message = mysqli_query($link, $sql_recent_message);
                        $recent_message = $result_recent_message ? mysqli_fetch_assoc($result_recent_message) : null;
                        $name = htmlspecialchars($names[$index]);

                        // 查詢未讀消息的數量
                        $sql_unread_count = "SELECT COUNT(*) as unread_count
                                 FROM message
                                 WHERE (account = '$clean_account' AND send_account = '$clean_pc_account' AND is_read = 0)";
                        $result_unread_count = mysqli_query($link, $sql_unread_count);
                        $unread_count = $result_unread_count ? mysqli_fetch_assoc($result_unread_count)['unread_count'] : 0;
                        ?>
                        <!-- 顯示好友資訊 -->
                        <div class="custom-box"
                            onclick="showAccount('<?php echo htmlspecialchars($pc_account); ?>', '<?php echo $name; ?>')">
                            <div class="account-time-row">
                                <p class="account"><?php echo $name . ' (' . htmlspecialchars($pc_account) . ')'; ?></p>
                                <p class="time1">
                                    <?php
                                    if (!empty($recent_message)) {
                                        $time = date('H:i', strtotime($recent_message['created_at']));
                                        $hours = date('H', strtotime($recent_message['created_at']));
                                        $period = ($hours < 12) ? '上午' : (($hours < 18) ? '下午' : '晚上');
                                        echo $period . ' ' . $time; // 顯示時間
                                    } else {
                                        echo '無消息'; // 如果沒有消息
                                    }
                                    ?>
                                </p>
                            </div>
                            <div class="message-row">
                                <p class="p2">
                                    <?php echo !empty($recent_message) ? htmlspecialchars($recent_message['content']) : '無內容'; ?>
                                </p>
                                <?php if ($unread_count > 0): ?>
                                    <span class="unread-count"><?php echo $unread_count; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="custom-box">
                        <p>找不到任何帳號。</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <!-- 聊天區 -->
        <div class="chat-area" id="mainContent" style="flex: 1; transition: width 0.3s;">
            <!-- 顯示姓名跟帳號 -->
            <div class="chat-header"
                style="display: flex; justify-content: space-between; align-items: center; padding: 5px;">
                <div id="accountDisplay" style="font-size: 20px;">
                    <?php echo htmlspecialchars($name) . ' (' . htmlspecialchars($accountDisplay) . ')'; ?>
                </div>
                <button id="addPageButton"
                    style="border: none; background: none; cursor: pointer; font-size: 20px; margin-right: 10px;"
                    onclick="toggleProfilePage();">
                    <i class="fa-solid fa-ellipsis-vertical"></i>
                </button>
            </div>
            <!-- 訊息顯示區 -->
            <div class="chat-messages" id="chatMessages" style="flex: 1; overflow-y: auto;"></div>
            <!-- 輸入訊息區(只有醫療機構可以上傳照片) -->
            <div class="chat-input">
                <?php if ($user_type === 'hospital'): ?>
                    <button type="button" class="p3-btn" data-bs-toggle="modal" data-bs-target="#imageModal">
                        <i class="fa-regular fa-image"></i>
                    </button>
                <?php endif; ?>
                <div class="input-container">
                    <textarea id="chat-input2" placeholder="請輸入訊息" rows="1"
                        onkeydown="handleKeyPress(event)"></textarea>
                    <button type="button" class="send-btn" onclick="sendMessage()">
                        <i class="fa-solid fa-location-arrow"></i>
                    </button>
                </div>
            </div>
        </div>
        <!-- 該使用者帳號的個人資料 -->
        <div id="newPage"
            style="display: none; width: 50%; background: white; border-left: 1px solid #ccc; padding: 10px; transition: width 0.3s;">
            <h2>個人資料</h2>
            <div id="infoContainer" class="info">
                <!-- 互動個人資料將在顯示在這裡 -->
            </div>
        </div>

        <!-- 新增綁定的互動視窗 -->
        <div class="modal fade s3-modal" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="" method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addModalLabel">患者/照護者帳號</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="text" class="form-control" id="modalPatient" name="modalPatient"
                                placeholder="輸入患者/照護者帳號">
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-secondary" value="add" name="action">發送通知</button>
                            <button type="button" class="btn btn-primary"
                                onclick="clearInput('modalPatient')">清除</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- 上傳照片的互動視窗 -->
        <div class="modal fade s3-modal" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form id="imageForm" action="chat.php" method="POST" enctype="multipart/form-data">
                        <div class="modal-header">
                            <h5 class="modal-title" id="imageModalLabel">上傳圖片</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <!-- 隱藏選擇帳號 -->
                            <input type="hidden" name="selectedAccount" id="hiddenSelectedAccount">

                            <!-- 圖片選擇按鈕 -->
                            <input type="file" id="imageInput" name="images[]" style="display: none;" accept="image/*"
                                multiple onchange="handleImageUpload(event)">
                            <button type="button" class="btn btn-secondary mb-2"
                                onclick="document.getElementById('imageInput').click();">選擇圖片</button>

                            <!-- 圖片預覽區域 -->
                            <div id="uploadedImages" class="d-flex flex-wrap"></div>
                            <div id="errorMessage" class="text-danger mt-2" style="display: none;"></div>
                        </div>
                        <div class="modal-footer">
                            <!-- 提交按鈕 -->
                            <button type="submit" class="btn btn-primary">發送</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>


        <script>
            let selectedAccount = '';
            let selectedName = '';

            window.onload = function () {
                selectedAccount = localStorage.getItem('selectedAccount') || '';
                selectedName = localStorage.getItem('selectedName') || '';
                updateAccountDisplay();
                if (selectedAccount) {
                    loadChatHistory(selectedAccount);
                } else {
                    console.error('No selected account found in localStorage.');
                }
            };

            // 更新帳號訊息
            function updateAccountDisplay() {
                const accountDisplay = document.getElementById('accountDisplay');
                accountDisplay.innerText = selectedName ? `${selectedName} (${selectedAccount})` : selectedAccount;
            }

            // 加載聊天歷史紀錄
            function loadChatHistory(account) {
                fetch(`?selectedAccount=${encodeURIComponent(account)}`)
                    .then(response => response.text())
                    .then(data => {
                        const chatMessages = document.querySelector('.chat-messages');
                        chatMessages.innerHTML = data; // 更新聊天紀錄
                    })
                    .catch(error => {
                        console.error('加载聊天历史时发生错误:', error);
                    });
            }

            // 顯示帳號並加載聊天紀錄
            function showAccount(account, name) {
                selectedAccount = account;
                selectedName = name;
                localStorage.setItem('selectedAccount', account);
                localStorage.setItem('selectedName', name);
                updateAccountDisplay(); // 更新帳號顯示信息
                loadChatHistory(account); // 加載聊天歷史
                document.getElementById('hiddenSelectedAccount').value = account;
                updateReadStatus(account);  // 更新已讀狀態
            }

            // 更新消息狀態為已讀
            function updateReadStatus(account) {
                const xhr = new XMLHttpRequest();
                xhr.open("POST", "chat.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

                xhr.onload = function () {
                    if (xhr.status === 200) {
                        const response = JSON.parse(xhr.responseText);
                        updateUnreadCount(response.unread_count); // 更新未讀數量
                        renderMessages(response.messages);
                    }
                };

                xhr.onerror = function () {
                    console.error("發生錯誤，無法更新狀態");
                };

                xhr.send("pc_account=" + encodeURIComponent(account));
            }


            // 處理圖片選擇後的預覽
            function handleImageUpload(event) {
                const files = event.target.files;
                const previewContainer = document.getElementById('uploadedImages');
                previewContainer.innerHTML = '';

                Array.from(files).forEach(file => {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.classList.add('img-thumbnail', 'mr-2', 'mb-2');
                        img.style.width = '100px';
                        previewContainer.appendChild(img);
                    };
                    reader.readAsDataURL(file);
                });
            }

            // 提交表單前的驗證，確保 selectedAccount 存在
            document.getElementById('imageForm').addEventListener('submit', function (event) {
                const selectedAccount = document.getElementById('hiddenSelectedAccount').value;
                if (!selectedAccount) {
                    event.preventDefault();
                    document.getElementById('errorMessage').innerText = '請選擇一個帳號來發送圖片。';
                    document.getElementById('errorMessage').style.display = 'block';
                }
            });

            // 上傳圖片
            function uploadImages() {
                const imageInput = document.getElementById('imageInput');
                const files = imageInput.files;

                if (files.length === 0) {
                    alert('請選擇圖片。');
                    return;
                }

                // 從 localStorage 獲取 selectedAccount
                const selectedAccount = localStorage.getItem('selectedAccount');
                if (!selectedAccount) {
                    alert('沒有選擇接收帳號');
                    return;
                }

                const formData = new FormData();
                formData.append('selectedAccount', selectedAccount);
                Array.from(files).forEach(file => {
                    formData.append('images[]', file);
                });

                fetch('chat.php', {
                    method: 'POST',
                    body: formData,
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'error') {
                            alert(`上傳失敗： ${data.message}`);
                            return;
                        }

                        // 清除選擇的圖片
                        imageInput.value = '';
                        document.getElementById('uploadedImages').innerHTML = '';  // 清空預覽
                        loadChatHistory(selectedAccount);  // 更新聊天紀錄
                    })
                    .catch(error => {
                        console.error('上傳圖片時發生錯誤:', error);
                        alert('上傳失敗，請稍後再試。');
                    });
            }

            // 清空圖片預覽
            function clearImagePreview() {
                const previewContainer = document.getElementById('uploadedImages');
                previewContainer.innerHTML = '';
                document.getElementById('imageInput').value = '';
            }

            // 處理 Enter 按下事件發送訊息
            function handleKeyPress(event) {
                if (event.key === 'Enter' && !event.shiftKey) {
                    event.preventDefault();
                    sendMessage();
                }
            }

            // 發送消息
            function sendMessage() {
                const selectedAccount = localStorage.getItem('selectedAccount'); // 使用 localStorage 而非全局變量
                if (!selectedAccount) {
                    alert('請選擇一個帳號。');
                    return;
                }

                const messageInput = document.getElementById('chat-input2');
                const message = messageInput.value.trim();

                if (!message) {
                    alert('請輸入訊息。');
                    return;
                }

                const formData = new FormData();
                formData.append('selectedAccount', selectedAccount);
                formData.append('message', message);

                fetch('chat.php', {
                    method: 'POST',
                    body: formData,
                })
                    .then(response => response.text())
                    .then(data => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(data, 'text/html');
                        const alerts = doc.querySelectorAll('script');
                        alerts.forEach(script => {
                            const scriptContent = script.innerText;
                            if (scriptContent.startsWith('alert')) {
                                const message = scriptContent.substring(scriptContent.indexOf('(') + 1, scriptContent.lastIndexOf(')')).replace(/['"]/g, '');
                                alert(message);
                            }
                        });

                        messageInput.value = '';
                        loadChatHistory(selectedAccount);
                    })
                    .catch(error => {
                        console.error('發送消息時發生錯誤:', error);
                        alert('發送失敗，請稍後再試。');
                    });
            }

            // 打开互動視窗時，設置正確的 selectedAccount
            $('#imageModal').on('show.bs.modal', function () {
                const selectedAccount = localStorage.getItem('selectedAccount');
                if (selectedAccount) {
                    document.getElementById('hiddenSelectedAccount').value = selectedAccount;
                }
            });

            //個人資料顯示
            function openImage(src) {
                // 創建一個全屏圖片顯示的 div
                const overlay = document.createElement('div');
                overlay.style.position = 'fixed';
                overlay.style.top = '0';
                overlay.style.left = '0';
                overlay.style.width = '100%';
                overlay.style.height = '100%';
                overlay.style.backgroundColor = 'rgba(0, 0, 0, 0.8)';
                overlay.style.display = 'flex';
                overlay.style.justifyContent = 'center';
                overlay.style.alignItems = 'center';
                overlay.style.zIndex = '1000';

                const img = document.createElement('img');
                img.src = src;
                img.style.maxWidth = '90%';
                img.style.maxHeight = '90%';
                img.style.borderRadius = '8px';

                overlay.appendChild(img);
                document.body.appendChild(overlay);

                // 點擊 overlay 時關閉圖片
                overlay.onclick = function () {
                    document.body.removeChild(overlay);
                };
            }
            function toggleProfilePage() {
                const newPage = document.getElementById("newPage");
                const mainContent = document.getElementById("mainContent");
                const chatInput = document.getElementById("chat-input2");
                const sendButton = document.querySelector(".send-btn");

                if (newPage.style.display === "none" || newPage.style.display === "") {
                    newPage.style.display = "block";
                    mainContent.style.width = "60%";
                    chatInput.style.minWidth = "680px";
                    sendButton.style.display = "inline-block";

                    // 確保 selectedAccount 存在
                    const selectedAccount = localStorage.getItem('selectedAccount');
                    if (selectedAccount) {
                        sendAccountToServer(selectedAccount);
                    } else {
                        console.error('No selected account found in localStorage');
                    }
                } else {
                    newPage.style.display = "none";
                    mainContent.style.width = "90%";
                    chatInput.style.minWidth = "900px";
                    sendButton.style.display = "inline-block";
                }
            }

            //發送帳號至php
            function sendAccountToServer(selectedAccount) {
                fetch('chat.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({ showAccount: selectedAccount }),
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.text();
                    })
                    .then(data => {
                        const infoContainer = document.getElementById("infoContainer");
                        infoContainer.innerHTML = ''; // 清空舊內容
                        infoContainer.innerHTML = data; // 將新的數據插入到 infoContainer 中
                    })
                    .catch(error => {
                        console.error('There was a problem with the fetch operation:', error);
                    });
            }
            //刪除帳號綁定
            document.addEventListener('DOMContentLoaded', function () {
                document.addEventListener('click', function (event) {
                    if (event.target.classList.contains('deleteButton')) {
                        console.log('Button clicked!');
                        const deleteAccount = localStorage.getItem('selectedAccount') || '';
                        const selectedName = localStorage.getItem('selectedName') || '';
                        console.log('Delete account:', deleteAccount);

                        if (confirm('確定要刪除該關聯帳號嗎？')) {
                            const infoContainer = event.target.closest('.info');
                            infoContainer.remove();

                            const params = new URLSearchParams();
                            params.append('deleteAccount', deleteAccount);

                            const loadingMessage = document.createElement('div');
                            loadingMessage.textContent = '正在刪除...';
                            document.body.appendChild(loadingMessage);

                            fetch('chat.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: params.toString()
                            })
                                .then(response => response.text())
                                .then(message => {
                                    alert(message);
                                    if (message.includes("刪除成功")) {
                                        // 清空 localStorage 中的帳號和名稱
                                        localStorage.removeItem('selectedAccount');
                                        localStorage.removeItem('selectedName');

                                        // 清空顯示的帳號和名稱
                                        refreshUserAccountDisplay(); // 調用更新函數

                                        // 刪除成功後刷新頁面或更新用戶列表
                                        location.reload(); // 或者使用 updateUserList();
                                    } else {
                                        document.body.appendChild(infoContainer);
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    document.body.appendChild(infoContainer);
                                })
                                .finally(() => {
                                    document.body.removeChild(loadingMessage);
                                });
                        }
                    }
                });
            });

            //查詢顯示
            function refreshUserAccountDisplay() {
                const userAccountDisplay = document.getElementById('accountDisplay');
                // 清空顯示內容
                userAccountDisplay.innerText = '';
            }
            document.getElementById('search-form').addEventListener('submit', function (e) {
                e.preventDefault(); // 防止表單默認提交

                var formData = new FormData(this); // 獲取表單數據

                fetch('chat.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.text())
                    .then(data => {
                        document.querySelector('.n1-contacts').innerHTML = data; // 更新查詢結果
                    })
                    .catch(error => {
                        console.error('錯誤:', error);
                        alert('查詢過程中發生錯誤');
                    });
            });


            function loadMessages(account) {
                const xhr = new XMLHttpRequest();
                xhr.open("POST", "chat.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

                xhr.onload = function () {
                    if (xhr.status === 200) {
                        const response = JSON.parse(xhr.responseText);

                        // 更新未讀數量
                        const unreadCount = response.unread_count;
                        const unreadCountElement = document.querySelector('.unread-count');
                        if (unreadCountElement) {
                            if (unreadCount > 0) {
                                unreadCountElement.textContent = unreadCount;
                                unreadCountElement.style.display = 'inline';
                            } else {
                                unreadCountElement.style.display = 'none';
                            }
                        }

                        // 更新聊天內容
                        const customBox = document.getElementById("custom-box");
                        if (customBox) {
                            customBox.innerHTML = '';  // 清空現有的聊天記錄

                            // 重新渲染所有聊天訊息
                            response.messages.forEach(message => {
                                const messageElement = document.createElement('div');
                                messageElement.classList.add('message');
                                if (message.is_read == 1) {
                                    messageElement.classList.add('read');
                                } else {
                                    messageElement.classList.add('unread');
                                }
                                messageElement.innerHTML = `
                        <p><strong>${message.send_account}:</strong> ${message.content}</p>
                        <span class="timestamp">${message.created_at}</span>
                    `;
                                customBox.appendChild(messageElement);
                            });
                        }
                    }
                };

                xhr.onerror = function () {
                    console.error("發生錯誤，無法載入訊息");
                };

                xhr.send("pc_account=" + encodeURIComponent(account));  // 傳送目標帳號
            }

            // 更新訊息狀態為已讀
            function updateReadStatus(account) {
                const xhr = new XMLHttpRequest();
                xhr.open("POST", "chat.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

                xhr.onload = function () {
                    if (xhr.status === 200) {
                        const response = JSON.parse(xhr.responseText);

                        // 更新未讀數量
                        const unreadCount = response.unread_count;
                        const unreadCountElement = document.querySelector('.unread-count');
                        if (unreadCountElement) {
                            if (unreadCount > 0) {
                                unreadCountElement.textContent = unreadCount;
                                unreadCountElement.style.display = 'inline';
                            } else {
                                unreadCountElement.style.display = 'none';
                            }
                        }

                        // 更新訊息顯示區塊
                        const customBox = document.getElementById("custom-box");
                        if (customBox) {
                            customBox.innerHTML = '';  // 清空現有的聊天記錄

                            // 重新渲染所有聊天訊息
                            response.messages.forEach(message => {
                                const messageElement = document.createElement('div');
                                messageElement.classList.add('message');
                                if (message.is_read == 1) {
                                    messageElement.classList.add('read');
                                } else {
                                    messageElement.classList.add('unread');
                                }
                                messageElement.innerHTML = `
                        <p><strong>${message.send_account}:</strong> ${message.content}</p>
                        <span class="timestamp">${message.created_at}</span>
                    `;
                                customBox.appendChild(messageElement);
                            });
                        }
                    }
                };
                xhr.onerror = function () {
                    console.error("發生錯誤，無法更新狀態");
                };

                xhr.send("pc_account=" + encodeURIComponent(account));  // 傳送目標帳號
            }

        </script>
    </div>
</body>

</html>