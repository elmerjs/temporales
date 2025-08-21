import os
import time
import pyautogui
import requests
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.keys import Keys
from selenium.common.exceptions import (
    ElementClickInterceptedException,
    TimeoutException,
    StaleElementReferenceException,
)
from webdriver_manager.chrome import ChromeDriverManager

# --- Configuración General ---
DOWNLOAD_DIR = r"C:\DescargasAutomatizadas"
CREDENCIALES_SIMCAS = {
    "usuario": "zoraida",
    "clave": "242834563608"
}
PERIODO_DESEADO = "II-2025"

if not os.path.exists(DOWNLOAD_DIR):
    os.makedirs(DOWNLOAD_DIR)

# --- Proxy ---
proxy_address = "http://proxy.unicauca.edu.co:3128"
os.environ['http_proxy'] = proxy_address
os.environ['https_proxy'] = proxy_address
os.environ['NO_PROXY'] = 'localhost,127.0.0.1,::1,localhost.unicauca.edu.co'

# ========== Helpers ==========
def esperar_y_click(driver, selector, by=By.CSS_SELECTOR, timeout=20):
    el = WebDriverWait(driver, timeout).until(EC.element_to_be_clickable((by, selector)))
    el.click()

def esperar_y_escribir(driver, selector, texto, by=By.ID, timeout=20):
    elemento = WebDriverWait(driver, timeout).until(EC.visibility_of_element_located((by, selector)))
    elemento.clear()
    elemento.send_keys(texto)

def wait_text(driver, selector, texto, by=By.ID, timeout=15):
    WebDriverWait(driver, timeout).until(EC.text_to_be_present_in_element((by, selector), texto))

def wait_invisible(driver, selector, by=By.CSS_SELECTOR, timeout=15):
    WebDriverWait(driver, timeout).until(EC.invisibility_of_element_located((by, selector)))

def wait_ajax_idle(driver, timeout=20):
    """Espera a que jQuery.active sea 0 (sin peticiones AJAX en curso)."""
    WebDriverWait(driver, timeout).until(
        lambda d: d.execute_script("return (window.jQuery && jQuery.active) ? jQuery.active : 0;") == 0
    )

def safe_click(driver, locator, by=By.XPATH, timeout=20):
    """Click robusto: espera, hace scroll al centro y si hay interceptación usa JS."""
    el = WebDriverWait(driver, timeout).until(EC.element_to_be_clickable((by, locator)))
    try:
        driver.execute_script("arguments[0].scrollIntoView({block:'center'});", el)
        time.sleep(0.2)
        el.click()
    except (ElementClickInterceptedException, StaleElementReferenceException, TimeoutException, Exception):
        try:
            driver.execute_script("arguments[0].click();", el)
        except Exception:
            # último recurso: mover el mouse y click normal otra vez
            driver.execute_script("arguments[0].scrollIntoView({block:'center'});", el)
            time.sleep(0.2)
            el.click()

# ========== Chrome ==========
chrome_options = Options()
chrome_options.add_argument("--start-maximized")
# chrome_options.add_argument("--headless=new")  # Actívalo si necesitas headless estable
chrome_options.add_argument("--window-size=1920,1080")
chrome_options.add_argument("--disable-gpu")
chrome_options.add_argument("--disable-features=PasswordManager")
chrome_options.add_argument("--no-proxy-server")
chrome_options.add_argument(f'--proxy-server={proxy_address.replace("http://", "")}')
chrome_options.add_argument('--proxy-bypass-list=localhost,127.0.0.1,::1,localhost.unicauca.edu.co')
chrome_options.add_experimental_option("prefs", {
    "download.default_directory": DOWNLOAD_DIR,
    "download.prompt_for_download": False,
    "download.directory_upgrade": True,
    "safebrowsing.enabled": True,
    "credentials_enable_service": False,
    "profile.password_manager_enabled": False
})

