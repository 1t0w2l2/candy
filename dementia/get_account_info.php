<?php
// 連接資料庫
include 'db.php';

if (isset($_POST['account'])) {
    $account = $_POST['account'];
    $query = "SELECT * FROM user WHERE account = ?";
    $stmt = $link->prepare($query);
    $stmt->bind_param('s', $account);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo "<form id='editForm' class='edit-account-form'>";
        echo "<div class='form-group'><label>姓名</label><input type='text' name='name' value='" . htmlspecialchars($row['name']) . "' class='form-control'></div>";
        echo "<input type='hidden' name='userType' value='" . htmlspecialchars($row['user_type']) . "' />";
        echo "<div class='form-group'>
        <label>電子郵件</label>
        <div style='display: flex; align-items: center;'>
            <input type='email' name='email' value='" . htmlspecialchars($row['email']) . "' class='form-control' style='flex: 1;'>
            <span class='email-status' style='margin-left: 10px; margin-top: 5px; font-weight: bold; color: " . ($row['email_status'] == 1 ? 'green' : 'red') . ";'>
                " . ($row['email_status'] == 1 ? '已驗證' : '未驗證') . "
            </span>
        </div>
      </div>";
        echo "<div class='form-group'><label>電話</label><input type='text' name='phone' value='" . htmlspecialchars($row['phone']) . "' class='form-control'></div>";
        echo "<div class='form-group'><label>性別</label><select name='sex' class='form-control'><option value='M' <?php echo (" . htmlspecialchars($row['sex']) . "== 'M') ? 'selected' : ''; ?>男</option><option value='F' <?php echo (" . htmlspecialchars($row['sex']) . "== 'F') ? 'selected' : ''; ?>女</option></select></div>";
        echo "<div class='form-group'><label>地址</label><input type='text' name='address' value='" . htmlspecialchars($row['address']) . "' class='form-control'></div>";
        echo "<div class='form-group'>
        <label>註冊日期</label>
        <input type='text' value='" . htmlspecialchars($row['register_time']) . "' class='form-control' readonly>
      </div>";
        if ($row['user_type'] == 'patient') {
            $patientQuery = "SELECT * FROM patient WHERE account = ?";
            $patientStmt = $link->prepare($patientQuery);
            $patientStmt->bind_param('s', $account);
            $patientStmt->execute();
            $patientResult = $patientStmt->get_result();

            if ($patientResult->num_rows > 0) {
                $patientRow = $patientResult->fetch_assoc();
                echo "<div class='form-group'><label>緊急聯絡人</label><input type='text' name='emergency_name' value='" . htmlspecialchars($patientRow['emergency_name']) . "' class='form-control'></div>";
                echo "<div class='form-group'><label>緊急聯絡電話</label><input type='text' name='emergency_phone' value='" . htmlspecialchars($patientRow['emergency_phone']) . "' class='form-control'></div>";
                echo "<div class='form-group'><label>生日</label><input type='date' name='birthday' value='" . htmlspecialchars($patientRow['birthday']) . "' class='form-control'></div>";
            }
        }

        echo "<div class='action-buttons'>";
        //echo "<button type='button' id='submitEditButton' class='btn submit-btn'>提交變更</button>";
        echo "<button id='editAccountButton' class='btn action-btn edit-btn'>修改</button>";
        echo "<button id='deleteAccountButton' class='btn action-btn delete-btn'>移除</button>";
        echo "</div>";
        echo "</form>";

    } else {
        echo "<p>無法找到帳號資訊。</p>";
    }
}
?>