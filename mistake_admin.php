<?php
include "db.php"; // 引入資料庫連線

// 每頁顯示的資料筆數
$records_per_page = 10;

// 獲取當前頁數，若無指定則預設為第1頁
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

// 計算資料偏移量
$offset = ($current_page - 1) * $records_per_page;

// 搜尋功能處理
$search_query = isset($_GET['hospital_query']) ? trim($_GET['hospital_query']) : '';

// 查詢條件，過濾掉審核狀態為「已取消」的項目
$where_clause = "WHERE m.`status` != '已取消' ";
if (!empty($search_query)) {
    $safe_query = mysqli_real_escape_string($link, $search_query);
    $where_clause .= "AND (m.`institution_id` LIKE '%$safe_query%' 
                     OR m.`institution_name` LIKE '%$safe_query%' 
                     OR m.`address` LIKE '%$safe_query%' 
                     OR m.`phone` LIKE '%$safe_query%' 
                     OR m.`website` LIKE '%$safe_query%')";
}

// 查詢總筆數
$sql_count = "SELECT COUNT(*) as total FROM `mistake` m $where_clause";
$result_count = mysqli_query($link, $sql_count);
$total_records = $result_count ? mysqli_fetch_assoc($result_count)['total'] : 0;

// 計算總頁數
$total_pages = max(1, ceil($total_records / $records_per_page));

// 查詢資料
$sql = "SELECT m.`institution_id`, m.`institution_name`, m.`address`, m.`phone`, m.`website`, m.`report_datetime`, m.`status`, m.`mistake_id`
        FROM `mistake` m 
        $where_clause
        LIMIT $offset, $records_per_page";
$result = mysqli_query($link, $sql);

// **取得 institution 的原始資料**
function getOriginalData($institution_id)
{
    global $link;
    $sql = "SELECT `institution_name`, `address`, `phone`, `website` FROM `institution` WHERE `institution_id` = '$institution_id'";
    $result = mysqli_query($link, $sql);
    return mysqli_fetch_assoc($result);
}

// **取得 institution 的原始營業時間**
function getOriginalServiceTimes($institution_id)
{
    global $link;
    $sql = "SELECT `day`, `open_time`, `close_time` FROM `servicetime` WHERE `institution_id` = '$institution_id'";
    $result = mysqli_query($link, $sql);

    $original_times = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $original_times[$row['day']] = "{$row['open_time']} - {$row['close_time']}";
    }

    return $original_times;
}

// **取得 mistake 回報的營業時間**
function getMistakeServiceTimes($mistake_id)
{
    global $link;
    $sql = "SELECT `day`, `open_time`, `close_time` FROM `mistake_servicetime` WHERE `mistake_id` = '$mistake_id'";
    $result = mysqli_query($link, $sql);

    $mistake_times = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $mistake_times[$row['day']] = "{$row['open_time']} - {$row['close_time']}";
    }

    return $mistake_times;
}

// **比對營業時間，只顯示修改的部分**
function compareServiceTimes($institution_id, $mistake_id)
{
    $original_times = getOriginalServiceTimes($institution_id);
    $mistake_times = getMistakeServiceTimes($mistake_id);

    $changes = [];
    foreach ($mistake_times as $day => $mistake_time) {
        $original_time = isset($original_times[$day]) ? $original_times[$day] : "無資料";

        // 只有當營業時間不同時才加入變更清單
        if ($original_time !== $mistake_time) {
            $changes[] = "<span style='color:red;'>$day: $mistake_time</span>";
        }
    }

    // 如果沒有任何變更，則顯示「無修改」
    return !empty($changes) ? implode("<br>", $changes) : "無修改";
}

?>

