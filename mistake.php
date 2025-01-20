<?php
include "db.php";

// 查詢 institution_name 欄位資料
$sql = "SELECT institution_name, address, phone, website FROM institution";
$result = mysqli_query($link, $sql);

// 將資料存入一個陣列以便於 JavaScript 使用
$institutions = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $institutions[$row['institution_name']] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include "head.php"; ?>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f3f4f6;
        }

        .main-container {
            display: flex;
            flex-wrap: wrap;
            max-width: 90%;
            margin: auto;
            gap: 20px;
            padding-top: 80px;
        }

        .filter-section {
            flex: 0 0 25%;
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            min-width: 250px;
        }

        .filter-section h2 {
            text-align: center;
            color: #9dc7c9;
        }

        .filter-section .btn {
            width: 100%;
            margin-top: 10px;

        }

        .post-section {
            flex: 0 0 70%;
            /* 占據 70% 寬度 */
            padding: 10px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .post {
            margin-top: 15px;
            padding: 15px;
            background-color: #eef2f7;
            border-radius: 10px;
        }

        .post-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .post-header .author {
            font-weight: bold;
            color: #4a5764;
        }

        .post-header .timestamp {
            font-size: 12px;
            color: #6c7a87;
        }

        .post-content {
            margin-top: 10px;
            line-height: 1.6;
        }

        .post-image {
            margin-top: 10px;
            background-color: #d9d9d9;
            width: 100%;
            height: 150px;
            border-radius: 8px;
        }

        .reply {
            margin-top: 20px;
            padding: 10px;
            background-color: #f6f7f9;
            border-radius: 8px;
        }

        .reply-header {
            display: flex;
            justify-content: flex-start;
            align-items: center;
        }

        .reply-header .author {
            font-weight: bold;
            color: #4a5764;
            margin-right: 10px;
        }

        .reply-header .timestamp {
            font-size: 12px;
            color: #6c7a87;
            margin-right: 10px;
            margin-left: auto;
        }

        .reply-header i {
            cursor: pointer;
            margin-left: 10px;
        }

        .reply-header .dropdown-menu {
            min-width: 150px;
        }

        .reply-content {
            margin-top: 10px;
            line-height: 1.6;
        }

        .reply-input {
            display: flex;
            width: 100%;
            gap: 10px;
            margin-top: 10px;
        }

        .reply-input textarea {
            flex-grow: 1;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            resize: none;
            margin-right: 10px;
        }

        .reply-input button {
            flex-shrink: 0;
            background-color: #5b8ba7;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 10px 20px;
            cursor: pointer;
        }

        .reply-input button:hover {
            background-color: #4a768c;
        }

        /* RWD: 當寬度小於 1000px 時，將篩選區內容移到貼文區，並調整為單欄 */
        @media (max-width: 1000px) {
            .main-container {
                display: block;
            }

            .filter-section {
                display: none;
            }

            .merged-section {
                display: grid;
                margin-bottom: 20px;
                background-color: transparent;
                border: none;
                box-shadow: none;
                padding: 0;
            }

            .filter-section,
            .merged-section {
                background-color: #fdfdfd;
                border: 1px solid #e0e0e0;
                border-radius: 8px;
                padding: 20px;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            }

            .filter-section h2,
            .merged-section h2 {
                font-size: 1.5em;
                color: #007b83;
                text-align: center;
                margin-bottom: 20px;
            }

            .filter-section form,
            .merged-section form {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }

            .filter-section input[type="text"],
            .merged-section input[type="text"] {
                padding: 10px;
                border: 1px solid #ccc;
                border-radius: 5px;
                width: 100%;
                box-sizing: border-box;
            }

            .filter-section .btn,
            .merged-section .btn {
                width: 100%;
                padding: 10px;
                border: none;
                border-radius: 5px;
                color: white;
                cursor: pointer;
            }

            .filter-section .btn-primary,
            .merged-section .btn-primary {
                background-color: #cebca6;
                transition: background-color 0.3s ease;
                margin-top: 10px;
            }

            .filter-section .btn-primary:hover,
            .merged-section .btn-primary:hover {
                background-color: #bda892;
            }

            .filter-section .btn[name="action"],
            .merged-section .btn[name="action"] {
                background-color: #9dc7c9;
                transition: background-color 0.3s ease;
            }

            .filter-section .btn[name="action"]:hover,
            .merged-section .btn[name="action"]:hover {
                background-color: #86afb0;
            }

        }

        .post-images {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }

        .post-image {
            flex: 1 1 calc(33.333% - 10px);
            max-width: calc(33.333% - 10px);
            height: auto;
            border-radius: 8px;
            object-fit: cover;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        @media (max-width: 768px) {
            .post-image {
                flex: 1 1 100%;
                max-width: 100%;
            }
        }
    </style>
    <script>
        // 將 PHP 陣列轉換為 JavaScript 物件
        const institutions = <?php echo json_encode($institutions); ?>;

        function fillInstitutionData() {
            const institutionName = document.getElementById('institution_name').value;
            if (institutions[institutionName]) {
                const data = institutions[institutionName];
                document.getElementById('address').value = data.address;
                document.getElementById('phone').value = data.phone;
                document.getElementById('website').value = data.website;
            } else {
                // 清空輸入框
                document.getElementById('address').value = '';
                document.getElementById('phone').value = '';
                document.getElementById('website').value = '';
            }
        }
        
    </script>
</head>

<body>
    <?php include "nav.php"; ?>

    <div class="s4-container">
        <div class="filter-section">
            <h2>回報錯誤</h2>
            <form action="" method="GET">
                <div class="mb-3" style="width:100%">
                    <input type="text" name="search_input" placeholder="請輸入關鍵字"
                        value="<?php echo isset($_GET['search_input']) ? htmlspecialchars($_GET['search_input']) : ''; ?>">
                    <input type="date" name="start_date" placeholder="活動日期"
                        value="<?php echo htmlspecialchars($start_date); ?>">
                </div>
                <div class="mb-3" style="width:100%">
                    <button type="submit" class="btn" name="action" value="search" style="background-color:#9dc7c9;">
                        <i class="fa fa-search"></i> 搜尋
                    </button>
                </div>
            </form>

            <?php
            $account = isset($_SESSION['account']) ? $_SESSION['account'] : '';
            if ($account): ?>
                <div class="mb-3">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"
                        style="background-color:#cebca6;">
                        <i class="fa-solid fa-plus"></i> 新增回報錯誤
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <div id="registrationRecords">
            <div class="container">
                <div class="main-container">


                    <!-- 新增回報錯誤的 Modal -->
                    <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="addModalLabel">新增回報錯誤</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form action="path/to/your/script.php" method="POST">
                                        <div class="mb-3">
                                            <label for="institution_name" class="form-label">醫療機構名稱</label>
                                            <input type="text" class="form-control" list="options0"
                                                id="institution_name" name="institution_name" placeholder="輸入機構名稱"
                                                required onchange="fillInstitutionData()">
                                            <datalist id="options0">
                                                <?php
                                                // 將資料庫中的資料填入 <option>
                                                foreach ($institutions as $name => $data) {
                                                    echo '<option>' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</option>';
                                                }
                                                ?>
                                            </datalist>
                                        </div>
                                        <div class="mb-3">
                                            <label for="address" class="form-label">地址</label>
                                            <input type="text" class="form-control" id="address" name="address"
                                                required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="phone" class="form-label">電話</label>
                                            <input type="tel" class="form-control" id="phone" name="phone" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="website" class="form-label">網站</label>
                                            <input type="url" class="form-control" id="website" name="website">
                                        </div>
                                        <div class="mb-3">
                                            <label for="servicetime" class="form-label">營業時間</label>
                                            <input type="text" class="form-control" id="servicetime" name="servicetime"
                                                readonly>
                                        </div>
                                        <!-- 隱藏回報日期時間的輸入框 -->
                                        <input type="hidden" id="reportDateTime" name="report_datetime">

                                        <div class="modal-footer">
                                            <button type="submit" class="btn btn-primary">送出回報</button>
                                            <button type="button" class="btn btn-secondary"
                                                data-bs-dismiss="modal">取消</button>
                                        </div>
                                      
                                    </form>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</body>

</html>