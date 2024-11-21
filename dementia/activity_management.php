<?php
session_start();
include "db.php";

// 檢查用戶是否已登錄
$account = isset($_SESSION['account']) ? $_SESSION['account'] : '';
if (empty($account)) {
    header("Location: login.php");
    exit();
}

// 獲取 institution_id
// 假设 $account 是传入的用户账号

// 先查找用户的类型（管理员或者医疗机构）
$sql_user_type = "SELECT user_type FROM user WHERE account = '" . mysqli_real_escape_string($link, $account) . "'";
$result_user_type = mysqli_query($link, $sql_user_type);

// 检查用户类型
if ($row = mysqli_fetch_assoc($result_user_type)) {
    $user_type = $row['user_type'];

    // 如果是管理员（假设管理员的 user_type 是 'admin'）
    if ($user_type == 'admin') {
        $institution_id = '12345678';
    } else {
        // 否则查询医院的 institution_id
        $sql_institution_id = "SELECT institution_id FROM hospital WHERE account = '" . mysqli_real_escape_string($link, $account) . "'";
        $result_institution_id = mysqli_query($link, $sql_institution_id);

        if ($row = mysqli_fetch_assoc($result_institution_id)) {
            $institution_id = $row['institution_id'];
        } else {
            die('找不到機構。');
        }
    }
} else {
    die('找不到該用戶。');
}



$activities = [];
$sql_all_activities = "SELECT * FROM activity WHERE institution_id = '$institution_id'";
$result_all_activities = mysqli_query($link, $sql_all_activities);

// 獲取所有活動
while ($row = mysqli_fetch_assoc($result_all_activities)) {
    foreach ($row as $key => $value) {
        if (!empty($value) && mb_strlen($value, 'UTF-8') > 50) {
            $row[$key] = mb_substr($value, 0, 50, 'UTF-8') . '...';
        }
    }

    $description = htmlspecialchars($row['description'], ENT_QUOTES, 'UTF-8');
    $short_description = mb_strlen($description, 'UTF-8') > 15 ? mb_substr($description, 0, 15, 'UTF-8') . '...' : $description;
    $row['short_description'] = $short_description;

    $activities[] = $row;
}

