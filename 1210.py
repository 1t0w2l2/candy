from selenium.webdriver.chrome.options import Options
from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
import time
import mysql.connector
from webdriver_manager.chrome import ChromeDriverManager

# 設定 Selenium WebDriver
service = Service(ChromeDriverManager().install())
options = Options()
options.add_argument("--start-maximized")
options.add_argument("--disable-notifications")
options.add_experimental_option("detach", True)

# 連接到 MySQL 資料庫
try:
    db_connection = mysql.connector.connect(
        host="127.0.0.1",
        user="root",
        password="",  # 密碼
        database="0819",
        charset='utf8mb4'
    )
    cursor = db_connection.cursor()
    print("資料庫連接成功")
except mysql.connector.Error as err:
    print(f"資料庫連接錯誤: {err}")
    exit()

# 啟動瀏覽器
driver = webdriver.Chrome(service=service, options=options)

# 登入 Facebook
def login_facebook():
    driver.get("https://www.facebook.com/")
    print("正在訪問 Facebook 登入頁面...")
    
    WebDriverWait(driver, 15).until(EC.presence_of_element_located((By.ID, "email")))

    username_field = driver.find_element(By.ID, "email")
    password_field = driver.find_element(By.ID, "pass")
    username_field.send_keys("0986684075")  # 替換為你的 Facebook 登入帳號
    password_field.send_keys("1qaz@WSX")  # 替換為你的 Facebook 密碼
    driver.find_element(By.NAME, "login").click()
    print("正在登入...")
    time.sleep(5)

# 抓取報名頁面的標題
def grab_signup_title(url):
    driver.get(url)
    time.sleep(5)  # 等待頁面加載

    try:
        title_element = driver.find_element(By.XPATH, "//h1")  # 根據實際情況調整
        return title_element.text
    except Exception as e:
        print("抓取標題時出現錯誤:", e)
        return None



# 其他程式碼保持不變...

# 抓取貼文內容並檢查圖片
def grab_post_content_and_image(post_url):
    driver.get(post_url)
    print("正在訪問貼文...")
    time.sleep(5)  # 等待頁面加載

    try:
        # 抓取貼文內容
        post_content = driver.find_element(By.XPATH, "//div[contains(@class, 'x1iorvi4')]/div[1]").text  # 根據實際情況調整
        
        # 嘗試抓取圖片，如果沒有圖片則返回 None
        try:
            image_element = driver.find_element(By.XPATH, "//img[contains(@class, 'x1ey2m1c')]")  # 根據實際情況調整
            image_url = image_element.get_attribute("src")
        except Exception as e:
            image_url = None  # 如果沒有圖片，設置為 None

        return post_content, image_url
    except Exception as e:
        print(f"抓取貼文內容時出現錯誤: {e}")
        return None, None

def insert_activity1(title, link, image_url):
    try:
        if image_url:  # 只有在有圖片的情況下插入圖片資料
            cursor.execute(
                "INSERT INTO activity1 (institution_name, activity_title, activity_link, activity_image) VALUES (%s, %s, %s, %s)", 
                ("桃園市失智共同照護中心-桃園長庚", title, link, image_url)
            )
        else:
            cursor.execute(
                "INSERT INTO activity1 (institution_name, activity_title, activity_link) VALUES (%s, %s, %s)", 
                ("桃園市失智共同照護中心-桃園長庚", title, link)
            )
        db_connection.commit()  # 提交到資料庫
    except mysql.connector.Error as err:
        print(f"插入資料時出錯: {err}")

# 執行登入
login_facebook()

# 報名頁面 URL 列表
signup_urls = [
    "https://www.beclass.com/rid=294d9bb66cd36941fd3a",
    "https://www.beclass.com/rid=284d95666a33696a5948",
    "https://www.beclass.com/rid=284d89066550cf509e0d",
    "https://www.beclass.com/rid=284d7ca6607c12530e74"
]

# 貼文 URL 列表
post_urls = [
    "https://www.facebook.com/peachgarden2017/posts/pfbid0epJpn1ZC2H9ZpCKYJk8yAy3CbrPh5UJSpRttwYw5YxP4JAbP2Drw7qn5osumHJWVl?locale=zh_TW",
    "https://www.facebook.com/peachgarden2017/posts/pfbid02UtYwAKhCE77uqSE7iS8a37c58KE5feAVSvwNAigtnGfdyfnBTPCFB5GR9Bysgbfdl?locale=zh_TW",
    "https://www.facebook.com/peachgarden2017/posts/pfbid02cM8d1rZPfDUwwrwQmwu2hGe7X36wwRLNJ3dZ9FVtpgA6nFqBK4MTYMg5vzR1Qqrml?locale=zh_TW",
    "https://www.facebook.com/peachgarden2017/posts/pfbid02YkDM9PPdXc2pz88iSKoKuVK1oBM7oRooiE1XQYVK2z1AGpWpvfFwD3k4r7VEBbSkl?locale=zh_TW"
]
for i in range(len(post_urls)):
    title = grab_signup_title(signup_urls[i])
    post_content, image_url = grab_post_content_and_image(post_urls[i])

    # 確保所有必要的資料都已抓取
    if title and post_content:
        insert_activity1(title, post_urls[i], image_url)  # 插入標題、連結和圖像連結
        print(f"\n標題: {title}")
        print(f"貼文內容:\n{post_content}")

# 關閉資料庫連接和瀏覽器
cursor.close()
db_connection.close()
driver.quit()
