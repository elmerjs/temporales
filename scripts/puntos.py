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
from webdriver_manager.chrome import ChromeDriverManager

# --- Configuración General ---
DOWNLOAD_DIR = r"C:\DescargasAutomatizadas"
CREDENCIALES_SIMCAS = { # Credenciales para el nuevo portal
    "usuario": "zoraida",
    "clave": "242834563608"
}
PERIODO_DESEADO = "II-2025" # Periodo a seleccionar (para el nombre del archivo si se usa)

# Crear la carpeta de descargas si no existe
if not os.path.exists(DOWNLOAD_DIR):
    os.makedirs(DOWNLOAD_DIR)

# --- Configuración del Proxy para Python (variables de entorno) ---
proxy_address = "http://proxy.unicauca.edu.co:3128"
os.environ['http_proxy'] = proxy_address
os.environ['https_proxy'] = proxy_address 
os.environ['NO_PROXY'] = 'localhost,127.0.0.1,::1,localhost.unicauca.edu.co' 

# ========== FUNCIONES AUXILIARES ========== #
# Se mantiene por si se necesita en el futuro, aunque no se usa para los clics pyautogui
def esperar_y_click(driver, selector, by=By.CSS_SELECTOR, timeout=20):
    """Espera y hace clic en un elemento. Timeout aumentado para mayor robustez."""
    WebDriverWait(driver, timeout).until(
        EC.element_to_be_clickable((by, selector))
    ).click()

def esperar_y_escribir(driver, selector, texto, by=By.ID, timeout=20):
    """Espera y escribe texto en un elemento. Timeout aumentado."""
    elemento = WebDriverWait(driver, timeout).until(
        EC.visibility_of_element_located((by, selector))
    )
    elemento.clear()
    elemento.send_keys(texto)


# ========== CONFIGURACIÓN DE CHROME ========== #
chrome_options = Options()
chrome_options.add_argument("--start-maximized")
# chrome_options.add_argument("--headless") # Quita el modo headless temporalmente para depurar
chrome_options.add_argument("--window-size=1920,1080")
chrome_options.add_argument("--disable-gpu")
chrome_options.add_argument("--disable-features=PasswordManager")

# Configuración de proxy para Chrome
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
    time.sleep(2) # Pausa inicial para que la página cargue bien

    # --- 1. Ingresar Credenciales ---
    print("🔑 Ingresando credenciales...")
    esperar_y_escribir(driver, "frmLogin:usuario", CREDENCIALES_SIMCAS["usuario"], By.ID)
    esperar_y_escribir(driver, "frmLogin:contrasena", CREDENCIALES_SIMCAS["clave"], By.ID)
    
    print("🖱️ Haciendo clic en 'Ingresar'...")
    esperar_y_click(driver, "#frmLogin\\:j_idt19 > span", By.CSS_SELECTOR)
    time.sleep(3) # Pequeña pausa para que el login procese

    # --- 2. Seleccionar Periodo de Vinculación con PyAutoGUI ---
    print("📅 Abriendo dropdown de periodo con PyAutoGUI en X=582, Y=592...")
    pyautogui.click(582, 592) # Clic en el trigger del dropdown de periodo
    time.sleep(1) # Esperar a que el dropdown se abra

    print("🖱️ Seleccionando 'II-2025' con PyAutoGUI en X=503, Y=653...")
    pyautogui.click(503, 653) # Clic en la opción 'II-2025'
    
    # --- Espera explícita para que el panel del dropdown se vuelva invisible ---
    print("⏳ Esperando que la lista de periodos se cierre completamente...")
    WebDriverWait(driver, 10).until(
        EC.invisibility_of_element_located((By.ID, "filtroPeriodo_panel"))
    )
    time.sleep(0.5) # Pequeña pausa adicional por si acaso
    
    # --- 3. Filtrar Resultados con PyAutoGUI ---
    print("🔎 Haciendo clic en 'Filtrar resultados' con PyAutoGUI en X=672, Y=693...")
    pyautogui.click(672, 693) # Clic en el botón "Filtrar resultados"
    time.sleep(8) # El usuario indicó que tarda unos 3 segundos, damos un poco más

    # --- 4. Descargar Excel con PyAutoGUI ---
    print("🔽 Verificando y eliminando archivo anterior si existe...")
    nombre_deseado = f"PuntosDocentes_{PERIODO_DESEADO}.xlsx"
    archivo_final = os.path.join(DOWNLOAD_DIR, nombre_deseado)

    # Eliminar el archivo existente antes de la descarga
    if os.path.exists(archivo_final):
        os.remove(archivo_final)
        print(f"🗑️ Archivo anterior eliminado: {archivo_final}")
    else:
        print("✅ No se encontró archivo anterior para eliminar.")

    print("🔽 Haciendo clic en el botón de descarga de Excel con PyAutoGUI en X=1764, Y=739...")
    pyautogui.click(1764, 739) # Clic en la posición del botón de descarga de Excel
    time.sleep(2) # Dar un pequeño tiempo para que se inicie la descarga

    print(f"⏳ Esperando descarga en: {DOWNLOAD_DIR} (45 segundos máximo para descarga y verificación)")
    tiempo_espera = 55
    descarga_completada = False

    while tiempo_espera > 0:
        archivos_en_dir = [f_name for f_name in os.listdir(DOWNLOAD_DIR) if f_name.endswith('.xlsx')]
        if archivos_en_dir:
            descargado_temp_path = max([os.path.join(DOWNLOAD_DIR, f_name) for f_name in archivos_en_dir], key=os.path.getctime)

            # Verificar si el archivo está completo (no es un archivo temporal .crdownload y tiene tamaño > 0)
            if ".crdownload" not in descargado_temp_path and os.path.getsize(descargado_temp_path) > 0:
                # El archivo ya está con el nombre correcto o es el que acabamos de descargar
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