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

// 查詢資料，包括營業時間
$sql = "SELECT m.`institution_id`, m.`institution_name`, m.`address`, m.`phone`, m.`website`, m.`report_datetime`, m.`status`, m.`mistake_id`
        FROM `mistake` m 
        $where_clause
        LIMIT $offset, $records_per_page";
$result = mysqli_query($link, $sql);
?>

<!DOCTYPE html>
<html lang="zh-Hant">

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
                            <th style="width: 12%;">營業時間</th>
                            <th style="width: 10%;">狀態</th>
                            <th style="width: 10%;">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <?php
                            $original_data = getOriginalData($row['institution_id']);
                            $service_times = getServiceTimes($row['mistake_id']);
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['institution_id']); ?></td>
                                <td>
                                    <?php
                                    if ($row['institution_name'] !== $original_data['institution_name']) {
                                        echo '<span style="color:red;">' . htmlspecialchars($row['institution_name']) . '</span>';
                                    } else {
                                        echo htmlspecialchars($row['institution_name']);
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    if ($row['address'] !== $original_data['address']) {
                                        echo '<span style="color:red;">' . htmlspecialchars($row['address']) . '</span>';
                                    } else {
                                        echo htmlspecialchars($row['address']);
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    if ($row['phone'] !== $original_data['phone']) {
                                        echo '<span style="color:red;">' . htmlspecialchars($row['phone']) . '</span>';
                                    } else {
                                        echo htmlspecialchars($row['phone']);
                                    }
                                    ?>
                                </td>
                                <td><a href="<?php echo htmlspecialchars($row['website']); ?>" target="_blank">網站</a></td>
                                <td>
                                    <button class="info-btn"
                                        onclick="showModal('<?php echo htmlspecialchars($row['mistake_id']); ?>')">查看營業時間</button>
                                </td>
                                <td>
                                    <button class="pending-btn"
                                    onclick="window.location.href='edit_institution_1.php?institution_id=<?php echo urlencode($row['institution_id']); ?>'">待審核</button>
                                </td>
                                <td>
                                    <div class="button-group">
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

 

    <!-- 模態框 -->
    <div id="exampleModal" class="modal fade s3-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">詳細資訊</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- 內容會由 JavaScript 和後端 Ajax 動態填充 -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="reject-button"
                        data-dismiss="modal">退回申請</button>
                    <button type="button" class="btn btn-primary" id="approve-button"
                        data-institution-id="123">審核通過</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showModal(mistakeId) {
            fetch('get_service_time.php?mistake_id=' + mistakeId)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    let content = '<ul>';
                    data.forEach(item => {
                        content += `<li>${item.day}: ${item.open_time} - ${item.close_time}</li>`;
                    });
                    content += '</ul>';
                    document.getElementById('exampleModal').querySelector('.modal-body').innerHTML = content;
                    $('#exampleModal').modal('show');
                })
                .catch(error => {
                    console.error('Error fetching service times:', error);
                    document.getElementById('exampleModal').querySelector('.modal-body').innerHTML = '<p>無法獲取營業時間資料。</p>';
                    $('#exampleModal').modal('show');
                });
        }
    </script>

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

// 獲取營業時間資料
function getServiceTimes($mistake_id)
{
    global $link;
    $sql = "SELECT `day`, `open_time`, `close_time` FROM `mistake_servicetime` WHERE `mistake_id` = '$mistake_id'";
    $result = mysqli_query($link, $sql);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}
?>
