<?php
include 'db.php';
$institution_id = isset($_POST['institution_id']) ? $_POST['institution_id'] : '';

$business_hours = []; // 用來存儲營業時間數據

if (!empty($institution_id)) {
    // 準備 SQL 語句
    $stmt = $link->prepare("SELECT * FROM `servicetime` WHERE `institution_id` = ?");
    $stmt->bind_param("s", $institution_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $day = $row["day"]; // 獲取星期
            $open_time = $row["open_time"]; // 營業開始時間
            $close_time = $row["close_time"]; // 營業結束時間

            // 把每一天的營業時間存入對應的索引，變成多筆時間的陣列
            $business_hours[$day][] = [
                'open_time' => $open_time,
                'close_time' => $close_time
            ];
        }
    }
    $stmt->close();
    // 測試輸出：檢查 $business_hours 陣列是否正確
    echo "<script type='text/javascript'>console.log(" . json_encode($business_hours) . ");</script>";
} else {
    // 如果沒有找到任何營業時間
    echo "<script type='text/javascript'>console.log('No service time found');</script>";
}



$institution_name = $_POST['institution_name']; // institution_name 是必有值的
$county = isset($_POST['county']) ? $_POST['county'] : '';
$town = isset($_POST['town']) ? $_POST['town'] : '';
$address = isset($_POST['address']) ? $_POST['address'] : '';
$phone = isset($_POST['phone']) ? $_POST['phone'] : '';
$person_charge = isset($_POST['person_charge']) ? $_POST['person_charge'] : '';
$website = isset($_POST['website']) ? $_POST['website'] : '';
$lat = isset($_POST['lat']) ? $_POST['lat'] : '';
$lng = isset($_POST['lng']) ? $_POST['lng'] : '';

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    include 'head.php';
    ?>
    <style>
        .day {
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .left-side {
            display: flex;
            align-items: center;
        }

        .left-side i,
        .left-side label {
            margin-right: 10px;
        }

        .bi-toggle-off,
        .bi-toggle-on {
            cursor: pointer;
        }

        .time-settings {
            display: flex;
            align-items: center;
            display: none;
            margin-top: 5px;
        }

        .time-settings input[type="time"] {
            margin-left: 5px;
            margin-right: 5px;
        }

        .bi-toggle-off,
        .bi-toggle-on {
            font-size: 24px;
            color: #ff6347;
            cursor: pointer;
        }

        .bi-toggle-on {
            color: #32cd32;
        }

        .time-slot {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .time-slot input[type="time"] {
            margin-left: 5px;
            margin-right: 5px;
        }

        .bi-trash {
            color: red;
            /* 修改刪除圖標的顏色 */
            cursor: pointer;
            margin-left: 10px;
        }

        .bi-plus {
            cursor: pointer;
            color: #007bff;
            /* 修改新增圖標的顏色 */
        }

        hr {
            border: 1px solid #ccc;
            margin: 20px 0;
        }


        .address-container {
            display: flex !important;
            align-items: center;
            /* 垂直居中對齊 */
            justify-content: space-between;
            /* 在兩端對齊（可選） */
        }

        .address-container input {
            flex: 1;
            /* 讓輸入框自適應剩餘的空間 */
            margin-right: 10px;
            /* 與按鈕之間的間距 */
        }

        .address-container button {
            padding: 8px 12px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .address-container button:hover {
            background-color: #0056b3;
        }



        .search-input {
            width: 100%;
            padding-left: 35px;
            /* 給輸入框增加內邊距，避免被圖示覆蓋 */
            height: 40px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
        }

        .search-icon {
            position: absolute;
            left: 10px;
            display: flex;
            align-items: center;
            cursor: pointer;
        }

        .search-icon svg {
            width: 16px;
            height: 16px;
            fill: #878E96;
        }

        .input-container {
            display: flex;
            align-items: center;
            /* 對齊圖示與輸入框 */
            position: relative;
        }


        .search-icon {
            position: absolute;
            left: 10px;
            display: flex;
            align-items: center;
        }

        .latlng-popup {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            display: none;
            /* 初始為隱藏 */
        }

        .address-row {
            display: flex;
            flex-wrap: wrap;
            width: 100%;
            gap: 15px;
            margin-bottom: 10px;
        }

        .form-floating {
            flex: 1;
        }

        .form-floating.wide {
            flex: 1 1 100%;
        }

        .form-floating.small {
            min-width: 100px;
        }

        .btnnn {
            display: block;
            width: 100%;
            /* 填滿寬度 */
            padding: 10px;
            background-color: #6c757d;
            /* 按鈕背景顏色 - 灰色 */
            color: white;
            /* 文字顏色 */
            border: none;
            /* 無邊框 */
            border-radius: 5px;
            /* 圓角 */
            text-align: center;
            /* 文字居中 */
            font-size: 16px;
            /* 文字大小 */
            cursor: pointer;
            /* 鼠標指標為手形 */
        }

        .btnnn:hover {
            background-color: #5a6268;
            /* 懸停時的背景顏色 - 更深的灰色 */
        }


        .error-message {
            color: red;
            font-size: 14px;
            margin-top: 10px;
            display: none;
            /* 隱藏初始錯誤訊息 */
        }

        .spinner-border {
            width: 1em;
            height: 1em;
            border: 0.15em solid transparent;
            border-top-color: #B7B7B7;
            border-radius: 50%;
            animation: spin 0.75s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* 儲存按鈕的樣式 */
        .btn-save {
            display: block;
            width: 100%;
            padding: 10px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            text-align: center;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
        }

        .btn-save:hover {
            background-color: #218838;
        }

        .popover img {
            max-width: 100%;
            width: 250px;
            margin-bottom: 10px !important;
        }

        .day-settings {
            margin-top: 5px;
        }

        #loading-spinner {
            position: fixed;
            /* 使 Spinner 固定在螢幕上 */
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 9999;
            /* 確保 Spinner 在最上層 */
        }
    </style>
</head>

<body>
    <?php include "nav.php"; ?>
    <div class="hero hero-inner">
        <div class="container">
            <div class="row align-items-center justify-content-center">
                <div class="col-lg-8">
                    <div class="card p-4 shadow-sm">
                        <h1 class="mb-4 text-center" style="color: #000;">醫療機構資料</h1>
                        <form action="register_hospital2.php" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="account"
                                value="<?php echo htmlspecialchars($_POST['account']); ?>">
                            <input type="hidden" name="password"
                                value="<?php echo htmlspecialchars($_POST['password']); ?>">
                            <input type="hidden" name="email" value="<?php echo htmlspecialchars($_POST['email']); ?>">
                            <input type="hidden" name="name" value="<?php echo htmlspecialchars($_POST['name']); ?>">
                            <input type="hidden" name="sex" value="<?php echo htmlspecialchars($_POST['sex']); ?>">
                            <input type="hidden" name="phone" value="<?php echo htmlspecialchars($_POST['phone']); ?>">
                            <input type="hidden" name="addressuser"
                                value="<?php echo htmlspecialchars($_POST['addressuser']); ?>">


                            <div class="form-group mb-4">
                                <label for="institution_name"><span style="color: red;">*</span> 醫療機構名稱</label>
                                <input type="text" class="form-control" id="institution_name" name="institution_name"
                                    value="<?php echo isset($_POST['institution_name']) ? htmlspecialchars($_POST['institution_name']) : ''; ?>"
                                    required>
                            </div>
                            <div class="form-group mb-4">
                                <label for="institution_id"><span style="color: red;">*</span> 醫療機構代碼</label>
                                <?php
                                $institution_id = isset($_POST['institution_id']) ? htmlspecialchars($_POST['institution_id']) : '';
                                $readonly = !empty($institution_id) ? 'readonly' : '';
                                $onblur = !empty($institution_id) ? '' : 'onblur="checkDuplicate()"';
                                ?>

                                <input type="text" class="form-control" id="institution_id" name="institution_id"
                                    value="<?php echo $institution_id; ?>" required <?php echo $readonly; ?> <?php echo $onblur; ?>>


                                <div id="duplicate_result" style="margin-top: 10px;"></div>
                            </div>


                            <div class="form-group mb-4">
                                <label><span style="color: red;">*</span> 醫療機構地址</label>
                                <div class="row">
                                    <!-- 縣市欄位 -->
                                    <div class="col-md-3">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="city" name="city"
                                                placeholder="縣市"
                                                value="<?php echo isset($_POST['county']) ? htmlspecialchars($_POST['county']) : ''; ?>"
                                                required>
                                            <label for="county">縣市</label>
                                        </div>
                                    </div>

                                    <!-- 鄉鎮市區欄位 -->
                                    <div class="col-md-3">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="town" name="town"
                                                placeholder="鄉鎮市區"
                                                value="<?php echo isset($_POST['town']) ? htmlspecialchars($_POST['town']) : ''; ?>"
                                                required>
                                            <label for="town">鄉鎮市區</label>
                                        </div>
                                    </div>

                                    <!-- 詳細地址欄位 -->
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <?php
                                            // 確認所有的地址信息是否存在
                                            $county = isset($_POST['county']) ? $_POST['county'] : '';
                                            $town = isset($_POST['town']) ? $_POST['town'] : '';
                                            $address = isset($_POST['address']) ? $_POST['address'] : '';

                                            // 刪除縣市和鄉鎮市區的部分
                                            $detailed_address = str_replace([$county, $town], '', $address);

                                            // 將處理後的詳細地址顯示在輸入框中
                                            ?>
                                            <input type="text" id="institution_address" class="form-control"
                                                placeholder="詳細地址" name="institution_address"
                                                value="<?php echo htmlspecialchars(trim($detailed_address)); ?>"
                                                required>
                                            <label for="town">詳細地址</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mb-4">
                                <label for="institution_address"><span style="color: red;">*</span> 地圖顯示 - 經緯度　</label>


                                <?php if (isset($_POST['lat']) && isset($_POST['lng']) && !empty($_POST['lat']) && !empty($_POST['lng'])): ?>
                                    <input type="text" id="complete-address" class="form-control" name="institution_latlng"
                                        value="<?php echo htmlspecialchars($_POST['lng']); ?>, <?php echo htmlspecialchars($_POST['lat']); ?>"
                                        readonly>
                                <?php else: ?>
                                    <div class="input-container">
                                        <span class="search-icon">
                                            <svg width="16" height="16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path fill-rule="evenodd" clip-rule="evenodd"
                                                    d="M6.458 11.872C3.444 11.872 1 9.438 1 6.436S3.444 1 6.458 1c3.015 0 5.459 2.434 5.459 5.436a5.398 5.398 0 01-1.114 3.291l3.97 3.954a.77.77 0 010 1.093.778.778 0 01-1.097 0l-3.978-3.962a5.449 5.449 0 01-3.24 1.06zm0-1.145c2.38 0 4.31-1.92 4.31-4.291 0-2.37-1.93-4.292-4.31-4.292S2.15 4.066 2.15 6.436s1.93 4.291 4.31 4.291z"
                                                    fill="#878E96"></path>
                                            </svg>
                                        </span>
                                        <input type="text" id="search-latlng" placeholder="搜尋經緯度" readonly required
                                            class="search-input" onclick="showLatLngSearchModal()">
                                    </div>
                                    <div id="latlng-result"></div>

                                <?php endif; ?>
                            </div>
                            <div class="form-group mb-4">
                                <label for="institution_phone"><span style="color: red;">*</span> 醫療機構電話</label>
                                <input type="text" class="form-control" id="institution_phone" name="institution_phone"
                                    value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>" required>
                            </div>
                            <div class="form-group mb-4">
                                <label for="person_charge">聯絡人</label>
                                <input type="text" id="complete-address" class="form-control" name="person_charge"
                                    value="<?php echo isset($_POST['person_charge']) ? htmlspecialchars($_POST['person_charge']) : ''; ?>">
                            </div>

                            <div class="form-group mb-4">
                                <label for="institution_url">網站</label>
                                <input type="text" class="form-control" id="institution_url" name="institution_url"
                                    value="<?php echo isset($website) ? htmlspecialchars($website) : ''; ?>">
                            </div>
                            <div class="form-group mb-4">
                                <label for="institution_phone"><span style="color: red;">*</span> 營業時間　</label><i
                                    class="bi bi-info-circle" tabindex="0" role="button" data-bs-toggle="popover"
                                    data-bs-trigger="focus" title="24小時營業-設定說明" data-bs-html="true"
                                    data-bs-content='請設定該天營業時間為<b>上午12:00 - 下午11:59</b>，以表示為24小時營業。'
                                    style="right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;"></i>
                                <div class="day-settings" id="day-settings">
                                    <?php
                                    // 定義一個包含所有天的陣列
                                    $days = [
                                        '星期一' => '星期一',
                                        '星期二' => '星期二',
                                        '星期三' => '星期三',
                                        '星期四' => '星期四',
                                        '星期五' => '星期五',
                                        '星期六' => '星期六',
                                        '星期日' => '星期日'
                                    ];

                                    // 遍歷每一天，動態生成 HTML
                                    foreach ($days as $key => $label) {
                                        // 確定當前這一天是否有多個時間段
                                        $day_business_hours = isset($business_hours[$key]) ? $business_hours[$key] : [];
                                        ?>
                                        <div class="day">
                                            <div class="left-side">
                                                <i class="bi <?php echo !empty($day_business_hours) ? 'bi-chevron-up' : 'bi-chevron-down'; ?>"
                                                    id="chevron-<?php echo $key; ?>"></i>
                                                <label><?php echo $label; ?></label>
                                            </div>
                                            <i class="bi bi-toggle-<?php echo !empty($day_business_hours) ? 'on' : 'off'; ?>"
                                                id="toggle-<?php echo htmlspecialchars($label); ?>"
                                                onclick="toggleBusiness('<?php echo htmlspecialchars($key); ?>')"></i>
                                        </div>

                                        <div class="time-settings" id="time-settings-<?php echo $key; ?>"
                                            style="<?php echo !empty($day_business_hours) ? '' : 'display: none;'; ?>">
                                            <div id="time-slots-<?php echo $key; ?>">
                                                <?php foreach ($day_business_hours as $index => $time) { ?>
                                                    <div class="time-slot"
                                                        id="time-slot-<?php echo $key; ?>-<?php echo $index; ?>">
                                                        <input type="time"
                                                            name="business_hours[<?php echo $key; ?>][<?php echo $index; ?>][open_time]"
                                                            value="<?php echo $time['open_time']; ?>" class="time-input">
                                                        -
                                                        <input type="time"
                                                            name="business_hours[<?php echo $key; ?>][<?php echo $index; ?>][close_time]"
                                                            value="<?php echo $time['close_time']; ?>" class="time-input">
                                                        <!-- 新增垃圾桶圖標 -->
                                                        <i class="bi bi-trash"
                                                            onclick="removeTimeSlot('<?php echo $key; ?>', '<?php echo $index; ?>')"
                                                            style="cursor: pointer;"></i>
                                                    </div>
                                                <?php } ?>
                                            </div>
                                            <span onclick="addTimeSlot('<?php echo $key; ?>')" style="cursor: pointer;"><i
                                                    class="bi bi-plus"></i> 新增時段</span>
                                        </div>
                                        <hr>
                                    <?php } ?>
                                </div>
                            </div>

                            <div class="form-group mb-4">
                                <label for="institution_img"><span style="color: red;">*</span>
                                    開業執照或評鑑合格證明書-相關證明　</label><i class="bi bi-info-circle" tabindex="0" role="button"
                                    data-bs-toggle="popover" data-bs-trigger="focus" title="相關證明-檔案上傳說明"
                                    data-bs-html="true" data-bs-content='證明上傳僅供系統核對身分，核對完成即可使用本系統。'
                                    style="right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;"></i>
                                <input class="form-control" type="file" id="institution_img" name="institution_img"
                                    accept="image/*" style="margin-top: 10px;" required>
                            </div>

                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-primary w-100" id="submit-button">
                                    送出
                                </button>
                            </div>




                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="latlng-search-modal" style="display:none;" class="latlng-popup">
        <div class="review-popup-content">
            <span onclick="closeLatLngSearchModal()" class="close-btn">&times;</span>
            <p class="review-title">搜尋經緯度</p>

            <div class="address-row">
                <div class="form-floating">
                    <input type="text" class="form-control" id="citytown" name="citytown" placeholder=" " required>
                    <label for="citytown">縣市</label>
                </div>
                <div class="form-floating">
                    <input type="text" class="form-control" id="district" name="district" placeholder=" " required>
                    <label for="district">鄉鎮市區</label>
                </div>
            </div>

            <div class="address-row">
                <div class="form-floating wide">
                    <input type="text" class="form-control" id="street" name="street" placeholder=" ">
                    <label for="street">路街名</label>
                </div>
            </div>

            <div class="address-row">
                <div class="form-floating small">
                    <input type="text" class="form-control" id="lane" name="lane" placeholder=" ">
                    <label for="lane">巷</label>
                </div>
                <div class="form-floating small">
                    <input type="text" class="form-control" id="alley" name="alley" placeholder=" ">
                    <label for="alley">弄</label>
                </div>
                <div class="form-floating small">
                    <input type="text" class="form-control" id="number" name="number" placeholder=" " required>
                    <label for="number">號</label>
                </div>
                <div class="form-floating small">
                    <input type="text" class="form-control" id="floor" name="floor" placeholder=" ">
                    <label for="floor">樓之</label>
                </div>
                <div class="form-floating small">
                    <input type="text" class="form-control" id="room" name="room" placeholder=" ">
                    <label for="room">室</label>
                </div>
            </div>
            <button type="button" id="searchBtn" onclick="setLatLng()" class="btnnn">
                <span id="loadingSpinner" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"
                    style="display: none;"></span>
                搜尋
            </button>

            <div id="error-message" class="error-message">找不到對應的經緯度</div>

            <div class="form-floating mb-4" id="latlngGroup" style="display: none; margin-top: 20px;">
                <input type="text" class="form-control" id="latlng" placeholder=" " style="padding-right: 30px;">
                <label for="latlng">地圖顯示緯度 (介於 20.5 至 25.5),經度 (介於 119.5 至 122.0)</label>
                <i class="bi bi-info-circle" tabindex="0" role="button" data-bs-toggle="popover" data-bs-trigger="focus"
                    title="地圖顯示 - 如何取得經緯度" data-bs-html="true" data-bs-content='<div>
                        <i class="bi bi-1-square"></i><span>　開啟<a href="https://www.google.com/maps" target="_blank">Google地圖</a>，將您的地標放至最大。</span><br>
                        <i class="bi bi-2-square"></i><span>　對著地標點擊滑鼠右鍵，顯示功能窗格。</span><br>
                        <i class="bi bi-3-square"></i><span>　點擊經緯度，即可成功複製。</span>
           <img src="images/map.png" alt="經緯度示例圖片" style="width: 50px !important; margin-bottom: 10px;">
       </div>' style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;"></i>
            </div>
            <button type="button" id="saveButton" class="btn-save" style="display: none;">儲存</button>
        </div>
    </div>




    <div class="site-footer">
        <div class="inner first">
            <div class="container">
                <div class="row">
                    <div class="col-md-6 col-lg-4">
                        <div class="widget">
                            <h3 class="heading">About Tour</h3>
                            <p>Far far away, behind the word mountains, far from the countries Vokalia and Consonantia,
                                there live the blind texts.</p>
                        </div>
                        <div class="widget">
                            <ul class="list-unstyled social">
                                <li><a href="#"><span class="icon-twitter"></span></a></li>
                                <li><a href="#"><span class="icon-instagram"></span></a></li>
                                <li><a href="#"><span class="icon-facebook"></span></a></li>
                                <li><a href="#"><span class="icon-linkedin"></span></a></li>
                                <li><a href="#"><span class="icon-dribbble"></span></a></li>
                                <li><a href="#"><span class="icon-pinterest"></span></a></li>
                                <li><a href="#"><span class="icon-apple"></span></a></li>
                                <li><a href="#"><span class="icon-google"></span></a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-2 pl-lg-5">
                        <div class="widget">
                            <h3 class="heading">Pages</h3>
                            <ul class="links list-unstyled">
                                <li><a href="#">Blog</a></li>
                                <li><a href="#">About</a></li>
                                <li><a href="#">Contact</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-2">
                        <div class="widget">
                            <h3 class="heading">Resources</h3>
                            <ul class="links list-unstyled">
                                <li><a href="#">Blog</a></li>
                                <li><a href="#">About</a></li>
                                <li><a href="#">Contact</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="widget">
                            <h3 class="heading">Contact</h3>
                            <ul class="list-unstyled quick-info links">
                                <li class="email"><a href="#">mail@example.com</a></li>
                                <li class="phone"><a href="#">+1 222 212 3819</a></li>
                                <li class="address"><a href="#">43 Raymouth Rd. Baltemoer, London 3910</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="inner dark">
            <div class="container">
                <div class="row text-center">
                    <div class="col-md-8 mb-3 mb-md-0 mx-auto">
                        <p>Copyright &copy;
                            <script>document.write(new Date().getFullYear());</script>. All Rights Reserved. &mdash;
                            Designed with love by <a href="https://untree.co" class="link-highlight">Untree.co</a>
                            <!-- License information: https://untree.co/license/ -->Distributed By
                            <a href="https://themewagon.com" target="_blank">ThemeWagon</a>
                        </p>
                    </div>

                </div>
            </div>
        </div>
    </div>


    <script>
        window.onload = function () {
            // 定義所有星期的名稱
            var days = ['星期一', '星期二', '星期三', '星期四', '星期五', '星期六', '星期日'];
            days.forEach(function (day) {
                var toggle = document.getElementById("toggle-" + day); // 獲取切換按鈕
                var timeSettings = document.getElementById("time-settings-" + day); // 獲取時間設置

                // 確保 toggle 和 timeSettings 不為 null
                if (toggle && timeSettings) {
                    // 如果切換按鈕是開啟狀態，顯示對應的時間設置
                    if (toggle.classList.contains("bi-toggle-on")) {
                        timeSettings.style.display = "block"; // 顯示時間設置
                    } else {
                        timeSettings.style.display = "none"; // 隱藏時間設置
                    }
                } else {
                    console.error("無法找到元素: ", day, toggle, timeSettings);
                }
            });
        };



        // 啟動 Bootstrap popover
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });

        let latlngInput, saveButton;

        document.querySelector('form').addEventListener('submit', function (event) {
            // 檢查經緯度是否有值
            const latLngInput = document.getElementById('search-latlng');
            const completeAddress = document.getElementById('complete-address');

            if ((!latLngInput || !latLngInput.value) && (!completeAddress || !completeAddress.value)) {
                alert('請輸入經緯度');
                event.preventDefault(); // 阻止表單送出
                return;
            }

            // 檢查是否有營業時間，且營業開始時間小於結束時間
            const openTimes = document.querySelectorAll('input[name*="[open_time]"]');
            const closeTimes = document.querySelectorAll('input[name*="[close_time]"]');

            for (let i = 0; i < openTimes.length; i++) {
                const openTime = openTimes[i].value;
                const closeTime = closeTimes[i].value;

                if (!openTime || !closeTime) {
                    alert('請填寫所有的營業時間');
                    event.preventDefault(); // 阻止表單送出
                    return;
                }
                console.log('開始', openTime)
                console.log('結束', closeTime)

                if (openTime > closeTime) {
                    alert('營業開始時間必須小於結束時間');
                    event.preventDefault(); // 阻止表單送出
                    return;
                }
            }

            const institutionImgInput = document.getElementById('institution_img');
            if (!institutionImgInput.files.length) {
                alert('請上傳相關證明檔案');
                event.preventDefault(); // 阻止表單送出
                return;
            }

            const institutionIdInput = document.getElementById('institution_id');
            const institutionIdValue = institutionIdInput.value;

            if (institutionIdValue) {
                // 如果 institution_id 有值，且已檢查過重複
                if (duplicateChecked) {
                    const duplicateResult = document.getElementById('duplicate_result').innerHTML;
                    if (duplicateResult.includes("此醫療機構代碼已存在。")) {
                        alert('醫療機構代碼重複，請檢查輸入，或向管理員詢問');
                        event.preventDefault(); // 阻止表單送出
                        return;
                    }
                }
            } else {
                if (!duplicateChecked) {
                    alert('請先查詢醫療機構代碼是否重複');
                    event.preventDefault(); // 阻止表單送出
                    return;
                }
            }

            const submitButton = document.getElementById('submit-button');
            submitButton.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>送出中...`;
            submitButton.disabled = true; // 禁用按鈕
            // 如果都通過檢查則送出表單
        });


        document.addEventListener('DOMContentLoaded', function () {
            latlngInput = document.getElementById("latlng");
            saveButton = document.getElementById("saveButton");

            // 監聽 input 的變化
            latlngInput.addEventListener('input', function () {
                const value = latlngInput.value.trim();
                saveButton.style.display = value ? "block" : "none"; // 顯示或隱藏儲存按鈕
            });

            // 儲存按鈕的點擊事件
            saveButton.addEventListener('click', function () {
                const value = latlngInput.value.trim();
                const [lat, lng] = value.split(',').map(Number);

                if (lat >= 20.5 && lat <= 25.5 && lng >= 119.5 && lng <= 122.0) {
                    const completeAddressInput = document.getElementById("search-latlng");
                    completeAddressInput.value = `${lng}, ${lat}`;
                    completeAddressInput.style.display = "block";


                    const searchIconContainer = document.querySelector('.input-container');
                    searchIconContainer.style.display = "none";

                    var result = document.getElementById('latlng-result');
                    result.innerHTML = '';


                    var newDiv = document.createElement('div');
                    newDiv.innerHTML = `
            <input type="text" class="form-control" name="institution_latlng" value="${latlngInput.value}" readonly style="display: inline-block; width: 70%;">
            <button type="button" class="btn btn-secondary" id="editButton" style="display: inline-block; margin-left: 10px;">編輯</button>`;

                    result.appendChild(newDiv);

                    const editButton = newDiv.querySelector("#editButton");
                    const inputField = newDiv.querySelector("input");


                    editButton.addEventListener('click', function () {
                        showLatLngSearchModal();
                    });


                    console.log("經緯度已儲存:", latlngInput.value);
                    closeLatLngSearchModal();
                } else {
                    alert("經緯度不在範圍內，請檢查輸入。");
                }
            });
        });

        // 顯示彈窗的函數
        function showLatLngSearchModal() {
            document.getElementById('latlng-search-modal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        // 關閉彈窗的函數
        function closeLatLngSearchModal() {
            document.getElementById('latlng-search-modal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function setLatLng() {
            const loadingSpinner = document.getElementById("loadingSpinner");
            loadingSpinner.style.display = "inline-block";

            const street = document.getElementById("street").value;
            const lane = document.getElementById("lane").value;
            const alley = document.getElementById("alley").value;
            const number = document.getElementById("number").value;
            const city = document.getElementById("district").value;
            const county = document.getElementById("citytown").value;

            // 檢查地址部分
            if (!city || !county || !number) {
                document.getElementById("error-message").textContent = "請填寫完整地址";
                document.getElementById("error-message").style.display = "block";
                loadingSpinner.style.display = "none";
                return;
            }

            // 拼接完整地址
            let fullStreet = street;

            if (lane) {
                fullStreet += lane.includes("巷") ? lane : lane + "巷";
            }
            if (alley) {
                fullStreet += alley.includes("弄") ? alley : alley + "弄";
            }
            if (!number.includes("號")) {
                fullStreet += " " + number + "號";
            } else {
                fullStreet += " " + number;
            }

            // Nominatim API 查詢 URL
            const url = `https://nominatim.openstreetmap.org/search?format=json&street=${encodeURIComponent(fullStreet)}&city=${encodeURIComponent(city)}&county=${encodeURIComponent(county)}&country=台灣`;

            // 查詢經緯度
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    loadingSpinner.style.display = "none";
                    document.getElementById("latlngGroup").style.display = "block";

                    if (data.length > 0) {
                        const latitude = data[0].lat;
                        const longitude = data[0].lon;

                        // 更新經緯度欄位
                        latlngInput.value = `${latitude}, ${longitude}`;
                        latlngInput.dispatchEvent(new Event('input')); // 手動觸發事件
                        document.getElementById("error-message").style.display = "none";
                        console.log(latlngInput.value)
                    } else {
                        document.getElementById("error-message").textContent = "找不到對應的經緯度，請手動輸入。";
                        document.getElementById("error-message").style.display = "block";
                        latlngInput.value = "";
                    }
                })
                .catch(error => {
                    loadingSpinner.style.display = "none";
                    console.error("Error:", error);
                    document.getElementById("error-message").textContent = "查詢過程中發生錯誤，請重新查詢或手動輸入";
                    document.getElementById("error-message").style.display = "block";
                    document.getElementById("latlngGroup").style.display = "block";
                    latlngInput.value = "";
                });
        }



        function removeTimeSlot(day, index) {
            // 找到對應的時間段 div 並移除
            var timeSlot = document.getElementById('time-slot-' + day + '-' + index);
            if (timeSlot) {
                timeSlot.remove();
            }
        }


        function toggleBusiness(day) {
            var toggle = document.getElementById("toggle-" + day);
            var timeSettings = document.getElementById("time-settings-" + day);
            console.log('time', day)
            var chevron = document.getElementById("chevron-" + day);  // 獲取對應的 chevron 圖標
            if (toggle.classList.contains("bi-toggle-off")) {
                // 切換到開啟狀態
                toggle.classList.remove("bi-toggle-off");
                toggle.classList.add("bi-toggle-on");
                timeSettings.style.display = "block";
                // 切換 chevron 向上的箭頭
                chevron.classList.remove("bi-chevron-down");
                chevron.classList.add("bi-chevron-up");
            } else {
                // 切換到關閉狀態
                toggle.classList.remove("bi-toggle-on");
                toggle.classList.add("bi-toggle-off");
                timeSettings.style.display = "none";
                // 切換 chevron 向下的箭頭
                chevron.classList.remove("bi-chevron-up");
                chevron.classList.add("bi-chevron-down");

                // 找到所有該日的時間段，並移除它們
                const timeSlots = document.getElementById('timeSlots');

                // 隱藏所有時間段元素，而不是刪除它們
                const children = timeSlots.children;
                for (let i = 0; i < children.length; i++) {
                    children[i].style.display = 'none';  // 設置每個子元素為隱藏
                }
            }
        }

        function addTimeSlot(day) {
            var timeSlots = document.getElementById("time-slots-" + day);
            var timeSlot = document.createElement("div");
            timeSlot.className = "time-slot";

            // 使用 Date().getTime() 作為唯一索引，避免重複
            var index = new Date().getTime();

            // 增加新的時間段輸入框，並增加垃圾桶圖標
            timeSlot.innerHTML = `
        <div id="time-slot-${day}-${index}">
            <input type="time" name="business_hours[${day}][${index}][open_time]" class="time-input"> - 
            <input type="time" name="business_hours[${day}][${index}][close_time]" class="time-input">
            <i class="bi bi-trash" onclick="removeTimeSlot('${day}', '${index}')" style="cursor: pointer;"></i>
        </div>
    `;

            timeSlots.appendChild(timeSlot);
        }





        let duplicateChecked = false;

        function checkDuplicate() {
            const institutionId = document.getElementById('institution_id').value;

            if (!institutionId) {
                alert("請輸入醫療機構代碼");
                return;
            }

            fetch("check_duplicate.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: "institution_id=" + encodeURIComponent(institutionId)
            })
                .then(response => response.text())
                .then(data => {
                    document.getElementById('duplicate_result').innerHTML = data;
                    duplicateChecked = true; // 標記為已檢查
                })
                .catch(error => {
                    console.error('There has been a problem with your fetch operation:', error);
                });
        }






        let isEditing = false;



    </script>

</body>

</html>