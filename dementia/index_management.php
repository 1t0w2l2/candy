<?php
include 'db.php';


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    include 'head.php';
    ?>
    <style>
        html,
        body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            flex-direction: column;
            min-height: 100vh;
        }

        .custom-container {
            display: flex;
            width: 100%;
            flex-grow: 1;
            height: calc(100% - 76px);
            margin-top:6%;
        }

        /* 左側選單 */
        .custom-sidebar {
            width: 250px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border-right: 1px solid #C7C2AB;
        }

        .custom-sidebar-logo h2 {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 28px;
            font-weight: bold;
            color: #6B4D38;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin: 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #cfb59e;
        }

        .custom-menu {
            list-style: none;
            padding: 0;
        }

        .custom-menu-item {
            padding: 10px;
            cursor: pointer;
            color: #6B4D38;
            transition: background-color 0.3s ease;
        }

        .custom-menu-item.custom-active {
            background-color: #C7C2AB;
            color: #fff;
        }

        .custom-menu-item:hover {
            background-color: #C7C2AB;
            color: #fff;

        }

        .custom-support {
            margin-top: auto;
            margin-bottom: 20px;
        }

        .custom-appearance {
            padding-top: 10px;
        }

        #custom-toggle-theme {
            padding: 10px;
            background-color: #C7C2AB;
            color: #6B4D38;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
            width: 100%;
            border-radius: 20px;
        }

        #custom-toggle-theme:hover {
            background-color: #cfb59e;
            color: #fff;
        }

        /* 右側表格區 */
        .custom-header h1 {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 28px;
            font-weight: bold;
            color: #6B4D38;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin: 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #cfb59e;
        }

        .custom-main-content {
            flex-grow: 1;
            /* 讓這部分自動擴展以填滿剩餘空間 */
            padding: 20px;
            overflow-y: auto;
            /* 添加滾動條以防止溢出 */
        }

        .custom-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* 搜尋欄位 */
        .search-input {
            padding: 8px;
            margin-right: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .search-button {
            background-color: #dee5ed;
            color: #333;
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .search-button:hover {
            background-color: #c0d3e4;
            /* 深一點的顏色 */
        }


        .custom-alert {
            margin: 20px 0;
            padding: 10px;
            background-color: #FFF8DC;
            border-left: 5px solid #FFD700;
        }

        .custom-payroll-table-container {
            display: flex;
            flex-direction: column;
            height: calc(100vh - 310px);
            overflow-y: auto;
            margin-bottom: 20px;
        }

        .custom-payroll-table {
            border-collapse: collapse;
            table-layout: fixed;
        }

        .custom-payroll-table tr {
            height: 9vh;
        }

        .custom-payroll-table th,
        .custom-payroll-table td {
            padding: 5px;
            text-align: center;
            border-bottom: 1px solid #ddd;
        }

        /* 為每個欄位指定不同的寬度 */
        .account-cell {
            width: 10%;
        }

        .institution-name-cell {
            width: 15%;
        }

        .institution-id-cell {
            width: 15%;
        }

        .name-cell {
            width: 10%;
        }

        .sex-cell {
            width: 5%;
        }

        .birthday-cell {
            width: 10%;
        }

        .phone-cell {
            width: 10%;
        }

        .email-cell {
            width: 15%;
        }

        .address-cell {
            width: 20%;
        }

        .sex-cell2 {
            width: 5%;
        }

        .phone-cell2 {
            width: 15%;
        }

        .email-cell2 {
            width: 20%;
        }

        .address-cell2 {
            width: 30%;
        }

        .emergency-name-cell {
            width: 10%;
        }

        .emergency-phone-cell {
            width: 10%;
        }





        .custom-status.custom-completed {
            background-color: #D4EDDA;
            color: #155724;
        }

        .custom-status.custom-need-setup {
            background-color: #FFF3CD;
            color: #856404;
        }

        .custom-main-content {
            flex-grow: 1;
            /* 自動擴展以填滿剩餘空間 */
            padding: 20px;
            overflow-y: auto;
            /* 添加滾動條以防止溢出 */
            display: flex;
            /* 使用 flexbox */
            flex-direction: column;
            /* 垂直方向 */
        }

        .custom-pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 20px;
        }


        .custom-button {
            background-color: #aba28a;
            color: white;
            border: none;
            padding: 10px 5px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            margin: 4px 2px;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .data-button {
            background-color: #8a93ab;
            color: white;
            border: none;
            padding: 10px 5px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            margin: 4px 2px;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        /* 表格 */
        #modal-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 16px;
            text-align: left;
            background-color: #f9f9f9;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        #modal-table th,
        #modal-table td {
            padding: 12px 15px;
            border: 1px solid #ddd;
        }

        #modal-table th {
            background-color: #555;
            color: white;
            text-transform: uppercase;
            font-weight: bold;
        }

        #modal-table td {
            background-color: #ffffff;
            color: #333;
        }

        #modal-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        #modal-table tr:hover {
            background-color: #e2e6ea;
            transition: background-color 0.3s ease;
        }

        #modal-table td:first-child {
            border-left: none;
        }

        #modal-table td:last-child {
            border-right: none;
        }

        .modal-header {
            background-color: #aba28a;
            color: white;
        }

        /* Secondary (退回申請) button style */
        .modal-footer .btn-secondary {
            background-color: #ce7360;
            border: none;
            color: white;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        /* Hover effect for secondary button */
        .modal-footer .btn-secondary:hover {
            background-color: #b86250;
            color: #f8f9fa;
        }

        /* Primary (審核通過) button style */
        .modal-footer .btn-primary {
            background-color: #495057;
            border: none;
            color: white;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        /* Hover effect for primary button */
        .modal-footer .btn-primary:hover {
            background-color: #343a40;
            color: #f8f9fa;
        }

        /* 關閉按鈕樣式 */
        button.close {
            background-color: transparent;
            border: none;
            font-size: 1.5rem;
            color: #fff;
            cursor: pointer;
            outline: none;
            padding: 5px 10px;
            transition: color 0.3s ease;
        }

        button.close:hover {
            color: #555;
        }

        /* 頁碼 */
        .pagination-info {
            flex: 1;
            text-align: left;
            font-size: 14px;
            color: #555;
        }

        .pagination-buttons {
            flex: 6;
            text-align: center;
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

        .custom-pagination span {
            color: #999;
            font-size: 14px;
        }

        * {
            box-sizing: border-box;
            /* 確保所有元素的邊距和邊框不影響總寬度 */
        }

        /* 帳號資訊表格樣式 */
        .account-info-table {
            margin-bottom: 20px;
            border-collapse: collapse;
            width: 100%;
        }

        .account-info-table th,
        .account-info-table td {
            border: 1px solid #dee2e6;
            padding: 8px;
            text-align: left;
        }

        .account-info-table th {
            background-color: #f8f9fa;
        }

        /* 帳號單元格 hover 效果 */
        .account-cell {
            transition: background-color 0.3s ease;
        }

        .account-cell:hover {
            background-color: #f0f0f0;
            cursor: pointer;
            color: #1A374D;
        }

        .modal.fade .modal-dialog {
            transition: transform 0.3s ease-out;
        }

        .btn-full-width {
            width: 100%;
            background-color: #1A374D;
            color: #fff;
            border: none;
            padding: 10px 0;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            border-radius: 5px;
        }

        .btn-full-width:hover {
            background-color: #406882;
        }

        /* 醫療機構新增資料 */
        /* 設置地址輸入行的樣式 */
        .address-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }

        .address-row .form-floating {
            flex: 1;
        }

        .address-row .wide {
            flex: 100%;
        }

        .address-row .small {
            flex: 1 1 18%;
        }

        .btnnn {
            width: 100%;
            background-color: #dee5ed;
            border: none;
            color: #333;
            padding: 10px;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            margin-bottom: 15px;
            transition: background-color 0.3s;
        }

        .btnnn:hover {
            background-color: #cfd8e3;
        }

        /* 錯誤訊息樣式設置 */
        .error-message {
            color: red;
            font-size: 0.9rem;
            display: none;
            margin-bottom: 10px;
        }

        .popover img {
            width: 250px !important;
            margin-bottom: 10px;
        }

        /* 營業時間 */
        /* 使 .day 元素在同一列顯示 */
        .day {
            display: flex;
            align-items: center;
            /* 垂直居中 */
            justify-content: space-between;
            /* 在水平方向上分開 */
            margin-bottom: 10px;
            /* 每個 .day 元素之間的間隔 */
        }

        /* 設定 .left-side 的顏色和間距 */
        .day .left-side {
            display: flex;
            align-items: center;
            /* 使內部內容垂直居中 */
        }

        .day .left-side i {
            margin-right: 10px;
            /* 調整 chevron 標籤與文字的間距 */
        }

        .bi-toggle-off,
        .bi-toggle-on {
            font-size: 24px;
            color: #ff6347;
            cursor: pointer;
        }

        .bi-toggle-on {
            color: #32cd32;
            /* 開啟時顯示綠色 */
        }

        .bi-toggle-off {
            color: #dc3545;
        }

        .bi-plus {
            cursor: pointer;
            color: #007bff;
            /* 修改新增圖標的顏色 */
        }

        /* 為每個時間段的容器設置基本樣式 */
        .time-slot {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            padding: 5px 0;
        }

        /* 時間段的輸入框 */
        .time-input {
            width: 120px;
            margin: 5px;
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        /* 垃圾桶圖標 */
        .bi-trash {
            color: #dc3545;
            margin-left: 10px;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .bi-trash:hover {
            color: #bb2d3b;
        }

        /* 帳號編輯刪除按鈕 */
        /* Action Buttons Layout */
        .action-buttons {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .action-btn {
            width: 48%;
            /* 兩個按鈕佔同一列並各佔48%寬度 */
            padding: 10px;
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            cursor: pointer;
        }

        .edit-btn {
            background-color: #dee5ed;
            /* 淺藍 */
            color: #6B4D38;
            /* 深咖啡 */
            border: none;
            border-radius: 4px;
        }

        .delete-btn {
            background-color: #cfb59e;
            /* 淺咖啡 */
            color: white;
            border: none;
            border-radius: 4px;
        }

        /* Button Hover Effects */
        .edit-btn:hover,
        .submit-btn:hover {
            background-color: #adbed2;
            color: #fff;
        }

        .delete-btn:hover {
            background-color: #6B4D38;
            color: #fff;
        }

        /* Submit and Cancel Button Styling */
        .submit-btn {
            background-color: #dee5ed;
            color: #6B4D38;
            border: none;
            border-radius: 4px;
            width: 48%;
            padding: 10px;
            font-size: 16px;
            font-weight: bold;
        }

        .cancel-btn {
            background-color: #f7f2f0;
            /* 米白 */
            color: #6B4D38;
            /* 深咖啡 */
            border: none;
            border-radius: 4px;
            width: 48%;
            padding: 10px;
            font-size: 16px;
            font-weight: bold;
        }

        .cancel-btn:hover {
            background-color: #6B4D38;
            color: #fff;
        }
    </style>
</head>

<body>
    <?php include "nav.php"; ?>
    <div class="custom-container">
        <!-- 左側選單 -->
        <aside class="custom-sidebar">
            <div class="custom-sidebar-logo">
                <h2>用戶資料管理</h2>
            </div>
            <ul class="custom-menu">
                <li class="custom-menu-item custom-active" data-page="hospital">醫療機構</li>
                <li class="custom-menu-item" data-page="patient">患者</li>
                <li class="custom-menu-item" data-page="caregiver">照護者</li>
                <li class="custom-menu-item" data-page="manager">管理者</li>
            </ul>
            <div class="custom-support"></div>
            <div class="custom-appearance">
                <button id="custom-toggle-theme">新增帳號</button>
            </div>
        </aside>

        <!-- 右側內容區 -->
        <div class="custom-main-content" id="content-area">
            <div class="page-content">
                <?php include 'data.php'; ?>
            </div>
        </div>

        <div id="exampleModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
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


        <!-- 帳號資訊 -->
        <div id="accountModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="accountModalLabel"
            aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="accountModalLabel">詳細資訊</h5>
                        <button type="button" class="close" id="closeAccountModal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>帳號資訊加載中...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 新增帳號互動視窗 -->
        <div id="addAccountModal" class="modal fade" tabindex="-1" aria-labelledby="addAccountModalLabel"
            aria-hidden="true" data-backdrop="static">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addAccountModalLabel">新增帳號</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="addAccountForm">
                            <div class="form-group">
                                <label for="account">帳號</label>
                                <input type="text" class="form-control" id="account" name="account" placeholder="輸入帳號"
                                    required>
                            </div>
                            <div class="form-group">
                                <label for="password">密碼</label>
                                <input type="password" class="form-control" id="password" name="password"
                                    placeholder="輸入密碼" required>
                            </div>
                            <div class="form-group">
                                <label for="confirmpassword">確認密碼</label>
                                <input type="password" class="form-control" id="confirmpassword" name="confirmpassword"
                                    placeholder="確認密碼" required>
                            </div>
                            <div class="form-group">
                                <label for="accountEmail">Email</label>
                                <input type="email" class="form-control" id="accountEmail" name="accountEmail"
                                    placeholder="輸入Email" required>
                            </div>
                            <div class="form-group">
                                <label for="name">姓名</label>
                                <input type="text" class="form-control" id="name" name="name" placeholder="輸入姓名"
                                    required>
                            </div>
                            <div class="form-group">
                                <label for="gender">性別</label>
                                <select class="form-control" id="gender" name="gender" required>
                                    <option value="" disabled selected>請選擇</option>
                                    <option value="M">男</option>
                                    <option value="F">女</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="phone">聯絡電話</label>
                                <input type="text" class="form-control" id="phone" name="phone" placeholder="輸入聯絡電話"
                                    required>
                            </div>
                            <div class="form-group">
                                <label for="address">地址</label>
                                <input type="text" class="form-control" id="address" name="address" placeholder="地址"
                                    required>
                            </div>
                            <div class="form-group">
                                <label for="usertype">使用者類型</label>
                                <select class="form-control" id="usertype" name="usertype" required>
                                    <option value="" disabled selected>請選擇使用者類型</option>
                                    <option value="patient">患者</option>
                                    <option value="caregiver">照護者</option>
                                    <option value="hospital">醫療機構</option>
                                    <option value="admin">管理者</option>
                                </select>
                            </div>
                            <div id="institutionNameField" style="display: none;">
                                <div class="form-group">
                                    <label for="institution_name">醫療機構名稱</label>
                                    <input type="text" class="form-control" id="institution_name"
                                        name="institution_name">
                                </div>
                                <div class="form-group">
                                    <label for="institution_id">醫療機構 ID</label>
                                    <input type="text" class="form-control" id="institution_id" name="institution_id">
                                    <div style="color: gray; margin-top: 5px;">
                                        *小提醒：一個醫療機構僅能申請一個帳號，請確認資料再申請!
                                    </div>
                                </div>

                            </div>
                            <button type="submit" class="btn btn-primary btn-full-width">提交</button>
                        </form>
                    </div>


                </div>
            </div>
        </div>



        <!-- 醫療機構新增資料 -->
        <div id="insterdataModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="dataModalLabel"
            aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="dataModalLabel">醫療機構資料</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <!-- 添加 ID -->
                        <form id="insterDataForm" method="post">

                            <label><span style="color: red;">*</span> 醫療機構地址</label>
                            <div class="address-row">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="city" name="city" placeholder=" "
                                        required>
                                    <label for="city">縣市</label>
                                </div>
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="district" name="district"
                                        placeholder=" " required>
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
                                    <input type="text" class="form-control" id="number" name="number" placeholder=" "
                                        required>
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
                                <span id="loadingSpinner" class="spinner-border spinner-border-sm" role="status"
                                    aria-hidden="true" style="display: none;"></span>
                                搜尋經緯度
                            </button>
                            <div id="error-message" class="error-message">找不到對應的經緯度</div>

                            <div class="form-floating mb-4" id="latlngGroup" style="display: none;">
                                <input type="text" class="form-control" id="latlng" placeholder=" "
                                    style="padding-right: 30px;">
                                <label for="latlng">地圖顯示緯度 (介於 20.5 至 25.5),經度 (介於 119.5 至 122.0)</label>
                            </div>

                            <div class="form-group mb-4">
                                <label for="institution_phone"><span style="color: red;">*</span> 醫療機構電話</label>
                                <input type="text" class="form-control" id="institution_phone" name="institution_phone"
                                    required>
                            </div>

                            <div class="form-group mb-4">
                                <label for="person_charge">聯絡人</label>
                                <input type="text" id="complete-address" class="form-control" name="person_charge">
                            </div>

                            <div class="form-group mb-4">
                                <label for="institution_url">網站</label>
                                <input type="text" class="form-control" id="institution_url" name="institution_url">
                            </div>

                            <div class="form-group mb-4">
                                <label for="institution_phone"><span style="color: red;">*</span> 營業時間</label>
                                <div class="day-settings" id="day-settings">
                                    <!-- 萬一使用者未設置營業時間，可以稍後通過 JavaScript 檢查 -->
                                    <!-- 星期一 -->
                                    <div class="day">
                                        <div class="left-side">
                                            <i class="bi bi-chevron-down" id="chevron-星期一"></i>
                                            <label>星期一</label>
                                        </div>
                                        <i class="bi bi-toggle-off" id="toggle-星期一" onclick="toggleBusiness('星期一')"></i>
                                    </div>
                                    <div class="time-settings" id="time-settings-星期一" style="display: none;">
                                        <div id="time-slots-星期一"></div>
                                        <span onclick="addTimeSlot('星期一')" style="cursor: pointer;"><i
                                                class="bi bi-plus"></i> 新增時段</span>
                                    </div>
                                    <hr>
                                    <!-- 星期二 -->
                                    <div class="day">
                                        <div class="left-side">
                                            <i class="bi bi-chevron-down" id="chevron-星期二"></i>
                                            <label>星期二</label>
                                        </div>
                                        <i class="bi bi-toggle-off" id="toggle-星期二" onclick="toggleBusiness('星期二')"></i>
                                    </div>

                                    <div class="time-settings" id="time-settings-星期二" style="display: none;">
                                        <div id="time-slots-星期二"></div>
                                        <span onclick="addTimeSlot('星期二')" style="cursor: pointer;"><i
                                                class="bi bi-plus"></i> 新增時段</span>
                                    </div>
                                    <hr>

                                    <!-- 星期三 -->
                                    <div class="day">
                                        <div class="left-side">
                                            <i class="bi bi-chevron-down" id="chevron-星期三"></i>
                                            <label>星期三</label>
                                        </div>
                                        <i class="bi bi-toggle-off" id="toggle-星期三" onclick="toggleBusiness('星期三')"></i>
                                    </div>

                                    <div class="time-settings" id="time-settings-星期三" style="display: none;">
                                        <div id="time-slots-星期三"></div>
                                        <span onclick="addTimeSlot('星期三')" style="cursor: pointer;"><i
                                                class="bi bi-plus"></i> 新增時段</span>
                                    </div>
                                    <hr>

                                    <!-- 星期四 -->
                                    <div class="day">
                                        <div class="left-side">
                                            <i class="bi bi-chevron-down" id="chevron-星期四"></i>
                                            <label>星期四</label>
                                        </div>
                                        <i class="bi bi-toggle-off" id="toggle-星期四" onclick="toggleBusiness('星期四')"></i>
                                    </div>

                                    <div class="time-settings" id="time-settings-星期四" style="display: none;">
                                        <div id="time-slots-星期四"></div>
                                        <span onclick="addTimeSlot('星期四')" style="cursor: pointer;"><i
                                                class="bi bi-plus"></i> 新增時段</span>
                                    </div>
                                    <hr>

                                    <!-- 星期五 -->
                                    <div class="day">
                                        <div class="left-side">
                                            <i class="bi bi-chevron-down" id="chevron-星期五"></i>
                                            <label>星期五</label>
                                        </div>
                                        <i class="bi bi-toggle-off" id="toggle-星期五" onclick="toggleBusiness('星期五')"></i>
                                    </div>

                                    <div class="time-settings" id="time-settings-星期五" style="display: none;">
                                        <div id="time-slots-星期五"></div>
                                        <span onclick="addTimeSlot('星期五')" style="cursor: pointer;"><i
                                                class="bi bi-plus"></i> 新增時段</span>
                                    </div>
                                    <hr>

                                    <!-- 星期六 -->
                                    <div class="day">
                                        <div class="left-side">
                                            <i class="bi bi-chevron-down" id="chevron-星期六"></i>
                                            <label>星期六</label>
                                        </div>
                                        <i class="bi bi-toggle-off" id="toggle-星期六" onclick="toggleBusiness('星期六')"></i>
                                    </div>

                                    <div class="time-settings" id="time-settings-星期六" style="display: none;">
                                        <div id="time-slots-星期六"></div>
                                        <span onclick="addTimeSlot('星期六')" style="cursor: pointer;"><i
                                                class="bi bi-plus"></i> 新增時段</span>
                                    </div>
                                    <hr>

                                    <!-- 星期日 -->
                                    <div class="day">
                                        <div class="left-side">
                                            <i class="bi bi-chevron-down" id="chevron-星期日"></i>
                                            <label>星期日</label>
                                        </div>
                                        <i class="bi bi-toggle-off" id="toggle-星期日" onclick="toggleBusiness('星期日')"></i>
                                    </div>

                                    <div class="time-settings" id="time-settings-星期日" style="display: none;">
                                        <div id="time-slots-星期日"></div>
                                        <span onclick="addTimeSlot('星期日')" style="cursor: pointer;"><i
                                                class="bi bi-plus"></i> 新增時段</span>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center mt-4">
                                <button type="button" class="btn btn-primary w-100" id="submit-button">送出</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>


        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
        <script>
            document.addEventListener("DOMContentLoaded", function () {

                // 啟動 Bootstrap popover
                var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-toggle="popover"]'));
                var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
                    return new bootstrap.Popover(popoverTriggerEl);
                });

                // 點擊選單項目時發送 GET 請求
                document.querySelectorAll('.custom-menu-item').forEach(function (item) {
                    item.addEventListener('click', function () {
                        const pageType = this.getAttribute('data-page');
                        loadData(pageType);
                    });
                });

                function loadData(type) {
                    const url = `data.php?type=${type}`;
                    const contentArea = document.querySelector('#content-area .page-content');

                    // 發送 GET 請求以獲取資料
                    fetch(url)
                        .then(response => response.text())
                        .then(data => {
                            contentArea.innerHTML = data;

                            // 更新選單狀態
                            updateActiveMenu(type);
                        })
                        .catch(error => {
                            console.error('發生錯誤:', error);
                        });
                }

                function updateActiveMenu(type) {
                    document.querySelectorAll('.custom-menu-item').forEach(function (item) {
                        item.classList.remove('custom-active');
                    });

                    const activeItem = document.querySelector(`.custom-menu-item[data-page="${type}"]`);
                    if (activeItem) {
                        activeItem.classList.add('custom-active');
                    }
                }

                $('#content-area').on('click', '.account-cell', function () {
                    const account = $(this).data('account');
                    $('#accountModal').modal('show');
                    $('#accountModal .modal-title').text('帳號詳細資訊 - ' + account);

                    // 請求帳號詳細資訊
                    $.ajax({
                        url: 'get_account_info.php',
                        type: 'POST',
                        data: { account },
                        success: function (response) {
                            $('#accountModal .modal-body').html(response);

                            $('#editAccountButton').on('click', submitEditForm);
                            $('#deleteAccountButton').on('click', confirmDelete);
                        }
                    });
                });

                // 提交編輯表單
                function submitEditForm() {
                    const accountData = {
                        account: $('#accountModal .modal-title').text().split(' - ')[1], // 從 modal 標題提取帳號
                        name: $('#editForm [name="name"]').val(),
                        email: $('#editForm [name="email"]').val(),
                        phone: $('#editForm [name="phone"]').val(),
                        sex: $('#editForm [name="sex"]').val(), // 新增性別欄位
                        address: $('#editForm [name="address"]').val() // 新增地址欄位
                    };
                    const emergencyName = $('#editForm [name="emergency_name"]').val();
                    const emergencyPhone = $('#editForm [name="emergency_phone"]').val();
                    const birthday = $('#editForm [name="birthday"]').val();

                    if (emergencyName) {
                        accountData.emergency_name = emergencyName;
                    }
                    if (emergencyPhone) {
                        accountData.emergency_phone = emergencyPhone;
                    }
                    if (birthday) {
                        accountData.birthday = birthday;
                    }

                    // 打印出 accountData 以檢查
                    //alert(JSON.stringify(accountData));

                    const editButton = $('#editAccountButton');
                    const deleteButton = $('#deleteAccountButton');
                    // 禁用編輯按鈕並顯示讀取圖示
                    editButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> 修改');
                    // 禁用刪除按鈕
                    deleteButton.prop('disabled', true);


                    $.ajax({
                        url: 'update_account.php',
                        type: 'POST',
                        data: accountData,
                        success: function (response) {
                            try {
                                // 確保回應是 JSON 格式
                                const res = JSON.parse(response);

                                if (res.status === 'success') {
                                    alert("帳號更新成功！");
                                    $('#accountModal').modal('hide');
                                    location.reload();
                                } else if (res.status === 'error') {
                                    alert("更新失敗，錯誤訊息: " + res.message);
                                    console.error('Error message:', res.message);
                                } else {
                                    alert("更新失敗，錯誤訊息: 未知錯誤");
                                }
                            } catch (error) {
                                alert("回應格式錯誤: " + error.message);
                                console.error('Error parsing response:', error.message);
                            }
                        },
                        error: function (xhr, status, error) {
                            console.error('Ajax request failed:', error);
                            alert("Ajax 請求失敗: " + error);
                        },
                        complete: function () {
                            // 完成後恢復按鈕狀態並移除讀取圖示
                            editButton.prop('disabled', false).html('修改');
                            deleteButton.prop('disabled', false);
                        }
                    });

                }




                // 確認刪除帳號
                function confirmDelete() {
                    const userType = $('#accountModal .modal-body').find('[name="userType"]').val();
                    console.log('帳號類別',userType)
                    const confirmMessage = userType === 'hospital' ?
                        "該動作僅刪除帳號，該醫療機構資訊仍會顯示在網頁上。你確定嗎？" :
                        "你確定要刪除這個帳號嗎？";

                    if (confirm(confirmMessage)) {
                        const account = $('#accountModal .modal-title').text().split(' - ')[1];

                        $.ajax({
                            url: 'delete_account.php',
                            type: 'POST',
                            data: { account },
                            success: function (response) {
                                if (response.status === 'success') {
                                    alert("帳號刪除成功！");
                                    $('#accountModal').modal('hide');
                                    location.reload();
                                }else if (res.status === 'error') {
                                    alert("刪除失敗，錯誤訊息: " + res.message);
                                    console.error('Error message:', res.message);
                                } else {
                                    alert("刪除失敗，請再試一次！");
                                }
                            }
                        });
                    }
                }



                $('#exampleModal').on('show.bs.modal', function (event) {
                    const button = $(event.relatedTarget);
                    const institutionId = button.data('institution-id');
                    const institutionName = button.data('institution-name');
                    const modal = $(this);

                    modal.find('.modal-title').text('詳細資訊 - ' + institutionName);
                    $('#approve-button').data('institution-id', institutionId);

                    $.ajax({
                        url: 'get_institution_info.php',
                        type: 'POST',
                        data: { institution_id: institutionId },
                        success: function (response) {
                            modal.find('.modal-body').html(response); // 僅更新 #exampleModal 的 .modal-body
                        }
                    });
                });


                $('#approve-button').on('click', function () {
                    handleInstitutionAction($(this), 'approve', '審核通過', '無法退回');
                });

                $('#reject-button').on('click', function () {
                    handleInstitutionAction($(this), 'reject', '退回申請', '審核通過');
                });

                function handleInstitutionAction(button, action, successText, disableText) {
                    const institutionId = button.data('institution-id');
                    if (!institutionId) return alert('無效的 institution ID');

                    button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> 正在發送email通知...');
                    const otherButton = action === 'approve' ? $('#reject-button') : $('#approve-button');

                    $.ajax({
                        url: 'approve_institution.php',
                        type: 'POST',
                        data: { institution_id: institutionId, action },
                        success: function (response) {
                            const res = JSON.parse(response);
                            if (res.status === 'success') {
                                otherButton.prop('disabled', true).text(disableText);
                                location.reload();
                                $('#exampleModal').modal('hide');

                            } else {
                                alert('錯誤: ' + res.message);
                            }
                        },
                        error: function () {
                            alert(`${action === 'approve' ? '審核' : '退回'}失敗，請稍後再試。`);
                        },
                        complete: function () {
                            button.prop('disabled', false).text(successText);
                        }
                    });
                }

                $('#closeAccountModal').on('click', function () {
                    $('#accountModal').modal('hide');
                });

                // 設定按鈕點擊時開啟模態視窗
                document.getElementById("custom-toggle-theme").addEventListener("click", function () {
                    $('#addAccountModal').modal({
                        backdrop: 'static', // 防止點擊背景關閉
                        keyboard: false     // 禁用 ESC 鍵關閉
                    }).modal('show'); // 顯示模態視窗
                });

                // 綁定關閉按鈕事件，確保只關閉 #addAccountModal 模態視窗
                $('#addAccountModal .close').on('click', function () {
                    $('#addAccountModal').modal('hide');
                });

                // 防止點擊背景時關閉
                $('#addAccountModal').on('click', function (event) {
                    const modalContent = document.querySelector('#addAccountModal .modal-content');
                    if (!modalContent.contains(event.target)) {
                        event.stopPropagation(); // 阻止點擊背景時關閉
                    }
                });


                // 綁定模態視窗右上角關閉按鈕的事件
                $('#insterdataModal .close').on('click', function () {
                    $('#insterdataModal').modal('hide');
                });



                document.getElementById("usertype").addEventListener("change", function () {
                    const institutionNameField = document.getElementById("institutionNameField");

                    if (this.value === "hospital") {
                        // 當選擇醫療機構時顯示醫療機構名稱欄位
                        institutionNameField.style.display = "block";
                    } else {
                        // 其他選項則隱藏醫療機構名稱欄位
                        institutionNameField.style.display = "none";
                    }
                });



                const form = document.getElementById('addAccountForm');
                const userTypeField = document.getElementById('usertype');
                const institutionNameField = document.getElementById('institutionNameField');
                const institutionNameInput = document.getElementById('institution_name');

                // 根據使用者類型選擇顯示「醫療機構名稱」欄位
                userTypeField.addEventListener('change', function () {
                    if (userTypeField.value === 'hospital') {
                        institutionNameField.style.display = 'block';
                        institutionNameInput.setAttribute('required', 'required'); // 設為必填
                    } else {
                        institutionNameField.style.display = 'none';
                        institutionNameInput.removeAttribute('required'); // 取消必填
                    }
                });

                const addAccountModal = $('#addAccountModal');
                const insterdataModal = $('#insterdataModal');

                form.addEventListener('submit', function (e) {
                    e.preventDefault(); // 阻止表單的預設提交行為

                    // 檢查所有欄位是否已填寫
                    const requiredFields = form.querySelectorAll('input[required], select[required]');
                    let allFieldsFilled = true;
                    requiredFields.forEach(field => {
                        if (!field.value.trim()) {
                            allFieldsFilled = false;
                            field.classList.add('is-invalid'); // 加上錯誤樣式
                        } else {
                            field.classList.remove('is-invalid'); // 移除錯誤樣式
                        }
                    });

                    // 檢查醫療機構名稱和醫療機構 ID 欄位
                    const userTypeField = document.getElementById('usertype');
                    const institutionNameInput = document.getElementById('institution_name');
                    const institutionIdInput = document.getElementById('institution_id');

                    if (userTypeField.value === 'hospital') {
                        let institutionFieldsFilled = true;

                        // 檢查醫療機構名稱
                        if (!institutionNameInput.value.trim()) {
                            institutionFieldsFilled = false;
                            institutionNameInput.classList.add('is-invalid');
                        } else {
                            institutionNameInput.classList.remove('is-invalid');
                        }

                        // 檢查醫療機構 ID
                        if (!institutionIdInput.value.trim()) {
                            institutionFieldsFilled = false;
                            institutionIdInput.classList.add('is-invalid');
                        } else {
                            institutionIdInput.classList.remove('is-invalid');
                        }

                        if (!institutionFieldsFilled) {
                            alert("醫療機構名稱和醫療機構 ID 皆為必填欄位！");
                            return;
                        }
                    }

                    // 檢查密碼與確認密碼是否相同
                    const passwordInput = document.getElementById('password');
                    const confirmPasswordInput = document.getElementById('confirmpassword');
                    if (passwordInput.value !== confirmPasswordInput.value) {
                        passwordInput.classList.add('is-invalid');
                        confirmPasswordInput.classList.add('is-invalid');
                        alert("密碼與確認密碼不相符，請檢查後再試。");
                    } else {
                        passwordInput.classList.remove('is-invalid');
                        confirmPasswordInput.classList.remove('is-invalid');
                    }

                    if (!allFieldsFilled) {
                        alert("請確保所有欄位已填寫完成！");
                        return;
                    }

                    // 準備資料並使用 AJAX 發送
                    const formData = new FormData(form);
                    fetch('insert_account.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => {
                            // 檢查回應是否成功
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json(); // 嘗試解析為 JSON
                        })
                        .then(data => {
                            if (data.status === 'success') {
                                if (data.message === 'hospital') {
                                    const addAccountForm = document.getElementById('addAccountForm');
                                    const insterDataForm = document.getElementById('insterDataForm');

                                    const formData = new FormData(addAccountForm);
                                    formData.forEach((value, key) => {
                                        const hiddenInput = document.createElement('input');
                                        hiddenInput.type = 'hidden';
                                        hiddenInput.name = key;
                                        hiddenInput.value = value;
                                        insterDataForm.appendChild(hiddenInput);
                                    });
                                    addAccountModal.modal('hide');  // 隱藏原互動視窗
                                    $('#insterdataModal').modal({
                                        backdrop: 'static', // 禁用點擊背景關閉
                                        keyboard: false     // 禁用 ESC 鍵關閉
                                    }).modal('show'); // 顯示模態視窗
                                } else {
                                    alert("帳號新增成功！");
                                    form.reset(); // 清空表單
                                    institutionNameField.style.display = 'none'; // 隱藏醫療機構名稱欄位
                                    $('#addAccountModal').modal('hide');
                                    loadData('hospital'); // 這樣可以加載指定頁面資料
                                }

                            } else {
                                if (data.message.includes('帳號')) {
                                    document.getElementById('account').classList.add('is-invalid');
                                } else if (data.message.includes('Email')) {
                                    document.getElementById('accountEmail').classList.add('is-invalid');
                                }
                                alert("新增失敗：" + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('錯誤:', error.message);
                            alert("伺服器返回非 JSON 格式，具體錯誤為：" + error.message);
                        });
                });


                const submitButton = document.getElementById("submit-button");

                // 添加點擊事件監聽
                submitButton.addEventListener("click", function () {
                    // 表單驗證與提交邏輯
                    const latlngInput = document.getElementById("latlng");
                    const city = document.getElementById("city").value;
                    const district = document.getElementById("district").value;
                    const street = document.getElementById("street").value;
                    const lane = document.getElementById("lane").value;
                    const alley = document.getElementById("alley").value;
                    const number = document.getElementById("number").value;
                    const floor = document.getElementById("floor").value;
                    const room = document.getElementById("room").value;
                    const phone = document.getElementById("institution_phone").value;

                    // 驗證經緯度是否填寫
                    if (!latlngInput.value) {
                        alert("請先點選搜尋經緯度或自行輸入經緯度！");
                        return;
                    }

                    // 驗證地址、電話
                    if (!city || !district || !number || !phone) {
                        alert("請填寫完整的地址和電話！");
                        return;
                    }

                    // 拼接地址
                    const fullAddress = `${city}${district}${street}${lane ? lane + "巷" : ""}${alley ? alley + "弄" : ""}${number}號${floor ? floor + "樓" : ""}${room ? room + "室" : ""}`;

                    // 生成營業時間資料
                    const serviceTimes = [];
                    document.querySelectorAll(".day").forEach(dayDiv => {
                        const dayLabel = dayDiv.querySelector("label").innerText;
                        const timeSlotsContainer = document.getElementById(`time-slots-${dayLabel}`);

                        // 若該日有營業時段，則取出每組時間並推入陣列
                        if (timeSlotsContainer && timeSlotsContainer.children.length > 0) {
                            Array.from(timeSlotsContainer.children).forEach(timeSlot => {
                                const openTime = timeSlot.querySelector(".open-time").value;
                                const closeTime = timeSlot.querySelector(".close-time").value;

                                if (openTime && closeTime) {
                                    serviceTimes.push({
                                        day: dayLabel,
                                        open_time: openTime,
                                        close_time: closeTime
                                    });
                                }
                            });
                        }
                    });

                    // 獲取 latlng 的值並分離為緯度和經度
                    const latlng = document.getElementById("latlng").value;
                    const [lng, lat] = latlng.split(',').map(coord => coord.trim());

                    // 檢查是否成功分離 lat 和 lng，然後加入到 FormData 中
                    if (lat && lng) {
                        // 定義 FormData
                        const formData = new FormData(document.getElementById("insterDataForm"));
                        formData.append("fullAddress", fullAddress);
                        formData.append("serviceTimes", JSON.stringify(serviceTimes));
                        formData.append("lat", lat);
                        formData.append("lng", lng);

                        // 測試輸出 FormData 的所有值
                        // for (let [key, value] of formData.entries()) {
                        //     console.log(`${key}: ${value}`);
                        // }

                        // 提交資料
                        fetch("insert_hospital.php", {
                            method: "POST",
                            body: formData
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    $('#insterdataModal').modal('hide');
                                    alert("帳號新增成功！");
                                    document.getElementById('addAccountForm').reset();
                                    document.getElementById('insterDataForm').reset();
                                    document.getElementById('institutionNameField').style.display = 'none';
                                    loadData('hospital');
                                } else {
                                    alert("提交失敗，原因：" + data.message);
                                }
                            })
                            .catch(error => {
                                console.error('錯誤:', error.message);
                                alert("伺服器返回非 JSON 格式，具體錯誤為：" + error.message);
                            });

                    } else {
                        console.error("latlng 格式不正確，無法分離經緯度");
                    }
                });








            });




            function setLatLng() {
                let latlngInput = document.getElementById("latlng");
                const loadingSpinner = document.getElementById("loadingSpinner");
                loadingSpinner.style.display = "inline-block";

                const street = document.getElementById("street").value;
                const lane = document.getElementById("lane").value;
                const alley = document.getElementById("alley").value;
                const number = document.getElementById("number").value;
                const city = document.getElementById("district").value;
                const county = document.getElementById("city").value;

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
            <input type="time" name="business_hours[${day}][${index}][open_time]" class="time-input open-time"> - 
            <input type="time" name="business_hours[${day}][${index}][close_time]" class="time-input close-time">
            <i class="bi bi-trash" onclick="removeTimeSlot('${day}', '${index}')" style="cursor: pointer;"></i>
        </div>
    `;

                timeSlots.appendChild(timeSlot);
            }

            function removeTimeSlot(day, index) {
                // 找到對應的時間段 div 並移除
                var timeSlot = document.getElementById('time-slot-' + day + '-' + index);
                if (timeSlot) {
                    timeSlot.remove();
                }
            }






        </script>

    </div>
</body>




</html>