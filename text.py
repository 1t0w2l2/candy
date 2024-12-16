from selenium.webdriver.chrome.options import Options
from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from bs4 import BeautifulSoup
import time
import mysql.connector
from webdriver_manager.chrome import ChromeDriverManager

# 設定 Selenium WebDriver
service = Service(ChromeDriverManager().install())  # 使用 webdriver-manager 來自動安裝 ChromeDriver
driver = webdriver.Chrome(service=service)  # 使用 `service` 來初始化 ChromeDriver

# 設置 Chrome 選項
options = Options()
options.add_argument("--start-maximized")  # 啟動時最大化視窗
options.add_argument("--disable-notifications")  # 禁用通知
options.add_experimental_option("detach", True)  # 讓瀏覽器在腳本結束後保持打開

# 設置 ChromeDriver
service = Service(ChromeDriverManager().install())

# 連接到 MySQL 資料庫，並設置字符集為 UTF-8
try:
    db_connection = mysql.connector.connect(
        host="127.0.0.1",        # 資料庫主機名稱
        user="root",             # 用戶名
        password="",  # 密碼
        database="0819",        # 資料庫名稱
        charset='utf8mb4'        # 設置連接的字符集為 utf8mb4，支援 UTF-8 編碼
    )
    cursor = db_connection.cursor()
    print("資料庫連接成功")
except mysql.connector.Error as err:
    print(f"資料庫連接錯誤: {err}")
    driver.quit()
    exit()


# 啟動瀏覽器
driver = webdriver.Chrome(service=service, options=options)

# 登入 Facebook
def login_facebook():
    driver.get("https://www.facebook.com/")  # Facebook 登入頁面
    print("正在訪問 Facebook 登入頁面...")

    # 等待登入表單元素出現
    WebDriverWait(driver, 15).until(EC.presence_of_element_located((By.ID, "email")))

    # 登入
    username_field = driver.find_element(By.ID, "email")
    password_field = driver.find_element(By.ID, "pass")
    username_field.send_keys("0986684075")  # 替換為你的 Facebook 登入帳號
    password_field.send_keys("1qaz@WSX")  # 替換為你的 Facebook 密碼
    driver.find_element(By.NAME, "login").click()
    print("正在登入...")

    time.sleep(5)

# 訪問特定貼文並抓取內容
def grab_post_content(post_url):
    driver.get(post_url)  # 訪問指定的貼文 URL
    print("正在訪問貼文...")

    time.sleep(5)  # 等待頁面加載

    

    try:
        # 查找貼文內容
        post_content = driver.find_element(By.XPATH, "//div[contains(@class, 'x1iorvi4')]/div[1]").text
        print("貼文內容:\n", post_content)  # 輸出貼文內容
    except Exception as e:
        print(f"抓取貼文內容時出現錯誤: {e}")



# 執行函數
login_facebook()

# 抓取第一篇貼文
print("抓取第一篇貼文:")
grab_post_content("https://www.facebook.com/peachgarden2017/posts/pfbid0epJpn1ZC2H9ZpCKYJk8yAy3CbrPh5UJSpRttwYw5YxP4JAbP2Drw7qn5osumHJWVl?locale=zh_TW")

# 抓取第二篇貼文
print("\n抓取第二篇貼文:")
grab_post_content("https://www.facebook.com/peachgarden2017/posts/pfbid02UtYwAKhCE77uqSE7iS8a37c58KE5feAVSvwNAigtnGfdyfnBTPCFB5GR9Bysgbfdl?locale=zh_TW")

# 抓取第三篇貼文
print("\n抓取第三篇貼文:")
grab_post_content("https://www.facebook.com/peachgarden2017/posts/pfbid0382q6N9bVFDc58yCZS9RPVq4dwe5DuvuTEHLZrzYoPEqW5vtUTQmmCzkjRuzgQmUal?locale=zh_TW")

# 抓取第四篇貼文
print("\n抓取第四篇貼文:")
grab_post_content("https://www.facebook.com/peachgarden2017/posts/pfbid0o6waVx9Kn5MVyRVQyeTiyHHjF8bjQE8TrMocFjWzyTbbPk7udfVPm7XLgxtaXg15l?locale=zh_TW")

print("\n抓取第四篇貼文:")
grab_post_content("https://www.facebook.com/peachgarden2017/posts/pfbid02cM8d1rZPfDUwwrwQmwu2hGe7X36wwRLNJ3dZ9FVtpgA6nFqBK4MTYMg5vzR1Qqrml?locale=zh_TW")
# 抓取第四篇貼文

print("\n抓取第五篇貼文:")
grab_post_content("https://www.facebook.com/peachgarden2017/posts/pfbid02YkDM9PPdXc2pz88iSKoKuVK1oBM7oRooiE1XQYVK2z1AGpWpvfFwD3k4r7VEBbSkl?locale=zh_TW")

# 關閉瀏覽器
driver.quit()
