<?php include 'db.php';

// 設定每頁顯示的記錄數
$records_per_page = 5;

// 獲取查詢參數
$hospital_search_query = isset($_GET['hospital_search_query']) ? mysqli_real_escape_string($link, $_GET['hospital_search_query']) : '';
$patient_search_query = isset($_GET['patient_search_query']) ? mysqli_real_escape_string($link, $_GET['patient_search_query']) : '';
$caregiver_search_query = isset($_GET['caregiver_search_query']) ? mysqli_real_escape_string($link, $_GET['caregiver_search_query']) : '';
$manager_search_query = isset($_GET['manager_search_query']) ? mysqli_real_escape_string($link, $_GET['manager_search_query']) : '';
$current_page1 = isset($_GET['page1']) && is_numeric($_GET['page1']) ? (int) $_GET['page1'] : 1;
$current_page2 = isset($_GET['page2']) && is_numeric($_GET['page2']) ? (int) $_GET['page2'] : 1;
$current_page3 = isset($_GET['page3']) && is_numeric($_GET['page3']) ? (int) $_GET['page3'] : 1;
$current_page4 = isset($_GET['page4']) && is_numeric($_GET['page4']) ? (int) $_GET['page4'] : 1;
$type = isset($_GET['type']) ? $_GET['type'] : 'hospital'; // 預設為顯示醫療機構

