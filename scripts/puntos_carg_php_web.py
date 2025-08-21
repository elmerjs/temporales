import os
import time
import requests
import sys # Importa el módulo sys
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from webdriver_manager.chrome import ChromeDriverManager

# --- Configuración del entorno y codificación ---
if sys.stdout.encoding != 'UTF-8':
    sys.stdout.reconfigure(encoding='utf-8')

# --- Configuración General ---
DOWNLOAD_DIR = r"C:\DescargasAutomatizadas"
CREDENCIALES_SIMCAS = {
    "usuario": "zoraida",
    "clave": "242834563608"
}

# Leer el periodo desde la línea de comandos
if len(sys.argv) < 2:
    print("❌ ERROR: Debes proporcionar el periodo (ej. 2025-2) como argumento.")
    sys.exit(1)

PERIODO_DESEADO = sys.argv[1]
print(f"✅ Periodo a procesar: {PERIODO_DESEADO}")

# Crear la carpeta de descargas si no existe
if not os.path.exists(DOWNLOAD_DIR):
    os.makedirs(DOWNLOAD_DIR)

# --- Configuración del Proxy para Python (variables de entorno) ---
proxy_address = "http://proxy.unicauca.edu.co:3128"
os.environ['http_proxy'] = proxy_address
os.environ['https_proxy'] = proxy_address
os.environ['NO_PROXY'] = 'localhost,127.0.0.1,::1,localhost.unicauca.edu.co'

# ========== FUNCIONES AUXILIARES ========== #
def esperar_y_click(driver, selector, by=By.CSS_SELECTOR, timeout=30):
    """Espera y hace clic en un elemento. Timeout aumentado para mayor robustez."""
    WebDriverWait(driver, timeout).until(
        EC.element_to_be_clickable((by, selector))
    ).click()

def esperar_y_escribir(driver, selector, texto, by=By.ID, timeout=30):
    """Espera y escribe texto en un elemento. Timeout aumentado."""
    elemento = WebDriverWait(driver, timeout).until(
        EC.visibility_of_element_located((by, selector))
    )
    elemento.clear()
    elemento.send_keys(texto)

# --- CONFIGURACIÓN PARA SUBIR A PHP ---
PHP_UPLOAD_URL = "http://localhost/temporalesc/procesar_puntos.php"

def subir_excel_a_php(archivo_local, periodo):
    """Sube el archivo descargado a tu servidor PHP"""
    files = {"file": open(archivo_local, "rb")}
    data = {"periodo": periodo}
    try:
        print("📤 Subiendo archivo Excel a la URL PHP:", PHP_UPLOAD_URL)
        r = requests.post(PHP_UPLOAD_URL, files=files, data=data)
        r.raise_for_status()
        print("✅ Respuesta del servidor PHP:", r.text)
    except requests.exceptions.RequestException as e:
        print(f"❌ Error al subir archivo al PHP: {str(e)}")
        print("Asegúrate de que tu servidor PHP esté corriendo y sea accesible.")

# ========== CONFIGURACIÓN DE CHROME ========== #
chrome_options = Options()
chrome_options.add_argument("--start-maximized")
chrome_options.add_argument("--headless") # <-- Asegúrate de que esta línea NO esté comentada
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

# ========== EJECUCIÓN PRINCIPAL ========== #
try:
    print("🚀 Iniciando navegador...")
    driver = webdriver.Chrome(
        service=Service(ChromeDriverManager().install()),
        options=chrome_options
    )

    print("🌐 Accediendo al portal de Puntos Docentes...")
    driver.get("https://simcas.unicauca.edu.co/puntosDocentes/")
    time.sleep(2)

    # --- 1. Ingresar Credenciales ---
    print("🔑 Ingresando credenciales...")
    esperar_y_escribir(driver, "frmLogin:usuario", CREDENCIALES_SIMCAS["usuario"], By.ID)
    esperar_y_escribir(driver, "frmLogin:contrasena", CREDENCIALES_SIMCAS["clave"], By.ID)

    print("🖱️ Haciendo clic en 'Ingresar'...")
    esperar_y_click(driver, "#frmLogin\\:j_idt19 > span", By.CSS_SELECTOR)
    time.sleep(3)

    # --- 2. Seleccionar Periodo de Vinculación con Selenium ---
    print(f"📅 Seleccionando periodo '{PERIODO_DESEADO}' con Selenium...")
    # Clic en el dropdown para abrirlo
    esperar_y_click(driver, "#filtroPeriodo_label", By.ID)
    time.sleep(1)
    # Clic en la opción específica, usando XPATH para robustez
    esperar_y_click(driver, f"//li[contains(@data-label, '{PERIODO_DESEADO}')]", By.XPATH)

    # --- 3. Filtrar Resultados con Selenium ---
    print("🔎 Haciendo clic en 'Filtrar resultados' con Selenium...")
    esperar_y_click(driver, "#btn_filtrar", By.ID)
    time.sleep(8)

    # --- 4. Descargar Excel con Selenium ---
    print("🔽 Verificando y eliminando archivo anterior si existe...")
    nombre_deseado = f"PuntosDocentes_{PERIODO_DESEADO}.xlsx"
    archivo_final = os.path.join(DOWNLOAD_DIR, nombre_deseado)

    if os.path.exists(archivo_final):
        os.remove(archivo_final)
        print(f"🗑️ Archivo anterior eliminado: {archivo_final}")
    else:
        print("✅ No se encontró archivo anterior para eliminar.")

    print("🔽 Haciendo clic en el botón de descarga de Excel con Selenium...")
    esperar_y_click(driver, "#btn_excel", By.ID)
    time.sleep(2)

    print(f"⏳ Esperando descarga en: {DOWNLOAD_DIR} (55 segundos máximo para descarga y verificación)")
    tiempo_espera = 55
    descarga_completada = False

    while tiempo_espera > 0:
        archivos_en_dir = [f_name for f_name in os.listdir(DOWNLOAD_DIR) if f_name.endswith('.xlsx')]
        if archivos_en_dir:
            descargado_temp_path = max([os.path.join(DOWNLOAD_DIR, f_name) for f_name in archivos_en_dir], key=os.path.getctime)

            if ".crdownload" not in descargado_temp_path and os.path.getsize(descargado_temp_path) > 0:
                if descargado_temp_path != archivo_final:
                    os.rename(descargado_temp_path, archivo_final)
                    print(f"✅ Archivo final guardado como: {archivo_final}")
                else:
                    print(f"✅ Archivo ya existe con nombre correcto: {archivo_final}")
                descarga_completada = True
                break

        print("  Archivo aún en descarga o temporal. Esperando...")
        time.sleep(2)
        tiempo_espera -= 2

    if not descarga_completada:
        print("❌ No se encontró el archivo descargado después del tiempo de espera.")
    else:
        print("📤 Subiendo el archivo descargado al script PHP...")
        subir_excel_a_php(archivo_final, PERIODO_DESEADO)

    print("🎉 Script ejecutado exitosamente.")

except Exception as e:
    print(f"❌ ERROR CRÍTICO: {str(e)}")
    if "driver" in locals():
        driver.save_screenshot("error_simcas.png")
        print("📸 Captura de pantalla guardada como 'error_simcas.png' en la misma carpeta del script.")

finally:
    if "driver" in locals() and not chrome_options.experimental_options.get("detach", False):
        driver.quit()
        print("🛑 Navegador cerrado.")