// 新增活動
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    // 獲取並清理資料
    $activityName = mysqli_real_escape_string($link, $_POST['activity_name']);
    $description = mysqli_real_escape_string($link, $_POST['description']);
    $startTime = mysqli_real_escape_string($link, $_POST['start_time']);
    $endTime = mysqli_real_escape_string($link, $_POST['end_time']);
    $location = mysqli_real_escape_string($link, $_POST['location']);
    $address = mysqli_real_escape_string($link, $_POST['address']);
    $registrationDeadline = mysqli_real_escape_string($link, $_POST['registration_deadline']);
    $maxParticipants = (int) $_POST['max_participants']; // 確保為整數
    $contactPerson = mysqli_real_escape_string($link, $_POST['contact_person']);
    $contactPhone = mysqli_real_escape_string($link, $_POST['contact_phone']);
    $status = mysqli_real_escape_string($link, $_POST['status']);
    $activityImageName = null; // 初始化活動海報檔名

    // 檢查是否已存在相同活動
    $sql_check_activity = "SELECT activity_id FROM activity WHERE activity_name = '$activityName'";
    $result = mysqli_query($link, $sql_check_activity);

    if (!$result) {
        die("SQL 查詢失敗: " . mysqli_error($link));
    }

    $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
    if ($row) {
        $activityId = $row['activity_id']; // 使用現有的活動ID
    } else {
        // 準備插入活動資料的 SQL 語句
        $sql_insert_activity = "INSERT INTO activity (activity_name, description, start_time, end_time, location, address, registration_deadline, max_participants, contact_person, contact_phone, institution_id, status) 
                                VALUES ('$activityName', '$description', '$startTime', '$endTime', '$location', '$address', '$registrationDeadline', $maxParticipants, '$contactPerson', '$contactPhone', '$institution_id', '$status')";

        if (!mysqli_query($link, $sql_insert_activity)) {
            die("執行失敗: " . mysqli_error($link));
        }
        $activityId = mysqli_insert_id($link); // 獲取新插入的活動ID
    }

    // 處理上傳的活動照片
    if (isset($_FILES['activity_photo']) && $_FILES['activity_photo']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['activity_photo']['tmp_name'];
        $fileName = $_FILES['activity_photo']['name'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        // 指定的上傳目錄
        $uploadFileDir = './activity/';
        $newFileName = uniqid() . '.' . $fileExtension;
        $dest_path = $uploadFileDir . $newFileName;

        // 檢查文件類型
        $allowedfileExtensions = array('jpg', 'gif', 'png', 'jpeg');
        if (in_array($fileExtension, $allowedfileExtensions)) {
            // 移動上傳的文件
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                // 更新活動表中的海報檔名
                $activityImageName = $newFileName;
                $sql_update_image = "UPDATE activity SET activity_image_name = '$activityImageName' WHERE activity_id = $activityId";

                if (!mysqli_query($link, $sql_update_image)) {
                    die("執行圖片更新失敗: " . mysqli_error($link));
                }
            } else {
                die("移動文件失敗");
            }
        } else {
            die("不允許的文件類型");
        }
    }

    // 處理問題和選項
    $questionsText = isset($_POST['questionText']) ? $_POST['questionText'] : [];
    $questionsType = isset($_POST['questionType']) ? $_POST['questionType'] : [];
    $optionsList = isset($_POST['options']) ? $_POST['options'] : [];

    foreach ($questionsText as $index => $questionText) {
        if (empty($questionText)) {
            continue;
        }

        // 插入問題資料
        $sql_question = "INSERT INTO questions (questions_text, activity_id) 
                         VALUES ('" . mysqli_real_escape_string($link, $questionText) . "', $activityId)";

        if (!mysqli_query($link, $sql_question)) {
            die("執行問題插入失敗: " . mysqli_error($link));
        }
        $questionId = mysqli_insert_id($link);

        // 確保使用正確的問題類型
        $questionType = mysqli_real_escape_string($link, $questionsType[$index]);

        // 處理簡答問題
        if ($questionType === 'text') {
            $sql_options = "INSERT INTO options (questions_id, options_type, options_text, activity_id) 
                            VALUES ($questionId, '$questionType', NULL, $activityId)";
            if (!mysqli_query($link, $sql_options)) {
                die("執行選項插入失敗: " . mysqli_error($link));
            }
        } elseif ($questionType === 'radio' || $questionType === 'checkbox') {
            if (isset($optionsList[$index])) {
                foreach ($optionsList[$index] as $option) {
                    if (!empty($option)) {
                        $sql_options = "INSERT INTO options (questions_id, options_type, options_text, activity_id) 
                                       VALUES ($questionId, '$questionType', '" . mysqli_real_escape_string($link, $option) . "', $activityId)";
                        if (!mysqli_query($link, $sql_options)) {
                            die("執行選項插入失敗: " . mysqli_error($link));
                        }
                    }
                }
            }
        }
    }

    // 提交成功訊息並導向
    echo "rt('活動新增成功'); window.location.href = 'activity_management.php';</script>";
}

