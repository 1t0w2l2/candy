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
$where_clause = "WHERE `status` != '已取消' ";
if (!empty($search_query)) {
    $safe_query = mysqli_real_escape_string($link, $search_query);
    $where_clause .= "AND (`institution_id` LIKE '%$safe_query%' 
                     OR `institution_name` LIKE '%$safe_query%' 
                     OR `address` LIKE '%$safe_query%' 
                     OR `phone` LIKE '%$safe_query%' 
                     OR `website` LIKE '%$safe_query%')";
}

// 查詢總筆數
$sql_count = "SELECT COUNT(*) as total FROM `mistake` $where_clause";
$result_count = mysqli_query($link, $sql_count);
$total_records = $result_count ? mysqli_fetch_assoc($result_count)['total'] : 0;

// 計算總頁數
$total_pages = max(1, ceil($total_records / $records_per_page));

// 查詢資料
$sql = "SELECT `institution_id`, `institution_name`, `address`, `phone`, `website`, `report_datetime`, `status` 
        FROM `mistake` 
        $where_clause
        LIMIT $offset, $records_per_page";
$result = mysqli_query($link, $sql);
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
</style>
<head>
    <?php include 'head.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>錯誤回報管理</title>
    <link rel="stylesheet" href="styles.css"> <!-- 加入你的CSS樣式 -->
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
                            <th style="width: 10%;">機構代碼</th>
                            <th style="width: 20%;">機構名稱</th>
                            <th style="width: 30%;">地址</th>
                            <th style="width: 10%;">電話</th>
                            <th style="width: 10%;">網站</th>
                            <th style="width: 10%;">狀態</th>
                            <th style="width: 10%;">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <?php
                            // 假設有一個函數可以獲取原始資料
                            $original_data = getOriginalData($row['institution_id']); // 這個函數需要自己實作
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['institution_id']); ?></td>
                                <td>
                                    <?php
                                    // 比對機構名稱
                                    if ($row['institution_name'] !== $original_data['institution_name']) {
                                        echo '<span style="color:red;">' . htmlspecialchars($row['institution_name']) . '</span>';
                                    } else {
                                        echo htmlspecialchars($row['institution_name']);
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    // 比對地址
                                    if ($row['address'] !== $original_data['address']) {
                                        echo '<span style="color:red;">' . htmlspecialchars($row['address']) . '</span>';
                                    } else {
                                        echo htmlspecialchars($row['address']);
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    // 比對電話
                                    if ($row['phone'] !== $original_data['phone']) {
                                        echo '<span style="color:red;">' . htmlspecialchars($row['phone']) . '</span>';
                                    } else {
                                        echo htmlspecialchars($row['phone']);
                                    }
                                    ?>
                                </td>
                                <td><a href="<?php echo htmlspecialchars($row['website']); ?>" target="_blank">網站</a></td>
                                <td><?php echo htmlspecialchars($row['status']); ?></td>
                                <td>
                                    <div class="button-group">
                                        <button class="edit-btn"
                                            onclick="window.location.href='edit_institution.php?institution_id=<?php echo urlencode($row['institution_id']); ?>'">編輯</button>
                                        <button class="delete-btn"
                                            onclick="confirmDelete('<?php echo htmlspecialchars($row['institution_id']); ?>')">刪除</button>
                                    </div>
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
            // 顯示首頁和上一頁
            if ($current_page > 1) {
                echo '<a href="?page=1"><button>首頁</button></a>';
                echo '<a href="?page=' . ($current_page - 1) . '"><button>&lt;</button></a>';
            } else {
                echo '<button class="disabled">首頁</button>';
                echo '<button class="disabled">&lt;</button>';
            }

            // 頁面範圍顯示邏輯，顯示5頁
            $start_page = max(1, $current_page - 2);
            $end_page = min($total_pages, $current_page + 2);

            // 顯示範圍的頁面
            for ($i = $start_page; $i <= $end_page; $i++) {
                if ($i == $current_page) {
                    echo '<button class="active">' . $i . '</button>';
                } else {
                    echo '<a href="?page=' . $i . '"><button>' . $i . '</button></a>';
                }
            }

            // 如果還有更多頁面，顯示省略號
            if ($end_page < $total_pages) {
                echo '<span class="ellipsis">...</span>';
            }

            // 顯示下一頁和末頁
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
</body>

</html>

<?php
// 假設的函數來獲取原始資料
function getOriginalData($institution_id)
{
    global $link; // 使用全局的資料庫連線
    $sql = "SELECT `institution_name`, `address`, `phone` FROM `mistake` WHERE `institution_id` = '$institution_id'";
    $result = mysqli_query($link, $sql);
    return mysqli_fetch_assoc($result);
}
?>