<!DOCTYPE html>
<html lang="zh-Hant">
<style>
    .custom-container {
        display: flex;
        flex-direction: column;
        padding: 20px;
        padding: 5% 100px 0px 100px;
    }

    .custom-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 20px;
    }

    .custom-header h1 {
        font-size: 28px;
        font-weight: bold;
        color: #6B4D38;
        text-transform: uppercase;
        border-bottom: 2px solid #cfb59e;
    }

    .search-input {
        padding: 8px;
        margin-right: 10px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }

    .search-button {
        padding: 8px 12px;
        border: none;
        border-radius: 4px;
        background-color: #dee5ed;
        cursor: pointer;
    }

    .search-button:hover {
        background-color: #c0d3e4;
    }

    .custom-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .custom-table th,
    .custom-table td {
        border: 1px solid #ddd;
        padding: 10px;
        text-align: left;
    }

    .custom-table th {
        background-color: #f4f4f4;
    }

    .custom-pagination {
        display: flex;
        justify-content: center;
        margin-top: 20px;
        flex-wrap: wrap;
    }

    .custom-pagination button {
        padding: 8px 16px;
        margin: 3px;
        border: none;
        background-color: #f0f0f0;
        border-radius: 20px;
        color: #333;
        font-size: 14px;
        cursor: pointer;
        transition: background-color 0.3s, box-shadow 0.3s;
        min-width: 40px;
        text-align: center;
    }

    .custom-pagination button:hover {
        background-color: #e0e0e0;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
    }

    .custom-pagination button.active {
        background-color: #555;
        color: white;
        font-weight: bold;
    }

    .custom-pagination button.disabled {
        background-color: #f0f0f0;
        color: #aaa;
        cursor: not-allowed;
    }

    .custom-pagination .ellipsis {
        font-size: 16px;
        color: #333;
        line-height: 32px;
    }

    .custom-pagination span {
        color: #999;
        font-size: 14px;
    }

    @media (max-width: 768px) {
        .custom-pagination button {
            font-size: 12px;
            padding: 6px 12px;
        }
    }

    .custom-table td button {
        padding: 8px 16px;
        border: none;
        border-radius: 5px;
        font-size: 14px;
        cursor: pointer;
        transition: background-color 0.3s, color 0.3s;
        margin: 0 5px;
    }


    .custom-table td button.edit-btn {
        background-color: #4CAF50;
        color: white;
    }

    .custom-table td button.edit-btn:hover {
        background-color: #45a049;
    }

    .custom-table td button.edit-btn:focus {
        outline: none;
    }

    .custom-table td button.delete-btn {
        background-color: #f44336;
        color: white;
    }

    .custom-table td button.delete-btn:hover {
        background-color: #e53935;
    }

    .custom-table td button.delete-btn:focus {
        outline: none;
    }

    .custom-table td button:disabled {
        background-color: #e0e0e0;
        color: #a0a0a0;
        cursor: not-allowed;
    }

    .button-group {
        display: flex;
        /* 使用 Flexbox 來排列按鈕 */
        justify-content: flex-start;
        /* 將按鈕靠左對齊 */
    }

    .edit-btn,
    .delete-btn {
        margin-right: 5px;
        /* 調整按鈕之間的間距 */
        white-space: nowrap;
        /* 防止文字換行 */
    }
  
        /* 模態框樣式 */
        .modal {
            display: none; /* 隱藏模態框 */
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgb(0,0,0);
            background-color: rgba(0,0,0,0.4);
            padding-top: 60px;
        }
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }


</style>
<head>
    <?php include 'head.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>錯誤回報管理</title>
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <?php include "nav.php"; ?>
    <div class="custom-container">
        <header class="custom-header">
            <h1>錯誤回報管理</h1>
        </header>

        <main>
            <?php if ($result && mysqli_num_rows($result) > 0): ?>
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th style="width: 8%;">機構代碼</th>
                            <th style="width: 20%;">機構名稱</th>
                            <th style="width: 25%;">地址</th>
                            <th style="width: 10%;">電話</th>
                            <th style="width: 10%;">網站</th>
                            <th style="width: 19%;">營業時間</th>
                            <th style="width: 15%;">狀態</th>
                            <th style="width: 10%;">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <?php
                            $original_data = getOriginalData($row['institution_id']);
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['institution_id']); ?></td>
                                <td>
                                    <?php
                                    echo ($row['institution_name'] !== $original_data['institution_name'])
                                        ? "<span style='color:red;'>" . htmlspecialchars($row['institution_name']) . "</span>"
                                        : htmlspecialchars($row['institution_name']);
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    echo ($row['address'] !== $original_data['address'])
                                        ? "<span style='color:red;'>" . htmlspecialchars($row['address']) . "</span>"
                                        : htmlspecialchars($row['address']);
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    echo ($row['phone'] !== $original_data['phone'])
                                        ? "<span style='color:red;'>" . htmlspecialchars($row['phone']) . "</span>"
                                        : htmlspecialchars($row['phone']);
                                    ?>
                                </td>
                                <td><a href="<?php echo htmlspecialchars($row['website']); ?>" target="_blank">網站</a></td>
                                <td><?php echo compareServiceTimes($row['institution_id'], $row['mistake_id']); ?></td>
                                <td>
                                    <button class="pending-btn"
                                        onclick="window.location.href='edit_institution_1.php?institution_id=<?php echo urlencode($row['institution_id']); ?>'">待審核</button>
                                </td>
                                <td>
                                    <button class="delete-btn"
                                        onclick="confirmDelete('<?php echo htmlspecialchars($row['institution_id']); ?>')">刪除</button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>沒有資料可顯示。</p>
            <?php endif; ?>
        </main>
        <!-- 分頁導航 -->
        <div class="custom-pagination">
            <?php
            if ($current_page > 1) {
                echo '<a href="?page=1"><button>首頁</button></a>';
                echo '<a href="?page=' . ($current_page - 1) . '"><button>&lt;</button></a>';
            } else {
                echo '<button class="disabled">首頁</button>';
                echo '<button class="disabled">&lt;</button>';
            }

            $start_page = max(1, $current_page - 2);
            $end_page = min($total_pages, $current_page + 2);

            for ($i = $start_page; $i <= $end_page; $i++) {
                if ($i == $current_page) {
                    echo '<button class="active">' . $i . '</button>';
                } else {
                    echo '<a href="?page=' . $i . '"><button>' . $i . '</button></a>';
                }
            }

            if ($end_page < $total_pages) {
                echo '<span class="ellipsis">...</span>';
            }

            if ($current_page < $total_pages) {
                echo '<a href="?page=' . ($current_page + 1) . '"><button>&gt;</button></a>';
                echo '<a href="?page=' . $total_pages . '"><button>末頁</button></a>';
            } else {
                echo '<button class="disabled">&gt;</button>';
                echo '<button class="disabled">末頁</button>';
            }
            ?>
        </div>

    </div>
    </div>

</body>

</html>