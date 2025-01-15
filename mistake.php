<?php
session_start();
include "db.php";

// 檢查用戶是否登入
$account = isset($_SESSION['account']) ? $_SESSION['account'] : '';
if (empty($account)) {
    header("Location: login.php");
    exit();
}

$keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';
$institutions = [];

if ($_SERVER['REQUEST_METHOD'] == 'GET' && !empty($keyword)) {
    // 查詢資料庫中包含輸入關鍵字的醫療機構
    $sql = "SELECT * FROM institution WHERE institution_name LIKE '%$keyword%'";
    $result = mysqli_query($link, $sql);

    if ($result && $result->num_rows > 0) {
        $institutions = mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
}
?>

<!doctype html>
<html lang="en">

<head>
    <?php include 'head.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // 顯示詳細資訊的彈窗
        function showDetails(institution) {
            let html = `
                <p>機構代碼: ${institution.institution_id}</p>
                <p>機構名稱: ${institution.institution_name}</p>
                <p>地址: ${institution.address}</p>
                <p>電話: ${institution.phone}</p>
                <p>負責人: ${institution.person_charge}</p>
                <p>網站: <a href="${institution.website}" target="_blank">${institution.website}</a></p>
            `;

            Swal.fire({
                title: '醫療機構詳細資訊',
                html: html,
                icon: 'info',
                confirmButtonText: '關閉'
            });
        }

        // 搜尋成功或失敗提示
        function showSearchResult(count) {
            if (count > 0) {
                Swal.fire({
                    title: '搜尋成功',
                    text: `共找到 ${count} 筆相關資料！`,
                    icon: 'success',
                    confirmButtonText: '確定'
                });
            } else {
                Swal.fire({
                    title: '搜尋失敗',
                    text: '沒有符合條件的資料，請重新輸入關鍵字！',
                    icon: 'error',
                    confirmButtonText: '確定'
                });
            }
        }

        // 搜尋關鍵字空白提示
        function showEmptyKeywordAlert() {
            Swal.fire({
                title: '輸入錯誤',
                text: '請輸入醫療機構名稱後再點擊送出！',
                icon: 'warning',
                confirmButtonText: '確定'
            });
        }
    </script>
</head>

<body>

<?php include "nav.php"; ?>
    <div class="s4-container">
        <div class="filter-section">
            <h2 style="text-align: center;">回報錯誤</h2>
            <form method="GET" action="" onsubmit="return validateForm()">
                <input type="text" id="keyword" name="keyword" placeholder="請輸入醫療機構名稱">
                <button type="submit" onclick="handleSearch()">送出</button>
            </form>
        </div>
        <div id="registrationRecords">
            <div class="container">
                <ul class="nav nav-tabs" id="tab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab1" data-bs-toggle="tab" data-bs-target="#content1" type="button" role="tab" aria-controls="content1" aria-selected="true">回報錯誤成功</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab2" data-bs-toggle="tab" data-bs-target="#content2" type="button" role="tab" aria-controls="content2" aria-selected="false">取消回報錯誤報名</button>
                    </li>
                </ul>
                <div id="registrationRecords">
                    <div class="container">
                        <?php if (!empty($institutions)): ?>
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    showSearchResult(<?php echo count($institutions); ?>);
                                });
                            </script>
                <!-- 詳細資訊彈跳視窗 -->
                <div id="detailsModal" class="modal fade s3-modal" tabindex="-1" aria-labelledby="detailsModalLabel"
                    aria-hidden="true" data-backdrop="static">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="detailsModalLabel">詳細資訊</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <p><strong>機構代碼：</strong><span id="modal_institution_id"></span></p>
                                <p><strong>機構名稱：</strong><span id="modal_institution_name"></span></p>
                                <p><strong>地址：</strong><span id="modal_institution_address"></span></p>
                                <p><strong>電話：</strong><span id="modal_institution_phone"></span></p>
                                <p><strong>負責人：</strong><span id="modal_person_charge"></span></p>
                                <p><strong>網站：</strong><a href="" id="modal_institution_website" target="_blank"></a></p>
                            </div>
                        </div>
                    </div>
                </div>

                        <?php else: ?>
                            <?php if (!empty($keyword)): ?>
                                <script>
                                    document.addEventListener('DOMContentLoaded', function() {
                                        showSearchResult(0);
                                    });
                                </script>
                            <?php endif; ?>
                            <p>沒有資料可顯示。</p>
                        <?php endif; ?>
                    </div>
                </div>


                <script>
                    // Function to populate modal with institution details
                    function populateDetailsModal(institutionJson) {
                        const institution = JSON.parse(institutionJson);
                        document.getElementById('modal_institution_id').innerText = institution.institution_id;
                        document.getElementById('modal_institution_name').innerText = institution.institution_name;
                        document.getElementById('modal_institution_address').innerText = institution.address;
                        document.getElementById('modal_institution_phone').innerText = institution.phone;
                        document.getElementById('modal_person_charge').innerText = institution.person_charge;
                        document.getElementById('modal_institution_website').href = institution.website;
                    }
                </script>
            </div>
        </div>

        <script>
            // 檢查表單是否輸入了關鍵字
            function validateForm() {
                const keyword = document.getElementById('keyword').value.trim();
                if (!keyword) {
                    showEmptyKeywordAlert();
                    return false;
                }
                return true;
            }
        </script>
    </div>

</body>

</html>
