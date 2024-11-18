<?php
session_start();
$p = $_POST;
include('pdo.php');
switch ($_GET['do']) {
    case 'getall':
        echo json_encode(fetchAll(query("SELECT * FROM `institution`")));
        break;
    case 'getpoint':
        echo json_encode(fetchAll(query("SELECT * FROM `service_category` WHERE `service`='{$p['selectedService']}';")));
        break;
    case 'pointid':
        if (isset($p['institution_ids'])) {
            if (!is_array($p['institution_ids'])) {
                $p['institution_ids'] = explode(',', $p['institution_ids']);
            }
            $batchSize = 500;
            $institutionIds = $p['institution_ids'];
            $results = [];

            for ($i = 0; $i < count($institutionIds); $i += $batchSize) {
                // 每次處理一批 ids
                $batchIds = array_slice($institutionIds, $i, $batchSize);

                // 生成對應的佔位符
                $placeholders = rtrim(str_repeat('?,', count($batchIds)), ',');

                // 準備 SQL 查詢語句
                $sql = "SELECT * FROM `institution` WHERE `institution_id` IN ($placeholders)";
                $stmt = $conn->prepare($sql);

                // 執行 SQL 查詢並傳遞這批的 id
                $stmt->execute($batchIds);
                $batchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // 合併結果
                $results = array_merge($results, $batchResults);
            }

            // 回傳所有結果
            echo json_encode( $results);
        } else {
            echo json_encode(['error' => 'Invalid institution_ids']);
        }
        break;
    case 'gettime':
        $institution_id = $p['institution_id'];
        echo json_encode(fetchAll(query("SELECT * FROM `servicetime` WHERE `institution_id` = '{$p['institution_id']}'")));
        break;
    case 'selectcounty':
        echo json_encode(fetchAll(query("SELECT * FROM `institution` WHERE `county`='{$p['selectedCounty']}' LIMIT 1;")));
        break;
    case 'selecttown':
        echo json_encode(fetchAll(query("SELECT * FROM `institution` WHERE `county`='{$p['selectedCounty']}' AND `town`='{$p['selectedTownship']}' LIMIT 1;")));
        break;
    case 'getreview':
        echo json_encode(fetchAll(query("SELECT * FROM `review` WHERE `institution_id`='{$p['institution_id']}';")));
        break;
    case 'maxReview':
        echo json_encode(fetchAll(query("SELECT MAX(`review_id`) as max_id FROM `review`;")));
        break;
    case 'submitreview':
        query("INSERT INTO `review`(`review_id`,`institution_id`, `account`, `rating`, `comment`, `review_date`) VALUES ('{$p['reviewID']}','{$p['id']}','{$p['account']}','{$p['rating']}','{$p['comment']}',now())");
        break;
    case 'reviewpic':
        if (isset($_FILES['files'])) {
            $files = $_FILES['files'];

            for ($i = 0; $i < count($files['name']); $i++) {
                $fileName = basename($files['name'][$i]);
                $fileTmpName = $files['tmp_name'][$i];
                $fileSize = $files['size'][$i];
                $fileError = $files['error'][$i];
                $fileType = $files['type'][$i];

                // 確認檔案是否有錯誤
                if ($fileError === 0) {
                    // 設定儲存路徑
                    $fileDestination = 'review/' . $fileName;

                    // 將檔案移動到指定資料夾
                    if (move_uploaded_file($fileTmpName, $fileDestination)) {
                        query("INSERT INTO `review_image`(`review_id`, `institution_id`, `review_image_name`) VALUES ('{$p['reviewID']}','{$p['institution_id']}','{$fileName}')");
                    } else {
                        http_response_code(500); // 內部錯誤
                        exit; // 停止執行並返回500
                    }
                } else {
                    http_response_code(400); // 錯誤的請求
                    exit; // 停止執行並返回400
                }
            }
            // 一切正常時，返回成功狀態碼
            http_response_code(200); // 成功
        } else {
            http_response_code(400); // 沒有上傳檔案
        }
        break;
    case 'getReviewPic':
        echo json_encode(fetchAll(query("SELECT * FROM `review_image` WHERE `institution_id`c")));
        break;
    case 'gitservice':
        echo json_encode(fetchAll(query("SELECT * FROM `service_category` WHERE `institution_id`='{$p['institution_id']}';")));
        break;
    case 'submitreply':
        query("INSERT INTO `review_interaction`(`review_id`, `account`, `interaction_date`, `comment`) VALUES ('{$p['review_id']}','{$p['account']}',now(),'{$p['replyContent']}')");
        break;
    case 'getReviewInteractions':
        echo json_encode(fetchAll(query("SELECT * FROM `review_interaction` WHERE `review_id`='{$p['review_id']}'")));
        break;
    case 'editreply':
        query("UPDATE `review_interaction` SET `interaction_date`=now(),`comment`='{$p['replyContent']}' WHERE `review_id`='{$p['review_id']}'");
        break;
    case 'delreply':
        query("DELETE FROM `review_interaction` WHERE `interaction_id` = '{$p['id']}'");
        break;
    case 'delreview':
        query("DELETE FROM `review` WHERE `review_id` = '{$p['review_id']}'");
        break;
    case 'serviceAll':
        echo json_encode(fetchAll(query("SELECT * FROM `service_category`")));
        break;
    case 'servicetype':
        echo json_encode(fetchAll(query("SELECT DISTINCT `service`, `lable` FROM `service_category`;")));
        break;
    case 'checkid':
        echo json_encode(fetchAll(query("SELECT * FROM `institution` WHERE `institution_id` ='{$p['institution_id']}';")));
        break;
}

?>