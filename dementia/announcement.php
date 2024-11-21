<?php
include "db.php";

// 檢查是否登入
$account = isset($_SESSION['account']) ? $_SESSION['account'] : '';
if (!$account) {
    header("Location: login.php");
    exit();
}

// 檢查帳號的 user_type 是否為 hospital
$sql_check_user_type = "SELECT user_type FROM user WHERE account = '$account'";
$result = mysqli_query($link, $sql_check_user_type);

if ($result) {
    // 取得查詢結果
    $user = mysqli_fetch_assoc($result);
    $_SESSION['user_type'] = $user['user_type'];

    // 若需要的話，您可以在這裡進行後續操作（例如顯示歡迎訊息等）
} else {
    echo "查詢使用者類型失敗：" . mysqli_error($link);
    exit();
}

// 處理公告新增
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add') {
        if (isset($_POST['share_post']) && isset($_POST['share_title'])) {
            $institution_id = isset($_SESSION['institution_id']) ? $_SESSION['institution_id'] : null;
            if (!$institution_id) {
                echo "無法取得機構ID，請重新登入。";
                exit();
            }

            $share_post = mysqli_real_escape_string($link, $_POST['share_post']);
            $share_title = mysqli_real_escape_string($link, $_POST['share_title']);
            $share_created = date("Y-m-d H:i:s");

            // 插入公告資料
            $sql = "INSERT INTO announcement (institution_id, share_title, share_post) VALUES ('$institution_id', '$share_title', '$share_post')";
            if (mysqli_query($link, $sql)) {
                $announcement_id = mysqli_insert_id($link);

                // 處理圖片上傳
                if (isset($_FILES['activity_photo']) && $_FILES['activity_photo']['error'][0] === UPLOAD_ERR_OK) {
                    $upload_dir = "./announcement/";
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }

                    foreach ($_FILES['activity_photo']['name'] as $key => $image_name) {
                        $image_tmp_name = $_FILES['activity_photo']['tmp_name'][$key];
                        $image_extension = pathinfo($image_name, PATHINFO_EXTENSION);
                        $new_image_name = uniqid() . '.' . $image_extension;
                        $image_path = $upload_dir . $new_image_name;

                        if (move_uploaded_file($image_tmp_name, $image_path)) {
                            $image_sql = "INSERT INTO announcement_image (announcement_id, image_name) VALUES ('$announcement_id', '$new_image_name')";
                            mysqli_query($link, $image_sql);
                        }
                    }
                }

                echo "<script>alert('公告新增成功'); window.location.href = 'announcement.php';</script>";
                exit();
            } else {
                echo "公告新增失敗：" . mysqli_error($link);
            }
        } else {
            echo "公告內容未設置。";
        }
    } elseif ($_POST['action'] == 'edit') {
        if (isset($_POST['announcement_id']) && isset($_POST['share_title']) && isset($_POST['share_post'])) {
            $announcement_id = $_POST['announcement_id'];
            $share_title = mysqli_real_escape_string($link, $_POST['share_title']);
            $share_post = mysqli_real_escape_string($link, $_POST['share_post']);

            $update_sql = "UPDATE announcement SET share_title = '$share_title', share_post = '$share_post' WHERE announcement_id = '$announcement_id'";
            if (mysqli_query($link, $update_sql)) {
                if (isset($_FILES['activity_photo']) && $_FILES['activity_photo']['error'][0] === UPLOAD_ERR_OK) {
                    $old_images_sql = "SELECT image_name FROM announcement_image WHERE announcement_id = '$announcement_id'";
                    $old_images_result = mysqli_query($link, $old_images_sql);
                    while ($old_image = mysqli_fetch_assoc($old_images_result)) {
                        unlink('./announcement/' . $old_image['image_name']);
                    }

                    mysqli_query($link, "DELETE FROM announcement_image WHERE announcement_id = '$announcement_id'");

                    $upload_dir = "./announcement/";
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }

                    foreach ($_FILES['activity_photo']['name'] as $key => $image_name) {
                        $image_tmp_name = $_FILES['activity_photo']['tmp_name'][$key];
                        $image_extension = pathinfo($image_name, PATHINFO_EXTENSION);
                        $new_image_name = uniqid() . '.' . $image_extension;
                        $image_path = $upload_dir . $new_image_name;

                        if (move_uploaded_file($image_tmp_name, $image_path)) {
                            $image_sql = "INSERT INTO announcement_image (announcement_id, image_name) VALUES ('$announcement_id', '$new_image_name')";
                            mysqli_query($link, $image_sql);
                        }
                    }
                }

                echo "<script>alert('公告更新成功'); window.location.href = 'announcement.php';</script>";
                exit();
            } else {
                echo "公告更新失敗：" . mysqli_error($link);
            }
        } else {
            echo "缺少必要的公告資料。";
        }
    }
}

