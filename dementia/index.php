<?php
session_start();
$account = isset($_SESSION['account']) ? $_SESSION['account'] : '';
$id = isset($_SESSION['institution_id']) ? $_SESSION['institution_id'] : '';
//echo "<script type='text/javascript'>alert('" . $id . "');</script>";
?>
<!doctype html>
<html lang="en">

<head>
    <?php
    include 'head.php';
    ?>
    <script src="js/bootstrap.min.js"></script>
</head>

<body >

   <?php include "nav.php"; ?>



    <div id="app">
        <div id="map" style="top: 13%">
            <div class="dropdown-container">
                <select class="form-select dropdown-style" v-model="selectedCounty" @change="handleCountyChange">
                    <option disabled value="">縣市</option>
                    <option v-for="c in county" :key="c" :value="c">{{ c }}</option>
                </select>
                <select class="form-select dropdown-style" v-model="selectedTownship" @change="handleTownshipChange">
                    <option disabled value="">鄉鎮市區</option>
                    <option v-if="selectedCounty" v-for="t in township" :key="t" :value="t">{{ t }}</option>
                </select>

                <select class="form-select dropdown-style" v-model="selectedService" @change="initMap">
                    <option disabled value="">服務項目</option> <!-- 預設選項 -->
                    <option value="all">顯示全部</option>
                    <optgroup v-for="(services, label) in groupedServices" :key="label" :label="label">
                        <option v-for="(service, index) in services" :key="index" :value="service">{{ service }}
                        </option>
                    </optgroup>
                </select>

            </div>
            <div id="left" class="sidebar flex-center left collapsed">
                <div class="sidebar-content rounded-rect flex-center">
                    <div class="sidebar-content-info">

                        <div v-if="Object.keys(properties).length > 0">
                            <h4 class="institution-name">{{properties.institution_name}}</h4>
                            <ul class="nav nav-tabs d-flex justify-content-between" id="myTab" role="tablist"
                                style="margin-bottom: 10px;">
                                <li class="nav-item flex-fill text-center" role="presentation">
                                    <button class="nav-link active w-100" id="info-tab" data-bs-toggle="tab"
                                        data-bs-target="#info" type="button" role="tab" aria-controls="info"
                                        aria-selected="true">資訊</button>
                                </li>
                                <li class="nav-item flex-fill text-center" role="presentation">
                                    <button class="nav-link w-100" id="reviews-tab" data-bs-toggle="tab"
                                        data-bs-target="#reviews" type="button" role="tab" aria-controls="reviews"
                                        aria-selected="false">評論</button>
                                </li>
                            </ul>

                            <div class="tab-content">
                                <!-- 資訊 -->
                                <div class="tab-pane fade show active" id="info" role="tabpanel"
                                    aria-labelledby="info-tab">

                                    <p class="institution-info"><span class="label">📍 地址：</span> {{ properties.address
                                        }}
                                    </p>
                                    <p class="institution-info"><span class="label">📞 電話：</span> {{ properties.phone }}
                                    </p>
                                    <p class="institution-info" v-if="properties.person_charge">
                                        <span class="label">👤 聯絡人：</span> {{ properties.person_charge }}
                                    </p>
                                    <p class="institution-info" v-if="properties.website">
                                        <span class="label">🔗 網站：</span>
                                        <a :href="properties.website" target="_blank">前往網站</a>
                                    </p>

                                    <div class="service-hours" v-if="Object.keys(ServiceHours).length > 0">
                                        <h2>🕒 服務時間</h2>
                                        <table class="service-hours-table">
                                            <thead>
                                                <tr>
                                                    <th>星期</th>
                                                    <th>營業時間</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="(hours, day) in ServiceHours" :key="day">
                                                    <td class="day">{{ day }}</td>
                                                    <td class="time">
                                                        <div v-for="hour in sortedHours(hours)" :key="hour.service_hour_id">
                                                            <span
                                                                v-if="hour.open_time === '00:00:00' && hour.close_time === '00:00:00'">休息</span>
                                                            <span v-else>{{ formatTime(hour.open_time) }} - {{
                                                                formatTime(hour.close_time) }}</span>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="service-hours" v-if="service_category.length > 0">
                                        <h2>🩺 服務</h2>
                                        <!-- <p class="institution-info"><span class="label">🩺 服務</span></p> -->
                                        <ul class="list-group">
                                            <li v-for="(category, index) in service_category" :key="category[0]"
                                                class="list-group-item">
                                                {{ category.service }}
                                            </li>
                                        </ul>
                                    </div>


                                </div>
                                <!-- 評價 -->
                                <div class="tab-pane fade" id="reviews" role="tabpanel" aria-labelledby="reviews-tab">

                                    <p class="rating">{{ averageRating.toFixed(1) }}</p>
                                    <div class="d-flex justify-content-center align-items-center stardiv">
                                        <img v-for="n in 5"
                                            :src="n <= averageRating ? 'images/star.png' : 'images/no_star.png'"
                                            class="img-fluid star mx-1" :key="n">
                                    </div>


                                    <!-- 新增評論按鈕 -->

                                    <!-- 在這裡顯示新增或刪除評論的按鈕 -->
                                    <?php if (!empty($account) && (isset($_SESSION['user_type']) && ($_SESSION['user_type'] === 'patient' || $_SESSION['user_type'] === 'caregiver'))): ?>
                                        <input type="button" v-if="!hasReviewed" value="新增評論" class="btn-add-review"
                                            @click="showReviewForm = true; newReview.rating = 0; newReview.comment = ''">
                                        <input type="button" v-else value="刪除評論" class="btn-delete-review"
                                            @click="deleteReview()">
                                    <?php endif; ?>




                                    <div class="review-card" v-for="review in reviews" :key="review.review_id">
                                        <div class="review-header">
                                            <span class="review-account">{{ review.account }}</span>
                                            <span class="review-date">{{ review.review_date }}</span>
                                        </div>

                                        <div class="d-flex align-items-center">
                                            <img v-for="n in 5"
                                                :src="n <= review.rating ? 'images/star.png' : 'images/no_star.png'"
                                                class="img-fluid star mx-1" :key="n">
                                        </div>

                                        <div class="review-comment-container">
                                            <p class="review-comment">
                                                <!-- 如果已展開顯示完整評論，否則只顯示截斷評論 -->
                                                {{ review.isExpanded ? review.comment : truncatedComment(review.comment)
                                                }}
                                            </p>
                                            <!-- 如果字數超過設定值，顯示「全文」或「收起」按鈕 -->
                                            <span v-if="review.comment.length > maxLength" @click="toggleText(review)"
                                                class="read-more">
                                                {{ review.isExpanded ? '收起' : '… 全文' }}
                                            </span>
                                        </div>

                                        <!-- 圖片顯示區塊，僅在有圖片時顯示 -->
                                        <div v-if="reviewPic[review.review_id]" class="review">
                                            <div class="image-gallery">
                                                <div v-for="(image, index) in reviewPic[review.review_id].slice(0, showAllImages[review.review_id] ? reviewPic[review.review_id].length : visibleImageCount)"
                                                    :key="image" class="gallery-item" @click="openModal(image)">
                                                    <img :src="image" alt="Review Image" class="thumbnail">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- 自定義按鈕區塊，無論是否有圖片都顯示 -->
                                        <div class="custom-button-container">
                                            <!-- 顯示查看更多或收起按鈕，僅在有多張圖片時顯示 -->
                                            <button
                                                v-if="reviewPic[review.review_id] && reviewPic[review.review_id].length > visibleImageCount"
                                                @click="toggleShowAllImages(review.review_id)"
                                                class="custom-show-more-button">
                                                {{ showAllImages[review.review_id] ? '收起' : '查看更多圖片' }}
                                            </button>

                                            <div v-if="sessionInstitutionId === properties.institution_id">
                                                <button
                                                    @click="toggleReplyForm(review, review.interactions && review.interactions.length > 0)"
                                                    class="custom-reply-button">
                                                    {{ review.interactions && review.interactions.length > 0 ? '編輯回覆' :
                                                    '回覆評論' }}
                                                </button>
                                            </div>


                                        </div>
                                        <div v-if="review.interactions && review.interactions.length > 0"
                                            class="interaction">
                                            <span class="interaction-account">{{ properties.institution_name }}</span>
                                            <span class="interaction-date">{{
                                                formatDate(review.interactions[0].interaction_date) }}</span>
                                            <p class="interaction-comment">{{ review.interactions[0].comment }}</p>

                                            <div v-if="sessionInstitutionId === properties.institution_id">
                                                <button class="delete-button"
                                                    @click="deleteComment(review.interactions[0].interaction_id)">刪除回覆</button>
                                            </div>

                                        </div>






                                    </div>
                                </div>
                            </div>



                            <!-- 123 -->
                        </div>
                        <div v-else>
                            請點擊地標以顯示資訊
                        </div>





                    </div>
                    <div class="sidebar-toggle rounded-rect left">
                        <span class="icon"></span>
                    </div>
                </div>

            </div>
        </div>


        <!-- 新增評價彈出窗口 -->
        <div v-if="showReviewForm" class="review-popup">
            <div class="review-popup-content">
                <span class="close-btn" @click="showReviewForm = false">&times;</span>
                <p class="review-title">{{properties.institution_name}}-新增評論</p>
                <div class="form-group">
                    <fieldset>
                        <span class="star-cb-group">
                            <input type="radio" id="rating-5" name="rating" value="5" v-model="newReview.rating" />
                            <label for="rating-5">5</label>

                            <input type="radio" id="rating-4" name="rating" value="4" v-model="newReview.rating" />
                            <label for="rating-4">4</label>

                            <input type="radio" id="rating-3" name="rating" value="3" v-model="newReview.rating" />
                            <label for="rating-3">3</label>

                            <input type="radio" id="rating-2" name="rating" value="2" v-model="newReview.rating" />
                            <label for="rating-2">2</label>

                            <input type="radio" id="rating-1" name="rating" value="1" v-model="newReview.rating" />
                            <label for="rating-1">1</label>

                            <input type="radio" id="rating-0" name="rating" value="0" class="star-cb-clear"
                                v-model="newReview.rating" />
                            <label for="rating-0">0</label>
                        </span>
                    </fieldset>
                </div>
                <div class="form-group">
                    <label for="comment">評論內容：</label>
                    <textarea class="form-control" style="resize: none; height:150px"
                        v-model="newReview.comment"></textarea>
                </div>
                <div class="mb-3">
                    <!-- 限制檔案類型為照片或影片 -->
                    <input class="form-control" type="file" id="formFile" multiple @change="handleFileChange"
                        accept="image/*,video/*">
                </div>

                <!-- 顯示選擇的檔案 -->
                <ul v-if="selectedFiles.length" class="image-flex">
                    <li v-for="(file, index) in selectedFiles" :key="index">
                        <img v-if="isImage(file)" :src="file.url" alt="Preview" class="image-preview">
                    </li>
                </ul>
                <button type="button" class="btn btn-outline-primary" style="width:100%" :disabled="!isFormValid"
                    @click="submitreview">送出</button>

            </div>
        </div>

        <!-- 動態框 -->
        <div class="modal fade modalmsg" id="successModal" tabindex="-1" aria-labelledby="successModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="successModalLabel">{{ modalMessage }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
            </div>
        </div>


        <!-- 動態框顯示大圖 -->
        <div v-if="modalImage" class="modalimg" @click="closeModal">
            <div class="s2-modal-content" @click.stop>
                <span class="modal-close" @click="closeModal">&times;</span>
                <img :src="modalImage" alt="Full Review Image" class="full-image">
            </div>
        </div>



        <!-- 回覆評論的動態框 -->
        <div class="modal fade" id="replyModal" tabindex="-1" aria-labelledby="replyModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="replyModalLabel">回覆評論</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <!-- 顯示評論者的名稱 -->
                            <p class="review-account-name mb-1">{{ replyToReview.account }}</p>

                            <!-- 顯示該評論的星號評分 -->
                            <div class="d-flex align-items-center stardiv">
                                <img v-for="n in 5"
                                    :src="n <= replyToReview.rating ? 'images/star.png' : 'images/no_star.png'"
                                    class="img-fluid star mx-1" :key="n">
                            </div>
                        </div>
                        <!-- 顯示評論內容（過長則滾動） -->
                        <div class="review-comment-box mt-2">
                            <p class="mb-0">{{ replyToReview.comment }}</p>
                        </div>
                        <!-- 回覆輸入框 -->
                        <textarea v-model="replyContent" class="form-control" placeholder="輸入回覆內容"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="button" class="btn btn-primary"
                            @click="isEditReply ? submitEditReply() : submitNewReply()"
                            :disabled="!replyContent.trim()">提交回覆</button>

                    </div>
                </div>
            </div>
        </div>






        <!-- Loading -->
        <div id="overlayer"></div>
        <div class="loader">
            <div class="spinner-border" role="status">
                <span class="sr-only"></span>
            </div>
        </div>

        <script>
            const account = '<?php echo $account; ?>';
            const sessionInstitutionId = '<?php echo $id; ?>';

            const vue = Vue.createApp({
                data() {
                    return {
                        map: null,  // 存儲地圖實例
                        hospital: [], //所有醫療資訊
                        county: [],
                        township: [],
                        selectedCounty: '',
                        selectedTownship: '',
                        selectedService: '',
                        properties: [], //點選的地標資訊
                        ServiceHours: {}, //服務時間
                        reviews: [], //點擊的機構的所有評價
                        averageRating: 0, //各機構評價分數
                        showReviewForm: false, //新增評價的視窗顯示與否
                        reviewID: 0, //評價的id
                        newReview: {
                            rating: 0, //預設評價的等級
                            comment: ''
                        },
                        selectedFiles: [], //選擇要上傳的檔案
                        modalMessage: '',  // 用來存儲模態框的訊息
                        reviewPic: {}, //儲存評論圖片名稱
                        visibleImageCount: 4,  // 初始顯示的圖片數量
                        showAllImages: {},      // 用來記錄每個評論是否顯示全部圖片
                        modalImage: null,  // 用來儲存顯示的大圖
                        service_all: [], //所有機構的服務項目
                        groupedServices: {}, // 用來分組服務
                        service_category: [], //點擊的機構的所有服務資訊
                        replyContent: '', // 回覆的內容
                        sessionInstitutionId: '', //登入的機構id
                        account: '', //登入的帳號
                        replyToReview: {}, //當前要回覆的評論
                        maxLength: 100, // 設定字數限制
                        isEditReply: false,     // 判斷是新增還是編輯回覆
                    };
                },
                created() {
                    // 在 Vue 初始化後將全局變量 sessionInstitutionId 複製到 Vue 的 data 中
                    this.sessionInstitutionId = sessionInstitutionId;
                    this.account = account;
                },
                computed: {
                    // 檢查表單是否有效
                    isFormValid() {
                        // 檢查評分是否為非零且評論內容不為空
                        return this.newReview.rating > 0 && this.newReview.comment.trim() !== '';
                    },
                    hasReviewed() {
                        return this.reviews.some(review => review.account === this.account);
                    }
                },
                methods: {
                    initMap() {
                        const _this = this;

                        // 使用 $.post 獲取資料（這是服務項目分組邏輯）
                        $.post('api.php?do=servicetype', (response) => {
                            this.service_all = JSON.parse(response);

                            // 使用分組方式來生成 optgroup 和 option
                            this.groupedServices = this.service_all.reduce((groups, item) => {
                                if (!groups[item.lable]) {
                                    groups[item.lable] = []; // 如果不存在該 label，則創建一個新數組
                                }
                                groups[item.lable].push(item.service); // 將服務添加到對應分組
                                return groups;
                            }, {});
                        });

                        // 獲取所有機構數據
                        $.post('api.php?do=getall', function (a) {
                            _this.hospital = JSON.parse(a); //所有機構資料
                            const countySet = new Set();
                            _this.hospital.forEach(item => {
                                if (item.county) {
                                    countySet.add(item.county);
                                }
                            });
                            _this.county = Array.from(countySet); //所有縣市

                            const townshipSet = new Set();
                            _this.hospital.forEach(item => {
                                if (item.town) {
                                    townshipSet.add(item.town);
                                }
                            });
                            _this.township = Array.from(townshipSet); //所有鄉鎮市區

                            // 初始化地圖
                            maptilersdk.config.apiKey = 'Bp0gEjiLZ9O8TINhneWS';
                            maptilersdk.config.primaryLanguage = maptilersdk.Language.AUTO;
                            _this.map = new maptilersdk.Map({
                                container: 'map',
                                style: maptilersdk.MapStyle.STREETS,
                                geolocate: maptilersdk.GeolocationType.POINT
                            });


                            console.log('123456', _this.selectedService)
                            // 判斷 selectedService 是否為空或者 'all'
                            if (_this.selectedService === '' || _this.selectedService === 'all') {
                                // 如果是，直接使用 _this.hospital 生成 features
                                const features = _this.hospital.map(item => {
                                    return {
                                        type: 'Feature',
                                        geometry: {
                                            type: 'Point',
                                            coordinates: [parseFloat(item.lat), parseFloat(item.lng)]
                                        },
                                        properties: {
                                            ...item
                                        }
                                    };
                                });

                                // 更新地圖點位
                                _this.updateMap(features);
                            } else {
                                // 如果不是，則向 API 發送請求並根據回傳的數據設置 features
                                $.post('api.php?do=getpoint', { selectedService: _this.selectedService }, function (response) {
                                    const pointData = JSON.parse(response);

                                    if (Array.isArray(pointData) && pointData.length > 0) {
                                        // 提取所有 institution_id
                                        const institutionIds = pointData.map(item => item.institution_id);
                                        console.log('所有的 institution_id:', institutionIds);

                                        // 將 institutionIds 發送到後端
                                        $.post('api.php?do=pointid', { institution_ids: institutionIds }, function (a) {
                                            const hospital_s = JSON.parse(a);
                                            const features = hospital_s.map(item => {
                                                return {
                                                    type: 'Feature',
                                                    geometry: {
                                                        type: 'Point',
                                                        coordinates: [parseFloat(item.lat), parseFloat(item.lng)]
                                                    },
                                                    properties: {
                                                        ...item
                                                    }
                                                };
                                            });

                                            // 更新地圖點位
                                            _this.updateMap(features);
                                        }).fail(function (jqXHR, textStatus, errorThrown) {
                                            console.error('pointid 請求失敗:', textStatus, errorThrown);
                                        });
                                    } else {
                                        console.error('獲取的 pointData 為空或不是數組');
                                    }
                                })

                            }
                        });
                    },

                    // 將地圖更新邏輯抽取為一個方法
                    updateMap(features) {
                        const geojson = {
                            type: 'FeatureCollection',
                            features: features
                        };

                        const bounds = [
                            [118.1036, 20.72799],
                            [122.9312, 26.60305]
                        ];

                        this.map.on('load', async () => {
                            this.map.setMaxBounds(bounds);

                            const image = await this.map.loadImage('https://docs.maptiler.com/sdk-js/assets/custom_marker.png');
                            this.map.addImage('custom-marker', image.data);

                            this.map.addSource('places', {
                                type: 'geojson',
                                data: geojson
                            });

                            this.map.addLayer({
                                id: 'places',
                                type: 'symbol',
                                source: 'places',
                                layout: {
                                    'icon-image': 'custom-marker',
                                    'icon-overlap': 'always'
                                }
                            });

                            // 點擊地圖標記事件
                            this.map.on('click', 'places', (e) => {
                                const properties = e.features[0].properties;
                                this.institution_id = properties.institution_id;
                                this.showSidebarInfo(properties);
                            });

                            // 滑鼠進入標記事件
                            this.map.on('mouseenter', 'places', () => {
                                this.map.getCanvas().style.cursor = 'pointer';
                            });

                            // 滑鼠離開標記事件
                            this.map.on('mouseleave', 'places', () => {
                                this.map.getCanvas().style.cursor = '';
                            });
                        });
                    },
                    showSidebarInfo(properties) {
                        const _this = this;
                        const textHtml = [];
                        this.properties = properties
                        //服務時間
                        $.post('api.php?do=gettime', { institution_id: _this.institution_id }, function (a) {
                            const serviceHours = JSON.parse(a);

                            _this.ServiceHours = serviceHours.reduce((acc, hour) => {
                                if (!acc[hour.day]) {
                                    acc[hour.day] = [];
                                }
                                acc[hour.day].push(hour);
                                return acc;
                            }, {});
                            console.log('time', _this.ServiceHours)
                        })

                        //評價
                        $.post('api.php?do=getreview', { institution_id: this.institution_id }, (response) => {
                            const reviewdata = JSON.parse(response);
                            if (reviewdata.length > 0) {
                                this.reviews = reviewdata.map(review => ({
                                    ...review,
                                    isExpanded: false, // 預設為收起狀態
                                }));
                                let totalRating = 0;
                                reviewdata.forEach(review => {
                                    totalRating += parseFloat(review.rating);
                                });
                                this.averageRating = totalRating / reviewdata.length;


                                this.reviews.forEach(review => {
                                    $.post('api.php?do=getReviewInteractions', { review_id: review.review_id }, (response) => {
                                        const interactions = JSON.parse(response);
                                        review.interactions = interactions || [];
                                        //console.log(`評價 ${review.review_id} 的回覆:`, review.interactions);
                                    });
                                });
                                console.log('評價', this.reviews);
                            } else {
                                this.reviews = [];
                                this.averageRating = 0;
                            }
                            //評價圖片
                            $.post('api.php?do=getReviewPic', { institution_id: _this.institution_id }, (response) => {
                                const pic = JSON.parse(response);
                                if (pic.length > 0) {
                                    _this.reviewPic = pic.reduce((acc, item) => {
                                        if (!acc[item.review_id]) {
                                            acc[item.review_id] = [];
                                        }
                                        const imagePath = `review/${item.review_image_name}`;
                                        acc[item.review_id].push(imagePath);
                                        return acc;
                                    }, {});
                                }
                            });
                        });


                        //服務
                        $.post('api.php?do=gitservice', { institution_id: _this.institution_id }, function (a) {
                            _this.service_category = JSON.parse(a);
                        })


                        if (document.getElementById('left').classList.contains('collapsed')) {
                            _this.toggleSidebar('left');
                        }
                        this.$forceUpdate();

                    },
                    toggleSidebar(id) {
                        var elem = document.getElementById(id);
                        elem.classList.toggle('collapsed');
                    },
                    sortedHours(hours) {
                        return hours.slice().sort((a, b) => {
                            return a.open_time.localeCompare(b.open_time);
                        });
                    },
                    formatTime(time) {
                        // 使用 slice 提取小時和分鐘
                        return time.slice(0, 5);
                    },
                    formatDate(datetime) {
                        // 將日期和時間用空格分開，取第一個部分（即日期）
                        return datetime.split(' ')[0];
                    },
                    handleCountyChange(event) {
                        const selectedCounty = event.target.value;
                        this.selectedCounty = selectedCounty;
                        const townSet = new Set();
                        this.hospital.forEach(item => {
                            if (item.county === selectedCounty && item.town) {
                                townSet.add(item.town);
                            }
                        });
                        this.township = Array.from(townSet); //讓鄉鎮市區選單只顯示該縣市的

                        this.selectedTownship = '';

                        $.post('api.php?do=selectcounty', { selectedCounty: this.selectedCounty }, (a) => {
                            const data = JSON.parse(a);
                            const latitude = data[0].lat;
                            const longitude = data[0].lng;

                            this.map.setCenter([latitude, longitude]);
                        });
                    },
                    handleTownshipChange(event) {
                        this.selectedTownship = event.target.value;

                        $.post('api.php?do=selecttown', {
                            selectedCounty: this.selectedCounty,
                            selectedTownship: this.selectedTownship
                        }, (a) => {
                            const data = JSON.parse(a);

                            const latitude = data[0].lat;
                            const longitude = data[0].lng;

                            this.map.setCenter([latitude, longitude]);
                            this.map.setZoom(15);

                        });

                    },
                    isImage(file) {
                        return file && file.file && file.file.type.startsWith('image/');
                    },
                    handleFileChange(event) {
                        const files = Array.from(event.target.files);
                        this.selectedFiles = files.map(file => {
                            return {
                                file,
                                name: file.name,
                                size: file.size,
                                url: URL.createObjectURL(file) // 用於圖片預覽
                            };
                        });
                    },
                    async submitreview() {
                        const _this = this;
                        if (_this.newReview.comment == '' && _this.newReview.rating == 0) {
                            _this.modalMessage = '評論新增失敗';
                            // 顯示動態框
                            const modalElement = document.getElementById('successModal');
                            const modalInstance = new bootstrap.Modal(modalElement);
                            modalInstance.show();
                        } else {
                            $.post('api.php?do=maxReview', function (a) {
                                const result = JSON.parse(a);
                                _this.reviewID = parseInt(result[0].max_id, 10) + 1;  // 提取 max_id 並轉為數字

                                // 儲存評論資料
                                const reviewData = {
                                    id: _this.properties.institution_id,
                                    account: account,
                                    rating: _this.newReview.rating,
                                    comment: _this.newReview.comment,
                                    reviewID: _this.reviewID
                                };

                                // 提交評論
                                $.post('api.php?do=submitreview', reviewData, function () {
                                    // 檢查是否有選擇圖片，如果有才進行圖片上傳
                                    if (_this.selectedFiles.length > 0) {
                                        // 儲存圖片資料
                                        const formData = new FormData();
                                        _this.selectedFiles.forEach(file => {
                                            formData.append('files[]', file.file);
                                        });
                                        formData.append('reviewID', _this.reviewID);
                                        formData.append('institution_id', _this.institution_id);

                                        // 上傳檔案
                                        fetch('api.php?do=reviewpic', {
                                            method: 'POST',
                                            body: formData
                                        })
                                            .then(response => {
                                                if (response.ok) {
                                                    _this.modalMessage = '評論新增成功！';
                                                } else {
                                                    _this.modalMessage = '檔案上傳失敗！';
                                                }

                                                // 顯示模態框
                                                const modalElement = document.getElementById('successModal');
                                                const modalInstance = new bootstrap.Modal(modalElement);
                                                modalInstance.show();
                                            })
                                            .catch(error => {
                                                console.error('Error uploading files:', error);
                                                _this.modalMessage = '檔案上傳時發生錯誤！';

                                                // 顯示模態框
                                                const modalElement = document.getElementById('successModal');
                                                const modalInstance = new bootstrap.Modal(modalElement);
                                                modalInstance.show();
                                            });
                                    } else {
                                        // 沒有圖片時，只顯示評論新增成功
                                        _this.modalMessage = '評論新增成功！';

                                        // 顯示模態框
                                        const modalElement = document.getElementById('successModal');
                                        const modalInstance = new bootstrap.Modal(modalElement);
                                        modalInstance.show();
                                    }

                                    // 重置表單狀態
                                    _this.showReviewForm = false;
                                    _this.newReview.rating = 0;
                                    _this.newReview.comment = '';
                                    _this.reviewID = 0;
                                    _this.selectedFiles = [];
                                    _this.showSidebarInfo(_this.properties);
                                });
                            });
                        }
                    },
                    deleteReview() {
                        // 找到該用戶的評論
                        const _this = this
                        const userReview = this.reviews.find(review => review.account === this.account);
                        console.log('review', userReview)
                        $.post('api.php?do=delreview', { review_id: userReview.review_id }, function () {
                            _this.modalMessage = '已成功刪除評論';
                            const modalElement = document.getElementById('successModal');
                            const modalInstance = new bootstrap.Modal(modalElement);
                            modalInstance.show();
                        })
                        _this.showSidebarInfo(_this.properties);



                    },
                    toggleShowAllImages(reviewId) {
                        // 切換 showAllImages 中對應 reviewId 的布林值
                        this.showAllImages = {
                            ...this.showAllImages,
                            [reviewId]: !this.showAllImages[reviewId]
                        };
                    },
                    openModal(image) {
                        // 實現打開圖片模態框的邏輯
                        //console.log("Open image modal for:", image);
                        this.modalImage = image;
                    },
                    closeModal() {
                        // 隱藏模態框
                        this.modalImage = null;
                    },
                    toggleReplyForm(review, isEdit = false) {
                        this.replyToReview = review;
                        this.isEditReply = isEdit; // 設置是新增還是編輯狀態

                        // 如果是編輯回覆，帶入原來的回覆內容，否則清空
                        if (isEdit && review.interactions && review.interactions.length > 0) {
                            this.replyContent = review.interactions[0].comment;
                        } else {
                            this.replyContent = ''; // 如果是新增回覆，清空輸入框
                        }

                        // 顯示回覆的 modal
                        const modalElement = new bootstrap.Modal(document.getElementById('replyModal'));
                        modalElement.show();
                    },
                    submitNewReply() {
                        const _this = this
                        const replyData = {
                            ...this.replyToReview,   // 展開 replyToReview 內的所有屬性
                            replyContent: this.replyContent,  // 加入回覆內容
                            account: this.account
                        };
                        $.post('api.php?do=submitreply', replyData, function () {

                            const replyModalElement = document.getElementById('replyModal');
                            const replyModalInstance = bootstrap.Modal.getInstance(replyModalElement);
                            if (replyModalInstance) {
                                replyModalInstance.hide(); // 關閉回覆動態框
                            }
                            _this.modalMessage = '已成功新增回覆';
                            const modalElement = document.getElementById('successModal');
                            const modalInstance = new bootstrap.Modal(modalElement);
                            modalInstance.show();
                        })
                        _this.replyContent = '';
                        _this.showSidebarInfo(_this.properties);
                    },
                    submitEditReply() {
                        const _this = this
                        const reviewId = this.replyToReview.review_id;

                        const replyData = {
                            review_id: this.replyToReview.review_id,
                            replyContent: this.replyContent
                        };
                        $.post('api.php?do=editreply', replyData, function () {

                            const replyModalElement = document.getElementById('replyModal');
                            const replyModalInstance = bootstrap.Modal.getInstance(replyModalElement);
                            if (replyModalInstance) {
                                replyModalInstance.hide(); // 關閉回覆動態框
                            }
                            _this.modalMessage = '已成功修改回覆';
                            const modalElement = document.getElementById('successModal');
                            const modalInstance = new bootstrap.Modal(modalElement);
                            modalInstance.show();
                        })
                        _this.showSidebarInfo(_this.properties);
                    },
                    deleteComment(id) {
                        const _this = this
                        //console.log('互動編號',id)
                        $.post('api.php?do=delreply', { id: id }, function () {
                            _this.modalMessage = '已成功刪除回覆';
                            const modalElement = document.getElementById('successModal');
                            const modalInstance = new bootstrap.Modal(modalElement);
                            modalInstance.show();
                        })
                        _this.showSidebarInfo(_this.properties);

                    },
                    truncatedComment(comment) {
                        // 截斷評論顯示部分
                        return comment.length > this.maxLength ? comment.substring(0, this.maxLength) : comment;
                    },
                    toggleText(review) {
                        // 切換展開/收起狀態
                        review.isExpanded = !review.isExpanded;
                    },



                },
                mounted() {
                    this.initMap();
                }

            }).mount("#app");

            document.querySelector(".sidebar-toggle").addEventListener('click', function () {
                vue.toggleSidebar('left');
            });

        </script>


</body>

</html>