// 處理編輯請求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $activity_id = intval($_POST['activity_id']);
    $activity_name = mysqli_real_escape_string($link, $_POST['activity_name']);
    $description = mysqli_real_escape_string($link, $_POST['description']);
    $start_time = mysqli_real_escape_string($link, $_POST['start_time']);
    $end_time = mysqli_real_escape_string($link, $_POST['end_time']);
    $location = mysqli_real_escape_string($link, $_POST['location']);
    $address = mysqli_real_escape_string($link, $_POST['address']);
    $registration_deadline = mysqli_real_escape_string($link, $_POST['registration_deadline']);
    $max_participants = intval($_POST['max_participants']);
    $contact_person = mysqli_real_escape_string($link, $_POST['contact_person']);
    $contact_phone = mysqli_real_escape_string($link, $_POST['contact_phone']);
    $status = mysqli_real_escape_string($link, $_POST['status']);

    // 更新活動信息
    $sql_update = "UPDATE activity SET activity_name = '$activity_name', description = '$description', 
                   start_time = '$start_time', end_time = '$end_time', location = '$location', 
                   address = '$address', registration_deadline = '$registration_deadline', 
                   max_participants = '$max_participants', contact_person = '$contact_person', 
                   contact_phone = '$contact_phone', status = '$status' WHERE activity_id = '$activity_id'";

    // 檢查是否有上傳新的海報圖片
    if (isset($_FILES['edit_activity_photo']) && $_FILES['edit_activity_photo']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['edit_activity_photo']['tmp_name'];
        $fileName = $_FILES['edit_activity_photo']['name'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        // 指定的上船目錄
        $uploadFileDir = './activity/';
        $newFileName = uniqid() . '.' . $fileExtension; // 使用唯一ID作為文件名
        $dest_path = $uploadFileDir . $newFileName;

        // 檢查文件類型
        $allowedfileExtensions = array('jpg', 'gif', 'png', 'jpeg');
        if (in_array($fileExtension, $allowedfileExtensions)) {
            // 移動上傳的文件
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                // 更新活動的海報圖片路徑
                $sql_update_image = "UPDATE activity SET activity_image_name = '$newFileName' WHERE activity_id = '$activity_id'";
                mysqli_query($link, $sql_update_image);
            } else {
                die("移動文件失敗");
            }
        } else {
            die("不允許的文件類型");
        }
    }

    // 執行活動信息的更新
    if (mysqli_query($link, $sql_update)) {
        echo json_encode(['success' => true]); // 返回成功信息
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($link)]); // 返回錯誤信息
    }
    exit();
}

// 獲取活動詳细信息的處理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'fetch') {
    $activity_id = intval($_POST['activity_id']);
    $result = mysqli_query($link, "SELECT * FROM activity WHERE activity_id = '$activity_id'");
    $activity = mysqli_fetch_assoc($result);

    if ($activity) {
        echo json_encode(['success' => true, 'activity' => $activity]);
    } else {
        echo json_encode(['success' => false, 'error' => '活動未找到']);
    }
    exit();
}



// 刪除活動
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $activity_id = intval($_POST['activity_id']);
    $sql_delete = "DELETE FROM activity WHERE activity_id = '$activity_id' AND institution_id = '$institution_id'";

    if (mysqli_query($link, $sql_delete)) {
        echo "<script>alert('活動刪除成功'); window.location.href = 'activity_management.php';</script>";
    } else {
        echo "<script>alert('刪除失敗: " . mysqli_error($link) . "');</script>";
    }
    exit();
}