// 如果是編輯請求，根據公告 ID 查詢公告資料
if (isset($_POST['announcement_id']) && $_POST['action'] == 'fetch') {
    $announcement_id = $_POST['announcement_id'];
    $sql = "SELECT * FROM announcement WHERE announcement_id = '$announcement_id'";
    $result = mysqli_query($link, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $announcement = mysqli_fetch_assoc($result);
        echo json_encode($announcement);
    } else {
        echo json_encode(['error' => '找不到該公告']);
    }
    exit();
}
//刪除
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['announcement_id'])) {
    $announcement_id = $_GET['announcement_id'];

    $announcement_id = mysqli_real_escape_string($link, $announcement_id);
    $sql = "DELETE FROM announcement WHERE announcement_id= '$announcement_id'";

    if (mysqli_query($link, $sql)) {
        echo "<script>alert('公告刪除成功！'); window.location.href = 'announcement.php';</script>";
    } else {
        echo "<script>alert('刪除失敗，請稍後再試。'); window.location.href = 'announcement.php';</script>";
    }
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <?php include "head.php"; ?>
    <style>
        /* 頁面標題的樣式 */
        h2 {
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        /* 公告區域的樣式 */
        .job-section {
            max-width: 70%;
            max-height: 600px;
            overflow: auto;
        }

        /* 公告卡片樣式 */
        .job-card {
            display: flex;
            position: relative;
            background-color: #fff;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 800px;
            align-items: flex-start;
        }

        /* 圖片預覽容器 */
        #add_image_preview_container {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        #add_image_preview_container img {
            max-width: 100px;
            max-height: 100px;
            object-fit: cover;
            border-radius: 5px;
        }

        /* 無邊框按鈕樣式 */
        .btn-no-border {
            border: none;
            font-size: 1.5rem;
            background: none;
            margin-bottom: 10px;
        }

        /* 額外圖片內容區 */
        .additional-images {
            position: relative;
        }

        .additional-images p {
            font-size: 14px;
            color: #666;
        }

        /* 日期顯示 */
        .date {
            position: absolute;
            right: 10px;
            bottom: 10px;
            font-size: 0.9em;
            color: #777;
        }

        /* 卡片頭部樣式，標題和按鈕並排 */
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* 卡片標題樣式 */
        .card-header h3 {
            margin: 0;
        }

        /* 按鈕容器樣式 */
        .button-container1 {
            display: flex;
            gap: 10px;
        }

        /* 編輯按鈕樣式 */
        .location1 {
            padding: 5px 10px;
            background-color: #9db1c9;
            color: #fff;
            border-radius: 5px;
            border: none;
            cursor: pointer;
        }

        /* 刪除按鈕樣式 */
        .location2 {
            background-color: #E8BCB9;
            color: #fff;
            padding: 5px 10px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
        }

        /* ------------- 圖片輪播的樣式 ------------- */

        /* 主要輪播容器 */
        .carousel {
            position: relative;
            width: 180px;
            /* 設定輪播圖片容器的寬度 */
            height: 250px;
            /* 設定輪播圖片容器的高度 */
            overflow: hidden;
            border-radius: 5px;
            /* 圓角 */
        }

        /* 輪播內部容器，隱藏其他圖片 */
        .carousel-inner {
            display: flex;
            /* 使用 flex 排列圖片 */
            transition: transform 0.5s ease-in-out;
            /* 圖片切換的動畫效果 */
        }

        /* 每張輪播圖片的樣式 */
        .carousel-item {
            flex-shrink: 0;
            /* 防止圖片縮放 */
            width: 100%;
            /* 讓每張圖片填滿容器 */
            display: none;
            /* 初始狀態下隱藏所有圖片 */
        }

        /* 顯示當前圖片 */
        .carousel-item.active {
            display: block;
        }

        /* 輪播指示點容器 */
        .carousel-indicators {
            position: absolute;
            bottom: -10px;
            left: 40%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        /* 輪播指示點樣式 */
        .carousel-indicators button {
            width: 12px;
            height: 12px;
            background-color: #aaa;
            border-radius: 50%;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        /* 活動指示點樣式 */
        .carousel-indicators .active {
            background-color: black;
        }

        /* 圖片樣式 */
        .carousel-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 5px;
        }

        /* 指示點被選中時的顏色 */
        .carousel-indicators button:hover {
            background-color: #555;
        }

        /* 放大圖片的模態框 */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            color: white;
            font-size: 30px;
            cursor: pointer;
        }

        /* 圖片輪播左右按鈕 */
        .prev-btn,
        .next-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background-color: transparent;
            color: white;
            font-size: 30px;
            border: none;
            padding: 10px;
            cursor: pointer;
            z-index: 1000;
        }

        .prev-btn {
            left: 350px;
        }

        .next-btn {
            right: 350px;
        }

        /* 關閉按鈕 */
        .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            color: white;
            font-size: 30px;
            cursor: pointer;
            z-index: 1001;
        }
    </style>