# ========== Ejecución ==========
try:
    print("🚀 Iniciando navegador...")
    driver = webdriver.Chrome(
        service=Service(ChromeDriverManager().install()),
        options=chrome_options
    )

    print("🌐 Abriendo SIMCAS...")
    driver.get("https://simcas.unicauca.edu.co/puntosDocentes/")
    time.sleep(2)

    # --- Login ---
    print("🔑 Ingresando credenciales...")
    esperar_y_escribir(driver, "frmLogin:usuario", CREDENCIALES_SIMCAS["usuario"], By.ID)
    esperar_y_escribir(driver, "frmLogin:contrasena", CREDENCIALES_SIMCAS["clave"], By.ID)
    print("🖱️ Clic en Ingresar...")
    esperar_y_click(driver, "#frmLogin\\:j_idt19 > span", By.CSS_SELECTOR)
    time.sleep(3)

    # --- Período ---
    print(f"📅 Seleccionando período '{PERIODO_DESEADO}'...")
    safe_click(driver, "//div[@id='filtroPeriodo']/div[contains(@class,'ui-selectonemenu-trigger')]/span", By.XPATH)

    opcion_periodo_id = "filtroPeriodo_1"
    opcion_periodo_element = WebDriverWait(driver, 10).until(
        EC.element_to_be_clickable((By.XPATH,
            f"//li[@data-label='{PERIODO_DESEADO}'] | "
            f"//li[normalize-space(text())='{PERIODO_DESEADO}'] | "
            f"//li[contains(@id,'filtroPeriodo_') and normalize-space(text())='{PERIODO_DESEADO}'] | "
            f"//li[@id='{opcion_periodo_id}']"
        ))
    )
    opcion_periodo_element.click()

    # Cerrar dropdown con TAB y verificar que el panel se cerró
    driver.find_element(By.TAG_NAME, 'body').send_keys(Keys.TAB)
    # Espera explícita a que el panel del selectOneMenu desaparezca
    try:
        wait_invisible(driver, "div[id$='filtroPeriodo_panel'].ui-selectonemenu-panel", By.CSS_SELECTOR, timeout=5)
    except TimeoutException:
        # Plan B: ESC por si quedó abierto
        driver.find_element(By.TAG_NAME, 'body').send_keys(Keys.ESCAPE)
        wait_invisible(driver, "div[id$='filtroPeriodo_panel'].ui-selectonemenu-panel", By.CSS_SELECTOR, timeout=5)

    # Confirmar que el label del select muestra el período elegido
    wait_text(driver, "filtroPeriodo_label", PERIODO_DESEADO, By.ID, timeout=10)

    # --- Checkboxes ---
    print("☑️ Marcando 'Ocasional' y 'Cátedra'...")
    safe_click(driver, "//label[normalize-space(.)='Ocasional']/preceding-sibling::div[contains(@class,'ui-chkbox-box')]", By.XPATH)
    time.sleep(0.2)
    safe_click(driver, "//label[normalize-space(.)='Cátedra']/preceding-sibling::div[contains(@class,'ui-chkbox-box')]", By.XPATH)
    time.sleep(0.2)

    # Asegurar que no haya AJAX pendiente antes de filtrar
    wait_ajax_idle(driver, timeout=20)

    # --- Filtrar resultados ---
    print("🔎 Filtrando resultados...")
    # Usar el botón por texto para evitar IDs con dos puntos
    XPATH_FILTRAR = "//button[.//span[normalize-space()='Filtrar resultados']]"
    safe_click(driver, XPATH_FILTRAR, By.XPATH)

    # Esperar a que termine el filtrado (AJAX)
    wait_ajax_idle(driver, timeout=30)

    # --- Exportar Excel ---
    print("🔽 Exportando a Excel...")
    XPATH_EXCEL = "//a[@title='Exportar a Excel' or .//span[contains(normalize-space(.),'Excel')]]"
    safe_click(driver, XPATH_EXCEL, By.XPATH)

    print(f"⏳ Esperando descarga en: {DOWNLOAD_DIR} (hasta 60 s)")
    tiempo_espera = 60
    archivo_final = None

    while tiempo_espera > 0:
        archivos_en_dir = [f for f in os.listdir(DOWNLOAD_DIR) if f.endswith('.xlsx')]
        if archivos_en_dir:
            descargado_temp_path = max([os.path.join(DOWNLOAD_DIR, f) for f in archivos_en_dir], key=os.path.getctime)
            nombre_deseado = f"PuntosDocentes_{PERIODO_DESEADO}.xlsx"
            archivo_final = os.path.join(DOWNLOAD_DIR, nombre_deseado)

            if descargado_temp_path.endswith(".crdownload") or os.path.getsize(descargado_temp_path) == 0:
                time.sleep(2)
                tiempo_espera -= 2
                continue

            if os.path.basename(descargado_temp_path) != nombre_deseado:
                if os.path.exists(archivo_final):
                    os.remove(archivo_final)
                os.rename(descargado_temp_path, archivo_final)
                print(f"✅ Archivo final: {archivo_final}")
            else:
                print(f"✅ Archivo ya con nombre correcto: {archivo_final}")
            break

        time.sleep(1)
        tiempo_espera -= 1
    else:
        print("❌ No se encontró el archivo descargado a tiempo.")

    print("🎉 Proceso completado.")

except Exception as e:
    print(f"❌ ERROR CRÍTICO: {str(e)}")
    try:
        if "driver" in locals():
            driver.save_screenshot("error_simcas.png")
            with open("page_source.html", "w", encoding="utf-8") as f:
                f.write(driver.page_source)
            print("📸 'error_simcas.png' y 'page_source.html' guardados para diagnóstico.")
    except Exception as e2:
        print(f"(No se pudo guardar evidencia: {e2})")
finally:
    if "driver" in locals():
        driver.quit()
        print("🛑 Navegador cerrado.")