// 搜尋活動
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'search') {
    $search_input = mysqli_real_escape_string($link, trim(isset($_POST['search_input']) ? $_POST['search_input'] : ''));
    $start_time = mysqli_real_escape_string($link, isset($_POST['start_time']) ? $_POST['start_time'] : '');
    $end_time = mysqli_real_escape_string($link, isset($_POST['end_time']) ? $_POST['end_time'] : '');

    $sql_search = "SELECT * FROM activity WHERE institution_id = '$institution_id'";

    if (!empty($search_input)) {
        $sql_search .= " AND (activity_name LIKE '%$search_input%' OR description LIKE '%$search_input%' OR location LIKE '%$search_input%' OR registration_deadline LIKE '%$search_input%' OR max_participants LIKE '%$search_input%' OR contact_person LIKE '%$search_input%' OR contact_phone LIKE '%$search_input%' OR status LIKE '%$search_input%')";
    }

    if (!empty($start_time) && !empty($end_time)) {
        $sql_search .= " AND ((start_time BETWEEN '$start_time' AND '$end_time') OR (end_time BETWEEN '$start_time' AND '$end_time') OR (start_time <= '$end_time' AND end_time >= '$start_time'))";
    } elseif (!empty($start_time)) {
        $sql_search .= " AND (start_time >= '$start_time' OR registration_deadline >= '$start_time')";
    } elseif (!empty($end_time)) {
        $sql_search .= " AND (end_time <= '$end_time' OR registration_deadline <= '$end_time')";
    }

    $result_search = mysqli_query($link, $sql_search);
    $activities = [];
    while ($row = mysqli_fetch_assoc($result_search)) {
        foreach ($row as $key => $value) {
            if (!empty($value) && mb_strlen($value, 'UTF-8') > 50) {
                $row[$key] = mb_substr($value, 0, 50, 'UTF-8') . '...';
            }
        }

        $description = htmlspecialchars($row['description'], ENT_QUOTES, 'UTF-8');
        $short_description = mb_strlen($description, 'UTF-8') > 15 ? mb_substr($description, 0, 15, 'UTF-8') . '...' : $description;
        $row['short_description'] = $short_description;

        $activities[] = $row;
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include "head.php"; ?>

</head>

<body>
    <?php include "nav.php"; ?>
    <div class="s4-container">
        <div class="filter-section">
            <h2 style="text-align: center;">活動管理</h2>
            <form action="" method="POST" class="input-group">
                <div class="mb-3" style="width:100%">
                    <input type="text" name="search_input" placeholder="請輸入關鍵字" value="">
                </div>
                <div class="mb-3" style="width:100%">
                    <input type="date" name="start_time" placeholder="開始日期" value="">
                </div>
                <div class="mb-3" style="width:100%">
                    <input type="date" name="end_time" placeholder="結束日期" value="">
                </div>
                <div class="mb-3" style="width:100%">
                    <button type="submit" class="btn" name="action" value="search">
                        <i class="fa fa-search"></i> 搜尋
                    </button>
                </div>
            </form>
            <div class="mb-3" style="width:100%">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addActivityModal">
                    <i class="fa-solid fa-plus"></i> 新增活動
                </button>
            </div>
        </div>
        <div class="card-container">
            <?php if (empty($activities)): ?>
            <?php else: ?>
                <?php
                // 定義顏色對應
                $colors = ['#45aaf2', '#fd79a8', '#A594F9', '#f39c12'];
                foreach ($activities as $index => $activity): ?>
                    <div class="s3-card" style="border-left: 5px solid <?php echo $colors[$index % count($colors)]; ?>;">
                        <div class="s3-card-left">
                            <div style="flex: 0 0 150px; margin-right: 15px;">
                                <img src="<?php echo htmlspecialchars('./activity/' . $activity['activity_image_name']); ?>"
                                    alt="<?php echo htmlspecialchars($activity['activity_name']); ?>"
                                    style="width: 100%; height: auto; border-radius: 5px;">
                            </div>
                            <div class="details">
                                <p style="font-size:20px;"><?php echo htmlspecialchars($activity['activity_name']); ?></p>
                                <p><strong>活動起訖:</strong> <?php echo htmlspecialchars($activity['start_time']); ?> ~
                                    <?php echo htmlspecialchars($activity['end_time']); ?>
                                </p>
                                <p><strong>地點:</strong> <?php echo htmlspecialchars($activity['location']); ?></p>
                                <p><strong>報名截止時間:</strong> <?php echo htmlspecialchars($activity['registration_deadline']); ?>
                                </p>
                                <p><strong>人數上限:</strong> <?php echo htmlspecialchars($activity['max_participants']); ?></p>
                                <p><strong>狀態:</strong> <?php echo htmlspecialchars($activity['status']); ?></p>
                            </div>
                        </div>
                        <div class="s3-card-right">
                            <button class="s5-btn-send" data-id="<?php echo htmlspecialchars($activity['activity_id']); ?>"
                                onclick="viewRegistration(this)">查看報名</button>
                            <button class="s5-btn-a edit-button"
                                data-id="<?php echo htmlspecialchars($activity['activity_id']); ?>" data-bs-toggle="modal"
                                data-bs-target="#editActivityModal">編輯</button>
                            <button class="s5-btn-custom delete-button"
                                data-id="<?php echo htmlspecialchars($activity['activity_id']); ?>">刪除</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>


        <!-- 新增活動的模態框 -->
        <div class="modal fade s3-modal" id="addActivityModal" tabindex="-1" aria-labelledby="addActivityModalLabel"
            aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog custom-modal-dialog modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addActivityModalLabel">新增活動</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="addactivityForm" method="POST" action="activity_management.php"
                        enctype="multipart/form-data">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="activity_name" class="form-label">活動名稱</label>
                                <input type="text" class="form-control" id="activity_name" name="activity_name"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">活動敘述</label>
                                <textarea class="form-control" id="description" name="description"
                                    style="height: 125px; overflow-y: auto;" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="start_time" class="form-label">開始時間</label>
                                <input type="datetime-local" class="form-control" id="start_time" name="start_time"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label for="end_time" class="form-label">結束時間</label>
                                <input type="datetime-local" class="form-control" id="end_time" name="end_time"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label for="location" class="form-label">地點</label>
                                <input type="text" class="form-control" id="location" name="location" required>
                            </div>
                            <div class="mb-3">
                                <label for="address" class="form-label">地址</label>
                                <input type="text" class="form-control" id="address" name="address" required>
                            </div>
                            <div class="mb-3">
                                <label for="registration_deadline" class="form-label">報名截止時間</label>
                                <input type="datetime-local" class="form-control" id="registration_deadline"
                                    name="registration_deadline" required>
                            </div>
                            <div class="mb-3">
                                <label for="max_participants" class="form-label">人數上限</label>
                                <input type="number" class="form-control" id="max_participants" name="max_participants"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label for="contact_person" class="form-label">聯絡人</label>
                                <input type="text" class="form-control" id="contact_person" name="contact_person"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label for="contact_phone" class="form-label">連絡電話</label>
                                <input type="text" class="form-control" id="contact_phone" name="contact_phone"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label for="status" class="form-label">狀態</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="開始報名">開始報名</option>
                                    <option value="報名截止">報名截止</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="activity_photo" class="form-label">活動海報</label>
                                <input type="file" class="form-control" id="activity_photo" name="activity_photo"
                                    accept="image/*" required onchange="previewAddImage(event)">
                                <img id="add_image_preview" src="#" alt="活動海報預覽"
                                    style="display: none; margin-top: 10px; max-width: 80%; height: auto;" />
                            </div>
                            <div class="mb-3">
                                <div id="activityInteractionContainer" class="p-4 bg-light border rounded">
                                    <h5 class="mb-3">活動報名問題設定</h5>
                                    <div class="d-flex align-items-center">
                                        <p class="p10 mb-0">以下可新增的活動報名問題</p>
                                        <button type="button" class="s3-button1" tabindex="0" role="button"
                                            data-bs-toggle="popover" data-bs-trigger="focus" title="新增活動報名問題表單說明"
                                            data-bs-html="true" data-bs-content="如需讓報名者填寫報名資料，請點選新增報名問題按鈕新增問題<br>
                                                                                 問題類型：<ul>
                                                                                <li>簡答 例:姓名、電話等</li>
                                                                                <li>單選 例:性別、葷素便當等</li>
                                                                                <li>多選 例：參加場次等</li>
                                                                                </ul>">
                                            <i class="fa-solid fa-circle-exclamation"></i></button>
                                    </div>
                                    <div id="questions"></div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <div class="d-flex justify-content-between w-100">
                                <button type="button" class="btn btn-primary  add-button" id="addQuestionButton"
                                    onclick="addQuestion()">新增報名問題</button>
                                <div>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                                    <button type="submit" class="btn btn-primary" form="addactivityForm" name="action"
                                        value="add">新增活動</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- 編輯活動的模態框 -->
        <div class="modal fade s3-modal" id="editActivityModal" tabindex="-1" aria-labelledby="editActivityModalLabel"
            aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog custom-modal-dialog modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editActivityModalLabel">編輯活動</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="editForm" action="" method="POST" enctype="multipart/form-data">
                        <div class="modal-body">
                            <input type="hidden" id="edit_activity_id" name="activity_id">
                            <div class="mb-3">
                                <label for="edit_activity_name" class="form-label">活動名稱</label>
                                <input type="text" class="form-control" id="edit_activity_name" name="activity_name"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_description" class="form-label">活動敘述</label>
                                <textarea class="form-control" id="edit_description" name="description"
                                    style="height:125px;overflow-y:auto;" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="edit_start_time" class="form-label">開始時間</label>
                                <input type="datetime-local" class="form-control" id="edit_start_time" name="start_time"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_end_time" class="form-label">結束時間</label>
                                <input type="datetime-local" class="form-control" id="edit_end_time" name="end_time"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_location" class="form-label">地點</label>
                                <input type="text" class="form-control" id="edit_location" name="location" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_address" class="form-label">地址</label>
                                <input type="text" class="form-control" id="edit_address" name="address" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_registration_deadline" class="form-label">報名截止時間</label>
                                <input type="datetime-local" class="form-control" id="edit_registration_deadline"
                                    name="registration_deadline" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_max_participants" class="form-label">人數上限</label>
                                <input type="number" class="form-control" id="edit_max_participants"
                                    name="max_participants" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_contact_person" class="form-label">聯絡人</label>
                                <input type="text" class="form-control" id="edit_contact_person" name="contact_person"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_contact_phone" class="form-label">連絡電話</label>
                                <input type="text" class="form-control" id="edit_contact_phone" name="contact_phone"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_status" class="form-label">狀態</label>
                                <select class="form-select" id="edit_status" name="status" required>
                                    <option value="開始報名">開始報名</option>
                                    <option value="報名截止">報名截止</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="edit_activity_photo" class="form-label">活動海報</label>
                                <input type="file" class="form-control" id="edit_activity_photo"
                                    name="edit_activity_photo" accept="image/*" onchange="previewEditImage(event)">
                                <img id="image_preview" src="#" alt="活動海報預覽"
                                    style="display: none; margin-top: 10px; max-width: 80%; height: auto;" />
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                            <button type="submit" class="btn btn-primary">更新活動</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function previewAddImage(event) {
            const imagePreview = document.getElementById('add_image_preview');
            const file = event.target.files[0];

            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    imagePreview.src = e.target.result; // 設置圖片預覽
                    imagePreview.style.display = 'block'; // 顯示圖片
                }
                reader.readAsDataURL(file); // 將文件讀取為 URL
            } else {
                imagePreview.src = '#'; // 清空預覽
                imagePreview.style.display = 'none'; // 隱藏圖片
            }
        }

        // 處理編輯按紐點擊
        document.querySelectorAll('.edit-button').forEach(button => {
            button.addEventListener('click', function () {
                const activityId = this.getAttribute('data-id');

                // 發起請求以獲取活動詳細訊息
                fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        'action': 'fetch',
                        'activity_id': activityId
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // 填充表單資訊
                            document.getElementById('edit_activity_id').value = data.activity.activity_id;
                            document.getElementById('edit_activity_name').value = data.activity.activity_name;
                            document.getElementById('edit_description').value = data.activity.description;
                            document.getElementById('edit_start_time').value = data.activity.start_time;
                            document.getElementById('edit_end_time').value = data.activity.end_time;
                            document.getElementById('edit_location').value = data.activity.location;
                            document.getElementById('edit_address').value = data.activity.address || '';
                            document.getElementById('edit_registration_deadline').value = data.activity.registration_deadline;
                            document.getElementById('edit_max_participants').value = data.activity.max_participants;
                            document.getElementById('edit_contact_person').value = data.activity.contact_person;
                            document.getElementById('edit_contact_phone').value = data.activity.contact_phone;
                            document.getElementById('edit_status').value = data.activity.status;

                            // 填充活動海報預覽
                            const activityImage = data.activity.activity_image_name;
                            if (activityImage) {
                                document.getElementById('image_preview').src = './activity/' + activityImage;
                                document.getElementById('image_preview').style.display = 'block';
                            } else {
                                document.getElementById('image_preview').style.display = 'none';
                            }

                        } else {
                            alert(data.error);
                        }
                    });
            });
        });

        // 提交編輯表單時，處理圖片上傳
        document.getElementById('editForm').addEventListener('submit', function (e) {
            e.preventDefault(); // 防止默任提交行為
            const formData = new FormData(this); // 使用 FormData 收集表單數據
            formData.append('action', 'edit'); // 添加操作類型

            fetch('', {
                method: 'POST',
                body: formData // 直接發送表單數據
            })
                .then(response => response.json())
                .then(data => {
                    // 關閉互動視窗
                    $('#editActivityModal').modal('hide');

                    // 延遲顯示成功消息
                    if (data.success) {
                        setTimeout(() => {
                            alert('活動更新成功');
                            window.location.reload(); // 刷新頁面以查看更新
                        }, 1000); // 500 毫秒的延遲時間
                    } else {
                        alert('更新失敗: ' + data.error);
                    }
                })
                .catch(error => console.error('Error:', error));
        });


        // 處理删除按紐點擊
        document.querySelectorAll('.delete-button').forEach(button => {
            button.addEventListener('click', function () {
                if (confirm('確定要刪除這個活動嗎？')) {
                    const activityId = this.getAttribute('data-id');

                    // 發送 POST 請求以刪除活動
                    fetch('', { // 發送到當前 PHP 檔案
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            'action': 'delete',
                            'activity_id': activityId
                        })
                    })
                        .then(response => response.text()) // 獲取文本響應
                        .then(responseText => {
                            // 創建一個臨時 DOM 元素來執行 PHP 輸出的 JavaScript
                            const tempDiv = document.createElement('div');
                            tempDiv.innerHTML = responseText;
                            document.body.appendChild(tempDiv);

                            // 查找和執行其中的腳本
                            const scriptTags = tempDiv.querySelectorAll('script');
                            scriptTags.forEach(script => {
                                eval(script.textContent); // 執行腳本
                            });

                            // 清理臨時 DOM 元素
                            document.body.removeChild(tempDiv);
                        })
                        .catch(error => console.error('Error:', error));
                }
            });
        });

        function toggleOptions(index) {
            const type = document.getElementById(`questionType${index}`).value;
            const optionsContainer = document.getElementById(`optionsContainer${index}`);
            const addOptionButton = optionsContainer.querySelector('button'); // 獲取新增選項的按鈕

            // 顯示或隱藏選項容器和按鈕
            if (type === 'radio' || type === 'checkbox') {
                optionsContainer.style.display = 'block';
                addOptionButton.style.display = 'inline-block'; // 显示按鈕
            } else {
                optionsContainer.style.display = 'none';
                addOptionButton.style.display = 'none'; // 隱藏按鈕
            }
        }

        function addQuestion() {
            const questionIndex = document.querySelectorAll('.question').length;
            const questionsDiv = document.getElementById('questions');
            const questionDiv = document.createElement('div');
            questionDiv.classList.add('question');
            questionDiv.innerHTML = `
    <h3>問題 ${questionIndex + 1}</h3>
    <select class="form-select" name="questionType[]" id="questionType${questionIndex}"
        onchange="toggleOptions(${questionIndex})">
        <option value="text">簡答</option>
        <option value="radio">單選</option>
        <option value="checkbox">多選</option>
    </select>
    <input type="text" class="form-control mb-3" name="questionText[]" placeholder="問題內容" required>
    <div id="optionsContainer${questionIndex}" style="display: none;">
        <h6>選項</h6>
        <div id="options${questionIndex}"></div>
        <button type="button" class="btn btn-secondary" onclick="addOption(${questionIndex})" style="display: none;">
            <i class="fa-solid fa-plus"></i>
        </button>
    </div>
    `;
            questionsDiv.appendChild(questionDiv);
        }

        function addOption(index) {
            const questionType = document.getElementById(`questionType${index}`).value;
            const optionDiv = document.createElement('div');

            // 根據問題類型生成不同的選項
            if (questionType === 'radio') {
                optionDiv.innerHTML = `
    <input type="radio" name="options[${index}]">
    <input type="text" class="form-control-1 mb-3" name="options[${index}][]" placeholder="選項內容">
    <button type="button" class="btn btn-delete" onclick="removeOption(this)">
        <i class="fa-solid fa-trash"></i>
    </button>
    `;
            } else if (questionType === 'checkbox') {
                optionDiv.innerHTML = `
    <input type="checkbox" name="options[${index}]">
    <input type="text" class="form-control-1 mb-3" name="options[${index}][]" placeholder="選項內容">
    <button type="button" class="btn btn-delete" onclick="removeOption(this)">
        <i class="fa-solid fa-trash"></i>
    </button>
    `;
            } else if (questionType === 'text') {
                optionDiv.innerHTML = `
    <input type="text" class="form-control-1 mb-3" name="options[${index}][]" placeholder="選項內容（可空）">
    <button type="button" class="btn btn-delete" onclick="removeOption(this)">
        <i class="fa-solid fa-trash"></i>
    </button>
    `;
            }

            document.getElementById(`options${index}`).appendChild(optionDiv);
        }

        function removeOption(button) {
            const optionDiv = button.parentElement;
            optionDiv.remove(); // 刪除選項
        }
        function viewRegistration(button) {
            var activityId = button.getAttribute('data-id');
            window.location.href = 'view_registration.php?activity_id=' + activityId;
        }
        document.addEventListener('DOMContentLoaded', function () {
            var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            popoverTriggerList.map(function (popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl);
            });
        });
    </script>



</body>

</html>