</head>


<body>
    <?php include "nav.php"; ?>
    <div class="s4-container">
        <div class="filter-section">
            <h2 style="text-align: center; color:#9dc7c9;">公告</h2>

            <!-- 搜尋表單 -->
            <form action="" method="GET" class="input-group">
                <div class="mb-3" style="width:100%">
                    <input type="text" name="search_input" placeholder="請輸入關鍵字"
                        value="<?php echo isset($_GET['search_input']) ? htmlspecialchars($_GET['search_input']) : ''; ?>">
                </div>
                <div class="mb-3" style="width:100%">
                    <button type="submit" class="btn" name="action" value="search" style="background-color:#9dc7c9;">
                        <i class="fa fa-search"></i> 搜尋
                    </button>
                </div>
            </form>

            <!-- 顯示新增公告按鈕，只有 user_type 為 'hospital' 時顯示 -->
            <div class="mb-3" style="width:100%">
                <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'hospital'): ?>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"
                        style="background-color:#cebca6;">
                        <i class="fa-solid fa-plus"></i> 新增公告
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="job-section">

            <?php
            // 獲取搜索輸入
            $searchInput = isset($_GET['search_input']) ? trim($_GET['search_input']) : '';
            $user_type = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : '';  // 從 session 讀取 user_type
            $institution_id = isset($_SESSION['institution_id']) ? $_SESSION['institution_id'] : null; // 確保從 session 中獲取 institution_id
            
            // 確保用戶是醫療機構且已經登入
            if ($user_type !== 'hospital' || $institution_id === null) {
                echo "<p>帳號尚未無效或未登入，無法查詢公告。</p>";
                exit;
            }

            // 構建 SQL 查詢語句：根據 institution_id 查詢公告
            $sql = "SELECT a.announcement_id, a.share_title, a.share_post, a.share_created, ai.image_name
            FROM announcement a
            LEFT JOIN announcement_image ai ON a.announcement_id = ai.announcement_id
            WHERE a.institution_id = ?";

            // 如果有搜尋條件
            if (isset($_GET['action']) && $_GET['action'] === 'search' && !empty($searchInput)) {
                $searchInputEscaped = "%" . $searchInput . "%";  // 搜索詞應該包括通配符
                $sql .= " AND (a.share_title LIKE ? OR a.share_post LIKE ?)";
            }

            // 預設按照公告創建時間降序排列
            $sql .= " ORDER BY a.share_created DESC";

            // 使用預處理語句來防止 SQL 注入
            $stmt = mysqli_prepare($link, $sql);

            if (!$stmt) {
                die('SQL準備失敗: ' . mysqli_error($link));
            }

            // 綁定參數
            if (isset($_GET['action']) && $_GET['action'] === 'search' && !empty($searchInput)) {
                mysqli_stmt_bind_param($stmt, 'sss', $institution_id, $searchInputEscaped, $searchInputEscaped); // 綁定搜尋詞
            } else {
                mysqli_stmt_bind_param($stmt, 's', $institution_id); // 沒有搜尋條件時，僅使用 institution_id
            }

            // 執行查詢
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            // 查詢結果處理
            $announcements = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $announcement_id = $row['announcement_id'];
                if (!isset($announcements[$announcement_id])) {
                    $announcements[$announcement_id] = [
                        'share_title' => $row['share_title'],
                        'share_post' => $row['share_post'],
                        'share_created' => $row['share_created'],
                        'images' => []
                    ];
                }

                if ($row['image_name']) {
                    $announcements[$announcement_id]['images'][] = $row['image_name'];
                }
            }

            // 檢查是否有公告
            if (!empty($announcements)) {
                // 如果有公告，輸出公告內容
                foreach ($announcements as $announcement_id => $announcement) {
                    echo '<div class="job-card" style="display: flex; align-items: flex-start; margin-bottom: 20px;" data-card-id="' . $announcement_id . '">';

                    // 圖片輪播
                    if (!empty($announcement['images'])) {
                        echo '<div class="carousel" style="flex: 0 0 180px; margin-right: 20px;">';
                        echo '<div class="carousel-inner">';

                        // 顯示圖片輪播
                        foreach ($announcement['images'] as $index => $image) {
                            $image_path = htmlspecialchars('./announcement/' . $image);

                            // 檢查圖片是否存在
                            if (!file_exists($image_path)) {
                                $image_path = './announcement/default-image.jpg'; // 預設圖片
                            }

                            echo '<div class="carousel-item ' . ($index == 0 ? 'active' : '') . '">
                            <img src="' . $image_path . '" alt="Announcement Image" class="carousel-image" style="width: 180px; max-height: 230px; object-fit: cover; border-radius: 5px;" onclick="openImageModal(' . json_encode($announcement['images']) . ', ' . $index . ')">
                          </div>';
                        }

                        echo '</div>'; // carousel-inner
            
                        // 顯示輪播指標
                        echo '<div class="carousel-indicators">';
                        foreach ($announcement['images'] as $index => $image) {
                            echo '<button type="button" class="indicator ' . ($index == 0 ? 'active' : '') . '" data-index="' . $index . '"></button>';
                        }
                        echo '</div>';
                        echo '</div>'; // carousel container
                    }

                    // 公告標題、內容、日期
                    echo '<div style="flex: 1; position: relative;">
                    <div class="card-header">
                        <h4>' . htmlspecialchars($announcement['share_title']) . '</h4>';

                    // 根據 user_type 顯示不同的按鈕
                    if ($user_type === 'hospital') {
                        echo '<div class="button-container1">
                        <button class="location1" data-bs-toggle="modal" data-bs-target="#editModal' . $announcement_id . '">編輯</button>
                        <button onclick="deleteAnnouncement(' . $announcement_id . ')" class="location2">刪除</button>
                      </div>';
                    }

                    echo '</div>
                    <div class="additional-images">
                        <p>' . nl2br(htmlspecialchars($announcement['share_post'])) . '</p>
                    </div>
                    <p style="padding-bottom: inherit; text-align:right;">' . htmlspecialchars(date('Y-m-d', strtotime($announcement['share_created']))) . '</p>
                  </div>
              </div>';
                }
            } else {
                // 沒有查詢到任何公告或相關公告時顯示提示
                if (isset($_GET['action']) && $_GET['action'] === 'search' && !empty($searchInput)) {
                    echo "<p>沒有相關公告。</p>"; // 如果有搜尋但未找到結果
                } else {
                    echo "<p>這個醫療機構目前沒有公告。</p>"; // 沒有公告的情況
                }
            }
            ?>
        </div>



        <!-- 編輯公告 Modal -->
        <?php foreach ($announcements as $announcement_id => $announcement): ?>
            <div class="modal fade s3-modal" id="editModal<?php echo $announcement_id; ?>" tabindex="-1"
                aria-labelledby="editModalLabel<?php echo $announcement_id; ?>" aria-hidden="true" data-bs-backdrop="static"
                data-bs-keyboard="false">
                <div class="modal-dialog custom-modal-dialog modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editModalLabel<?php echo $announcement_id; ?>">編輯公告</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="edit">
                                <input type="hidden" name="announcement_id" value="<?php echo $announcement_id; ?>">
                                <div class="mb-3">
                                    <label for="share_title" class="form-label">公告標題</label>
                                    <input type="text" name="share_title" class="form-control"
                                        value="<?php echo htmlspecialchars($announcement['share_title']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="share_post" class="form-label">公告內容</label>
                                    <textarea name="share_post" class="form-control" rows="5"
                                        required><?php echo htmlspecialchars($announcement['share_post']); ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="activity_photo" class="form-label">更換圖片</label>
                                    <input type="file" name="activity_photo[]" id="activity_photo" class="form-control"
                                        accept="image/*" multiple onchange="previewImages(event)">
                                    <!-- 圖片預覽區域 -->
                                    <?php
                                    // 如果公告有多張圖片，顯示每一張圖片
                                    if (!empty($announcement['images'])) {
                                        echo '<div style="display: flex; gap: 10px;" id="existing-images">';
                                        foreach ($announcement['images'] as $index => $image) {
                                            $imagePath = './announcement/' . $image;
                                            echo '<img src="' . htmlspecialchars($imagePath) . '" alt="Announcement Image" class="carousel-image" style="max-width: 150px; height: auto; margin-top: 10px;">';
                                        }
                                        echo '</div>';
                                    } else {
                                        echo '<p>目前沒有圖片。</p>';
                                    }
                                    ?>
                                </div>
                                <!-- 圖片預覽區 (顯示選擇的圖片) -->
                                <div class="image-preview-area mt-2">
                                    <div id="image-preview-container" style="display: flex; gap: 10px; margin-top: 10px;">
                                    </div>
                                </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                            <button type="submit" class="btn btn-primary">保存變更</button>
                        </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- 新增公告 Modal -->
    <div class="modal fade s3-modal" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true"
        data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog custom-modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addModalLabel">新增公告</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label for="share_title" class="form-label">公告標題</label>
                            <input type="text" name="share_title" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="share_post" class="form-label">公告內容</label>
                            <textarea name="share_post" class="form-control" rows="5" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="activity_photo" class="form-label">公告圖片</label>
                            <input type="file" class="form-control" id="activity_photo" name="activity_photo[]"
                                accept="image/*" multiple required onchange="previewAddImages(event)">
                            <div id="add_image_preview_container"
                                style="margin-top: 10px; display: flex; flex-wrap: wrap; gap: 10px;"></div>
                        </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">新增公告</button>
                </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        //新增的圖片預覽區
        document.getElementById("activity_photo").addEventListener("change", function (e) {
            const previewContainer = document.getElementById("add_image_preview_container");
            previewContainer.innerHTML = ""; // 清空預覽容器

            for (let i = 0; i < e.target.files.length; i++) {
                const file = e.target.files[i];
                const reader = new FileReader();
                reader.onload = function (event) {
                    const img = document.createElement("img");
                    img.src = event.target.result;
                    previewContainer.appendChild(img);
                };
                reader.readAsDataURL(file);
            }
        });

        // 刪除公告
        function deleteAnnouncement(announcementId) {
            // 顯示確認提示
            if (confirm("您確定要刪除這個公告嗎？")) {
                // 使用 GET 請求傳遞公告 ID
                window.location.href = "announcement.php?action=delete&announcement_id=" + announcementId;
            }
        }

        // 編輯公告圖片預覽區
        function previewImages(event) {
            const previewContainer = document.getElementById('image-preview-container');
            const existingImagesContainer = document.getElementById('existing-images');
            previewContainer.innerHTML = '';
            existingImagesContainer.innerHTML = '';
            const files = event.target.files;
            if (files) {
                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.alt = 'Selected Image';
                        img.style.maxWidth = '150px';
                        img.style.height = 'auto';
                        img.style.marginTop = '10px';
                        previewContainer.appendChild(img);
                    };
                    reader.readAsDataURL(file);
                }
            }
        }

        document.addEventListener("DOMContentLoaded", function () {
            const carousels = document.querySelectorAll('.carousel');

            carousels.forEach(carousel => {
                const items = carousel.querySelectorAll('.carousel-item');
                const indicators = carousel.querySelectorAll('.carousel-indicators button');
                const images = Array.from(carousel.querySelectorAll('.carousel-item img')).map(img => img.src.split('/').pop()); // 獲取所有圖片的文件名
                let currentIndex = 0;

                // 更新輪播顯示
                function updateCarousel() {
                    items.forEach(item => item.classList.remove('active'));
                    indicators.forEach(indicator => indicator.classList.remove('active'));

                    items[currentIndex].classList.add('active');
                    indicators[currentIndex].classList.add('active');
                }

                // 點擊指示點切換圖片
                indicators.forEach((indicator, index) => {
                    indicator.addEventListener('click', function () {
                        currentIndex = index;
                        updateCarousel();
                    });
                });

                // 自動切換輪播圖片
                setInterval(function () {
                    currentIndex = (currentIndex + 1) % items.length; // 自動循環
                    updateCarousel();
                }, 5000); // 每 5 秒切換一次

                // 點擊圖片打開放大視窗
                items.forEach((item, index) => {
                    const image = item.querySelector('img');
                    image.addEventListener('click', function () {
                        openImageModal(images, index); // 開啟模態框顯示該圖片
                    });
                });
            });
        });

        // 打開模態框顯示圖片
        function openImageModal(images, currentIndex) {
            // 創建模態框
            var modal = document.createElement('div');
            modal.classList.add('modal');

            // 創建模態框的內容
            var modalContent = document.createElement('div');
            modalContent.classList.add('modal-content1');

            // 創建圖片元素
            var img = document.createElement('img');
            img.src = './announcement/' + images[currentIndex];
            img.alt = "Enlarged Image";
            img.style.width = '500px';   // 保持圖片的原始大小
            img.style.height = 'auto';

            // 創建上一張圖片按鈕
            var prevBtn = document.createElement('button');
            prevBtn.innerHTML = "&lt;";  // 左箭頭
            prevBtn.classList.add('prev-btn');
            prevBtn.onclick = function () {
                // 顯示上一張圖片
                currentIndex = (currentIndex - 1 + images.length) % images.length;
                img.src = './announcement/' + images[currentIndex];
            };

            // 創建下一張圖片按鈕
            var nextBtn = document.createElement('button');
            nextBtn.innerHTML = "&gt;";  // 右箭頭
            nextBtn.classList.add('next-btn');
            nextBtn.onclick = function () {
                // 顯示下一張圖片
                currentIndex = (currentIndex + 1) % images.length;
                img.src = './announcement/' + images[currentIndex];
            };

            // 創建關閉按鈕
            var closeBtn = document.createElement('span');
            closeBtn.classList.add('close-btn');
            closeBtn.innerHTML = "&times;"; // 乘號作為關閉圖標
            closeBtn.onclick = function () {
                modal.style.display = "none"; // 關閉模態框
            };

            // 將圖片、導航按鈕和關閉按鈕添加到模態框內容中
            modalContent.appendChild(prevBtn);
            modalContent.appendChild(img);
            modalContent.appendChild(nextBtn);
            modalContent.appendChild(closeBtn);

            // 將模態框內容添加到模態框
            modal.appendChild(modalContent);

            // 將模態框添加到頁面中
            document.body.appendChild(modal);

            // 顯示模態框
            modal.style.display = "flex";
        }

        // 關閉模態框（當用戶點擊模態框之外的區域時）
        window.onclick = function (event) {
            var modal = document.querySelector('.modal');
            if (modal && event.target === modal) {
                modal.style.display = "none";
            }
        };


    </script>
</body>

</html>