if ($type === 'hospital') {
    // 醫療機構部分
    $sql_count = "SELECT COUNT(*) as total FROM hospital";
    if ($hospital_search_query) {
        $sql_count .= " WHERE account LIKE '%$hospital_search_query%' OR 
                        institution_name LIKE '%$hospital_search_query%' OR 
                        institution_address LIKE '%$hospital_search_query%' OR 
                        institution_phone LIKE '%$hospital_search_query%' OR 
                        institution_id LIKE '%$hospital_search_query%'";
    }
    $result_count = mysqli_query($link, $sql_count);
    $total_records = mysqli_fetch_assoc($result_count)['total'];
    $total_pages1 = ceil($total_records / $records_per_page);
    $offset1 = ($current_page1 - 1) * $records_per_page;

    $sql = "SELECT h.hospital_id, h.account, h.institution_id, h.institution_name, 
                   h.institution_address, h.institution_phone, h.status, u.register_time 
            FROM hospital h 
            JOIN user u ON h.account = u.account";
    if ($hospital_search_query) {
        $sql .= " WHERE h.account LIKE '%$hospital_search_query%' 
                  OR h.institution_name LIKE '%$hospital_search_query%' 
                  OR h.institution_address LIKE '%$hospital_search_query%' 
                  OR h.institution_phone LIKE '%$hospital_search_query%' 
                  OR h.institution_id LIKE '%$hospital_search_query%'";
    }
    $sql .= " ORDER BY FIELD(h.status, 2, 0, 1) ASC, u.register_time ASC 
          LIMIT $offset1, $records_per_page";
    $result = mysqli_query($link, $sql);
    ?>

    <!-- 醫療機構頁面標題與搜尋欄 -->
    <header class="custom-header">
        <h1>醫療機構</h1>
        <div class="custom-controls">
            <form method="GET" action="" id="search-form">
                <input type="hidden" name="type" value="hospital">
                <input type="text" name="hospital_search_query" placeholder="搜尋醫療機構..." class="search-input"
                    value="<?php echo htmlspecialchars($hospital_search_query); ?>">
                <button type="submit" class="search-button">搜尋</button>
            </form>
        </div>
    </header>

    <div class="custom-alert">
        Review your payroll data carefully before closing it.
    </div>

    <!-- 醫療機構表格內容 -->
    <div class="custom-payroll-table-container">
        <?php
        if ($result->num_rows > 0) {
            echo '<table class="custom-payroll-table">';
            echo '<thead><tr><th>帳號</th><th>醫療機構名稱</th><th>醫療機構代碼</th><th>醫療機構地址</th>
                      <th>醫療機構電話</th><th>申請時間</th><th>審核狀態</th></tr></thead>';
            echo '<tbody>';
            while ($row = $result->fetch_assoc()) {
                echo '<tr>';
                echo '<td class="account-cell" data-account="' . htmlspecialchars($row['account']) . '">' . htmlspecialchars($row['account']) . '</td>';
                echo '<td class="institution-name-cell">' . htmlspecialchars($row['institution_name']) . '</td>';
                echo '<td class="institution-id-cell">' . htmlspecialchars($row['institution_id']) . '</td>';
                echo '<td class="address-cell">' . htmlspecialchars($row['institution_address']) . '</td>';
                echo '<td class="institution-phone-cell">' . htmlspecialchars($row['institution_phone']) . '</td>';
                echo '<td class="register-time-cell">' . htmlspecialchars($row['register_time']) . '</td>';
                echo '<td class="status-cell">';
                echo ($row['status'] == 1) ? '審核通過' :
                    (($row['status'] == 2) ? '<button type="button" id="insert-hospital-data" class="data-button" data-toggle="modal" 
                             data-target="#dataModal" data-institution-id="' . $row['institution_id'] . '" 
                             data-institution-name="' . htmlspecialchars($row['institution_name']) . '">資料填寫</button>'
                        : '<button type="button" class="custom-button" data-toggle="modal" 
                              data-target="#exampleModal" data-institution-id="' . $row['institution_id'] . '" 
                              data-institution-name="' . htmlspecialchars($row['institution_name']) . '">待審核</button>');
                echo '</td></tr>';
            }
            echo '</tbody></table>';
        } else {
            echo "沒有資料可顯示";
        }
        ?>
    </div>

    <!-- 醫療機構分頁導航 -->
    <div class="custom-pagination">
        <div class="pagination-info">
            <span>顯示第 <?php echo ($offset1 + 1); ?> 到 <?php echo min($offset1 + $records_per_page, $total_records); ?>
                筆，<br>
                共<?php echo $total_records; ?>筆資料</span>
        </div>

        <div class="pagination-buttons">
            <?php
            $base_url1 = "?type=hospital";
            if ($hospital_search_query) {
                $base_url1 .= "&hospital_search_query=" . urlencode($hospital_search_query);
            }
            $base_url1 .= "&page1=";

            // 「首頁」按鈕
            if ($current_page1 > 1) {
                echo '<a href="' . $base_url1 . '1"><button>首頁</button></a>';
            } else {
                echo '<button class="disabled">首頁</button>';
            }

            // 「上一頁」按鈕
            if ($current_page1 > 1) {
                echo '<a href="' . $base_url1 . ($current_page1 - 1) . '"><button>&lt;</button></a>';
            } else {
                echo '<button class="disabled">&lt;</button>';
            }

            // 分頁顯示
            for ($i = 1; $i <= $total_pages1; $i++) {
                if ($i == $current_page1) {
                    echo '<button class="active">' . $i . '</button>';
                } else {
                    echo '<a href="' . $base_url1 . $i . '"><button>' . $i . '</button></a>';
                }
            }

            // 「下一頁」按鈕
            if ($current_page1 < $total_pages1) {
                echo '<a href="' . $base_url1 . ($current_page1 + 1) . '"><button>&gt;</button></a>';
            } else {
                echo '<button class="disabled">&gt;</button>';
            }

            // 「末頁」按鈕
            if ($current_page1 < $total_pages1) {
                echo '<a href="' . $base_url1 . $total_pages1 . '"><button>末頁</button></a>';
            } else {
                echo '<button class="disabled">末頁</button>';
            }
            ?>
        </div>

    </div>

    <?php
} else if ($type === 'patient') {
    // 患者部分
    $sql_count = "SELECT COUNT(*) as total FROM patient p 
                  JOIN user u ON p.account = u.account";
    if ($patient_search_query) {
        $sql_count .= " WHERE p.account LIKE '%$patient_search_query%' 
                        OR u.name LIKE '%$patient_search_query%'
                        OR p.birthday LIKE '%$patient_search_query%'
                        OR u.email LIKE '%$patient_search_query%'
                        OR u.phone LIKE '%$patient_search_query%'
                        OR u.address LIKE '%$patient_search_query%' 
                        OR p.emergency_name LIKE '%$patient_search_query%'
                        OR p.emergency_phone LIKE '%$patient_search_query%'";
    }
    $result_count = mysqli_query($link, $sql_count);
    $total_records = mysqli_fetch_assoc($result_count)['total'];
    $total_pages2 = ceil($total_records / $records_per_page);
    $offset2 = ($current_page2 - 1) * $records_per_page;

    $sql = "SELECT p.patient_id, p.account, u.name, p.birthday, u.email, u.sex, u.phone, u.address, 
            u.email_status, p.emergency_name, p.emergency_phone 
            FROM patient p 
            JOIN user u ON p.account = u.account";
    if ($patient_search_query) {
        $sql .= " WHERE p.account LIKE '%$patient_search_query%'
                  OR u.name LIKE '%$patient_search_query%'
                  OR p.birthday LIKE '%$patient_search_query%'
                  OR u.email LIKE '%$patient_search_query%'
                  OR u.phone LIKE '%$patient_search_query%'
                  OR u.address LIKE '%$patient_search_query%'
                  OR p.emergency_name LIKE '%$patient_search_query%'
                  OR p.emergency_phone LIKE '%$patient_search_query%'";
    }
    $sql .= " ORDER BY u.name ASC LIMIT $offset2, $records_per_page";
    $result = mysqli_query($link, $sql);
    ?>

        <!-- 患者頁面標題與搜尋欄 -->
        <header class="custom-header">
            <h1>患者</h1>
            <div class="custom-controls">
                <form method="GET" action="" id="search-form">
                    <input type="hidden" name="type" value="patient">
                    <input type="text" name="patient_search_query" placeholder="搜尋患者..." class="search-input"
                        value="<?php echo htmlspecialchars($patient_search_query); ?>">
                    <button type="submit" class="search-button">搜尋</button>
                </form>
            </div>
        </header>

        <div class="custom-alert">
            Review your payroll data carefully before closing it.
        </div>

        <!-- 患者表格內容 -->
        <div class="custom-payroll-table-container">
            <?php
            if ($result->num_rows > 0) {

                echo '<table class="custom-payroll-table">';
                echo '<thead><tr>
                <th class="account-cell">帳號</th>
                <th class="name-cell">姓名</th>
                <th class="sex-cell">性別</th>
                <th class="birthday-cell">出生日期</th>
                <th class="phone-cell">電話</th>
                <th class="email-cell">電子郵件</th>
                <th class="address-cell">住址</th>
                <th class="emergency-name-cell">緊急聯絡人</th>
                <th class="emergency-phone-cell">緊急聯絡人電話</th>
              </tr></thead>';
                echo '<tbody>';

                while ($row = $result->fetch_assoc()) {
                    echo '<tr>';
                    echo '<td class="account-cell" data-account="' . htmlspecialchars($row['account']) . '">' . htmlspecialchars($row['account']) . '</td>';
                    echo '<td class="name-cell">' . htmlspecialchars($row['name']) . '</td>';
                    
                    echo '<td class="sex-cell">' . ($row['sex'] === 'M' ? '男' : ($row['sex'] === 'F' ? '女' : '未知')) . '</td>';
                    echo '<td class="birthday-cell">' . htmlspecialchars($row['birthday']) . '</td>';
                    echo '<td class="phone-cell">' . htmlspecialchars($row['phone']) . '</td>';
                    echo '<td class="email-cell">' . htmlspecialchars($row['email']) . '</td>';
                    echo '<td class="address-cell">' . htmlspecialchars($row['address']) . '</td>';
                    echo '<td class="emergency-name-cell">' . htmlspecialchars($row['emergency_name']) . '</td>';
                    echo '<td class="emergency-phone-cell">' . htmlspecialchars($row['emergency_phone']) . '</td>';
                    echo '</tr>';
                }

                echo '</tbody></table>';
            } else {
                echo "沒有資料可顯示";
            }
            ?>
        </div>


        <!-- 患者分頁導航 -->
        <div class="custom-pagination">
            <div class="pagination-info">
                <span>顯示第 <?php echo ($offset2 + 1); ?> 到 <?php echo min($offset2 + $records_per_page, $total_records); ?>
                    筆，<br>
                    共<?php echo $total_records; ?>筆資料</span>
            </div>

            <div class="pagination-buttons">
                <?php
                $base_url2 = "?type=patient";
                if ($patient_search_query) {
                    $base_url2 .= "&patient_search_query=" . urlencode($patient_search_query);
                }
                $base_url2 .= "&page2=";

                // 「首頁」按鈕
                if ($current_page2 > 1) {
                    echo '<a href="' . $base_url2 . '1"><button>首頁</button></a>';
                } else {
                    echo '<button class="disabled">首頁</button>';
                }

                // 「上一頁」按鈕
                if ($current_page2 > 1) {
                    echo '<a href="' . $base_url2 . ($current_page2 - 1) . '"><button>&lt;</button></a>';
                } else {
                    echo '<button class="disabled">&lt;</button>';
                }

                // 分頁顯示
                for ($i = 1; $i <= $total_pages2; $i++) {
                    if ($i == $current_page2) {
                        echo '<button class="active">' . $i . '</button>';
                    } else {
                        echo '<a href="' . $base_url2 . $i . '"><button>' . $i . '</button></a>';
                    }
                }

                // 「下一頁」按鈕
                if ($current_page2 < $total_pages2) {
                    echo '<a href="' . $base_url2 . ($current_page2 + 1) . '"><button>&gt;</button></a>';
                } else {
                    echo '<button class="disabled">&gt;</button>';
                }

                // 「末頁」按鈕
                if ($current_page2 < $total_pages2) {
                    echo '<a href="' . $base_url2 . $total_pages2 . '"><button>末頁</button></a>';
                } else {
                    echo '<button class="disabled">末頁</button>';
                }
                ?>
            </div>

        </div>

    <?php
} else if ($type === 'caregiver') {
    // 照護者部分的記錄計數查詢
    $sql_count = "SELECT COUNT(*) as total FROM caregiver c JOIN user u ON c.account = u.account";
    if ($caregiver_search_query) {
        $sql_count .= " WHERE c.account LIKE '%$caregiver_search_query%'
        OR u.name LIKE '%$caregiver_search_query%'
        OR u.email LIKE '%$caregiver_search_query%'
        OR u.phone LIKE '%$caregiver_search_query%'
        OR u.address LIKE '%$caregiver_search_query%'";
    }
    $result_count = mysqli_query($link, $sql_count);
    $total_records = mysqli_fetch_assoc($result_count)['total'];
    $total_pages3 = ceil($total_records / $records_per_page);
    $offset2 = ($current_page3 - 1) * $records_per_page;

    // 照護者查詢
    $sql = "SELECT u.account, u.name, u.email, u.sex, u.phone, u.address, u.email_status
            FROM caregiver c JOIN user u ON c.account = u.account";
    if ($caregiver_search_query) {
        $sql .= " WHERE c.account LIKE '%$caregiver_search_query%'
        OR u.name LIKE '%$caregiver_search_query%'
        OR u.email LIKE '%$caregiver_search_query%'
        OR u.phone LIKE '%$caregiver_search_query%'
        OR u.address LIKE '%$caregiver_search_query%'";
    }
    $sql .= " ORDER BY u.name ASC LIMIT $offset2, $records_per_page";
    $result = mysqli_query($link, $sql);
    ?>

            <!-- 照護者頁面標題與搜尋欄 -->
            <header class="custom-header">
                <h1>照護者</h1>
                <div class="custom-controls">
                    <form method="GET" action="" id="search-form">
                        <input type="hidden" name="type" value="caregiver">
                        <input type="text" name="caregiver_search_query" placeholder="搜尋照護者..." class="search-input"
                            value="<?php echo htmlspecialchars($caregiver_search_query); ?>">
                        <button type="submit" class="search-button">搜尋</button>
                    </form>
                </div>
            </header>

            <div class="custom-alert">
                Review your payroll data carefully before closing it.
            </div>

            <!-- 照護者表格內容 -->
            <div class="custom-payroll-table-container">
            <?php
            if ($result->num_rows > 0) {
                echo '<table class="custom-payroll-table">';
                echo '<thead><tr><th>帳號</th><th>姓名</th><th>性別</th><th>電話</th><th>電子郵件</th><th>住址</th><th>電子郵件驗證狀態</th></tr></thead>';
                echo '<tbody>';
                while ($row = $result->fetch_assoc()) {
                    echo '<tr>';
                    echo '<td class="account-cell" data-account="' . htmlspecialchars($row['account']) . '">' . htmlspecialchars($row['account']) . '</td>';
                    echo '<td class="name-cell">' . htmlspecialchars($row['name']) . '</td>';
                    echo '<td class="sex-cell2">' . ($row['sex'] === 'M' ? '男' : ($row['sex'] === 'F' ? '女' : '未知')) . '</td>';
                    echo '<td class="phone-cell2">' . htmlspecialchars($row['phone']) . '</td>';
                    echo '<td class="email-cell2">' . htmlspecialchars($row['email']) . '</td>';
                    echo '<td class="address-cell2">' . htmlspecialchars($row['address']) . '</td>';
                    $emailStatus = ($row['email_status'] == 1) ? '已驗證' : '未驗證';
                    echo '<td>' . htmlspecialchars($emailStatus) . '</td>';
                    echo '</tr>';

                }
                echo '</tbody></table>';
            } else {
                echo "沒有資料可顯示";
            }
            ?>
            </div>

            <!-- 照護者分頁導航 -->
            <div class="custom-pagination">
                <div class="pagination-info">
                    <span>顯示第 <?php echo ($offset2 + 1); ?> 到 <?php echo min($offset2 + $records_per_page, $total_records); ?>
                        筆，<br>
                        共<?php echo $total_records; ?>筆資料</span>
                </div>

                <div class="pagination-buttons">
                <?php
                $base_url2 = "?type=caregiver";
                if ($caregiver_search_query) {
                    $base_url2 .= "&caregiver_search_query=" . urlencode($caregiver_search_query);
                }
                $base_url2 .= "&page3=";

                // 「首頁」按鈕
                if ($current_page3 > 1) {
                    echo '<a href="' . $base_url2 . '1"><button>首頁</button></a>';
                } else {
                    echo '<button class="disabled">首頁</button>';
                }

                // 「上一頁」按鈕
                if ($current_page3 > 1) {
                    echo '<a href="' . $base_url2 . ($current_page3 - 1) . '"><button>&lt;</button></a>';
                } else {
                    echo '<button class="disabled">&lt;</button>';
                }

                // 分頁顯示
                for ($i = 1; $i <= $total_pages3; $i++) {
                    if ($i == $current_page3) {
                        echo '<button class="active">' . $i . '</button>';
                    } else {
                        echo '<a href="' . $base_url2 . $i . '"><button>' . $i . '</button></a>';
                    }
                }

                // 「下一頁」按鈕
                if ($current_page3 < $total_pages3) {
                    echo '<a href="' . $base_url2 . ($current_page3 + 1) . '"><button>&gt;</button></a>';
                } else {
                    echo '<button class="disabled">&gt;</button>';
                }

                // 「末頁」按鈕
                if ($current_page3 < $total_pages3) {
                    echo '<a href="' . $base_url2 . $total_pages3 . '"><button>末頁</button></a>';
                } else {
                    echo '<button class="disabled">末頁</button>';
                }
                ?>
                </div>

            </div>

    <?php
} else if ($type === 'manager') {
    // 管理者部分的記錄計數查詢
    $sql_count = "SELECT COUNT(*) as total FROM user WHERE user_type='admin'";
    if ($manager_search_query) {
        $sql_count .= " AND (account LIKE '%$manager_search_query%'
            OR name LIKE '%$manager_search_query%'
            OR email LIKE '%$manager_search_query%'
            OR phone LIKE '%$manager_search_query%'
            OR address LIKE '%$manager_search_query%')";
    }
    $result_count = mysqli_query($link, $sql_count);
    $total_records = mysqli_fetch_assoc($result_count)['total'];
    $total_pages3 = ceil($total_records / $records_per_page);
    $offset2 = ($current_page3 - 1) * $records_per_page;

    // 管理者查詢
    $sql = "SELECT * FROM user WHERE user_type='admin'";
    if ($manager_search_query) {
        $sql .= " AND (account LIKE '%$manager_search_query%'
            OR name LIKE '%$manager_search_query%'
            OR email LIKE '%$manager_search_query%'
            OR phone LIKE '%$manager_search_query%'
            OR address LIKE '%$manager_search_query%')";
    }
    $sql .= " ORDER BY name ASC LIMIT $offset2, $records_per_page";
    $result = mysqli_query($link, $sql);
    ?>

                <!-- 管理者頁面標題與搜尋欄 -->
                <header class="custom-header">
                    <h1>管理者</h1>
                    <div class="custom-controls">
                        <form method="GET" action="" id="search-form">
                            <input type="hidden" name="type" value="manager">
                            <input type="text" name="caregiver_search_query" placeholder="搜尋管理者..." class="search-input"
                                value="<?php echo htmlspecialchars($manager_search_query); ?>">
                            <button type="submit" class="search-button">搜尋</button>
                        </form>
                    </div>
                </header>

                <div class="custom-alert">
                    Review your payroll data carefully before closing it.
                </div>

                <!-- 管理者表格內容 -->
                <div class="custom-payroll-table-container">
            <?php
            if ($result->num_rows > 0) {
                echo '<table class="custom-payroll-table">';
                echo '<thead><tr><th>帳號</th><th>姓名</th><th>性別</th><th>電話</th><th>電子郵件</th><th>住址</th><th>電子郵件驗證狀態</th></tr></thead>';
                echo '<tbody>';
                while ($row = $result->fetch_assoc()) {
                    echo '<tr>';
                    echo '<td class="account-cell" data-account="' . htmlspecialchars($row['account']) . '">' . htmlspecialchars($row['account']) . '</td>';
                    echo '<td class="name-cell">' . htmlspecialchars($row['name']) . '</td>';
                    echo '<td class="sex-cell2">' . ($row['sex'] === 'M' ? '男' : ($row['sex'] === 'F' ? '女' : '未知')) . '</td>';
                    echo '<td class="phone-cell2">' . htmlspecialchars($row['phone']) . '</td>';
                    echo '<td class="email-cell2">' . htmlspecialchars($row['email']) . '</td>';
                    echo '<td class="address-cell2">' . htmlspecialchars($row['address']) . '</td>';
                    $emailStatus = ($row['email_status'] == 1) ? '已驗證' : '未驗證';
                    echo '<td>' . htmlspecialchars($emailStatus) . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
            } else {
                echo "沒有資料可顯示";
            }
            ?>
                </div>

                <!-- 管理者分頁導航 -->
                <div class="custom-pagination">
                    <div class="pagination-info">
                        <span>顯示第 <?php echo ($offset2 + 1); ?> 到 <?php echo min($offset2 + $records_per_page, $total_records); ?>
                            筆，<br>
                            共<?php echo $total_records; ?>筆資料</span>
                    </div>

                    <div class="pagination-buttons">
                <?php
                $base_url2 = "?type=manager";
                if ($manager_search_query) {
                    $base_url2 .= "&caregiver_search_query=" . urlencode($manager_search_query);
                }
                $base_url2 .= "&page4=";

                // 「首頁」按鈕
                if ($current_page3 > 1) {
                    echo '<a href="' . $base_url2 . '1"><button>首頁</button></a>';
                } else {
                    echo '<button class="disabled">首頁</button>';
                }

                // 「上一頁」按鈕
                if ($current_page3 > 1) {
                    echo '<a href="' . $base_url2 . ($current_page3 - 1) . '"><button>&lt;</button></a>';
                } else {
                    echo '<button class="disabled">&lt;</button>';
                }

                // 分頁顯示
                for ($i = 1; $i <= $total_pages3; $i++) {
                    if ($i == $current_page3) {
                        echo '<button class="active">' . $i . '</button>';
                    } else {
                        echo '<a href="' . $base_url2 . $i . '"><button>' . $i . '</button></a>';
                    }
                }

                // 「下一頁」按鈕
                if ($current_page3 < $total_pages3) {
                    echo '<a href="' . $base_url2 . ($current_page3 + 1) . '"><button>&gt;</button></a>';
                } else {
                    echo '<button class="disabled">&gt;</button>';
                }

                // 「末頁」按鈕
                if ($current_page3 < $total_pages3) {
                    echo '<a href="' . $base_url2 . $total_pages3 . '"><button>末頁</button></a>';
                } else {
                    echo '<button class="disabled">末頁</button>';
                }
                ?>
                    </div>

                </div>

    <?php
}



// 關閉資料庫連線
mysqli_close($link);
?>

<script>
    // 為當前選中的項目添加 active 樣式
    document.querySelectorAll('.custom-menu-item').forEach(function (item) {
        item.classList.remove('custom-active'); // 移除所有項目的 active 樣式
    });

    const currentPageType = '<?php echo htmlspecialchars($type); ?>'; // 確保 type 變數安全
    const activeItem = document.querySelector(`.custom-menu-item[data-page="${currentPageType}"]`);
    if (activeItem) {
        activeItem.classList.add('custom-active'); // 添加 active 樣式
    }
</script>