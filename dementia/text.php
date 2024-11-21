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
    $user = mysqli_fetch_assoc($result);
    if ($user['user_type'] !== 'hospital') {
        echo "無權限新增公告，僅限醫療機構使用者。";
        exit();
    }
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

// 查詢所有公告資料
$institution_id = $_SESSION['institution_id'];
$sql = "SELECT a.announcement_id, a.share_title, a.share_post, a.share_created, ai.image_name
        FROM announcement a
        LEFT JOIN announcement_image ai ON a.announcement_id = ai.announcement_id
        WHERE a.institution_id = '$institution_id'
        ORDER BY a.share_created DESC";

$result = mysqli_query($link, $sql);
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
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <?php include "head.php"; ?>
    <style>
        h2 {
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .job-list {
            display: block;
            padding-left: 20px;
            width: 75%;
        }

        .job-section {
            max-width: 70%;
            max-height: 600px;
            overflow: auto;
        }

        .job-card {
            position: relative;
            background-color: #fff;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 800px;
            /* 固定寬度 */
        }


        .carousel-inner img {
            width: 100%;
            height: auto;
        }

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

        .btn-no-border {
            border: none;
            font-size: 1.5rem;
            background: none;
            margin-bottom: 10px;
        }

        .additional-images {
            position: relative;
            padding-bottom: 40px;
            /* 預留空間以避免文字遮擋按鈕 */
        }



        .date {
            position: absolute;
            right: 10px;
            bottom: 10px;
            font-size: 0.9em;
            color: #777;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            /* 將標題和按鈕推到兩邊 */
            align-items: center;
            /* 垂直居中對齊 */
        }

        .card-header h3 {
            margin: 0;
            /* 去除標題的外邊距 */
        }

        .button-container1 {
            display: flex;
            /* 使用 flexbox 使按鈕並排 */
            gap: 10px;
            /* 按鈕之間的間隔 */
        }

        .location1 {
            padding: 5px 10px;
            background-color: #c6d6eb;
            color: #fff;
            padding: 5px 10px;
            border-radius: 5px;
            border: none;
        }

        .location2 {
            background-color: #E8BCB9;
            color: #fff;
            padding: 5px 10px;
            border-radius: 5px;
            border: none;
        }
    </style>
</head>


<body>
    <?php include "nav.php"; ?>
    <div class="s4-container">
        <div class="filter-section">
            <h2 style="text-align: center;">公告</h2>
            <form action="" method="POST" class="input-group">
                <div class="mb-3" style="width:100%">
                    <input type="text" name="search_input" placeholder="請輸入關鍵字" value="">
                </div>
                <div class="mb-3" style="width:100%">
                    <button type="submit" class="btn" name="action" value="search">
                        <i class="fa fa-search"></i> 搜尋
                    </button>
                </div>
            </form>
            <div class="mb-3" style="width:100%">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="fa-solid fa-plus"></i> 新增公告
                </button>
            </div>
        </div>
        <div class="job-section">
            <?php if (!empty($announcements)): ?>
                <?php foreach ($announcements as $announcement_id => $announcement): ?>
                    <div class="job-card" style="display: flex; align-items: flex-start; margin-bottom: 20px;"
                        data-card-id="<?php echo $announcement_id; ?>">
                        <!-- 左側圖片區塊 -->
                        <?php if (!empty($announcement['images'])): ?>
                            <div id="carousel<?php echo $announcement_id; ?>" class="carousel slide" data-bs-ride="carousel"
                                style="flex: 0 0 150px; margin-right: 15px;">
                                <div class="carousel-inner">
                                    <?php foreach ($announcement['images'] as $index => $image): ?>
                                        <div class="carousel-item <?php echo $index == 0 ? 'active' : ''; ?>">
                                            <img src="<?php echo htmlspecialchars('./announcement/' . $image); ?>"
                                                alt="Announcement Image" class="carousel-image"
                                                style="width: 100%; height: auto; border-radius: 5px;">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="carousel-indicators">
                                    <?php foreach ($announcement['images'] as $index => $image): ?>
                                        <button type="button" data-bs-target="#carousel<?php echo $announcement_id; ?>"
                                            data-bs-slide-to="<?php echo $index; ?>" class="<?php echo $index == 0 ? 'active' : ''; ?>"
                                            aria-current="true" aria-label="Slide <?php echo $index + 1; ?>"></button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div style="flex: 1; position: relative;">
                            <div class="card-header">
                                <h4><?php echo htmlspecialchars($announcement['share_title']); ?></h4>
                                <div class="button-container1">
                                    <button class="location1" data-bs-toggle="modal"
                                        data-bs-target="#editModal<?php echo $announcement_id; ?>">編輯</button>
                                    <button class="location2" data-bs-toggle="modal"
                                        data-bs-target="#editModal<?php echo $announcement_id; ?>">刪除</button>
                                </div>
                            </div>
                            <div class="additional-images">
                                <p><?php echo htmlspecialchars($announcement['share_post']); ?></p>
                            </div>
                            <div style="text-align:right;">
                                <p style="padding-bottom: inherit;">
                                    <?php echo htmlspecialchars(date('Y-m-d', strtotime($announcement['share_created']))); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <!-- 編輯公告 Modal -->
                    <div class="modal fade s3-modal" id="editModal<?php echo $announcement_id; ?>" tabindex="-1"
                        aria-labelledby="editModalLabel<?php echo $announcement_id; ?>" aria-hidden="true"
                        data-bs-backdrop="static" data-bs-keyboard="false">
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
                                            <input type="file" name="activity_photo[]" class="form-control" accept="image/*"
                                                multiple>

                                            <!-- 圖片預覽區域 -->
                                            <div class="image-preview-container mt-2">
                                                <?php
                                                // 如果公告有圖片，顯示原始圖片
                                                if (!empty($announcement['image_name'])) {
                                                    echo '<img src="' . htmlspecialchars($announcement['image_name']) . '" alt="Current Image" class="img-thumbnail" style="max-width: 100px; margin-right: 10px;">';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                                    <button type="submit" class="btn btn-primary">更新公告</button>
                                </div>
                                </form>
                            </div>
                        </div>
                    </div>

                <?php endforeach; ?>
            <?php else: ?>
                <p>目前沒有任何公告。</p>
            <?php endif; ?>
        </div>

    </div>
    <!-- 新增公告 Modal -->
    <div class="modal fade s3-modal" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true"
        data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog custom-modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addModalLabel">新增公告</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addForm" method="POST" action="" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="share_title" class="form-label">公告標題</label>
                            <input type="text" class="form-control" id="share_title" name="share_title" required>
                        </div>
                        <div class="mb-3">
                            <label for="share_post" class="form-label">公告內容</label>
                            <textarea class="form-control" id="share_post" name="share_post"
                                style="height:125px;overflow-y:auto;" required></textarea>
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
                        <div>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                            <button type="submit" class="btn btn-primary" form="addForm" name="action"
                                value="add">新增公告</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>



    <script>
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


    </script>

</body>

</html>