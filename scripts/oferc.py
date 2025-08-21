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
CREDENCIALES = {
    "usuario": "59824341",
    "clave": "59824341"
}
PERIODO = "2025-2"
PHP_UPLOAD_URL = "http://localhost/temporalesc/procesar_archivo_aspirantes.php"

# Crear la carpeta de descargas si no existe
if not os.path.exists(DOWNLOAD_DIR):
    os.makedirs(DOWNLOAD_DIR)

# --- Configuración del Proxy para Python (variables de entorno) ---
# Dirección del proxy institucional
proxy_address = "http://proxy.unicauca.edu.co:3128"

# Si tu proxy REQUIERE USUARIO Y CONTRASEÑA, descomenta la siguiente línea
# y reemplaza 'tu_usuario' y 'tu_contraseña' con tus credenciales reales:
# proxy_address = "http://tu_usuario:tu_contraseña@proxy.unicauca.edu.co:3128"

# Configurar variables de entorno para que Python y librerías como 'requests' las utilicen.
# Esto es crucial para que las conexiones HTTP/HTTPS generales funcionen correctamente a través del proxy.
os.environ["HTTP_PROXY"] = proxy_address
os.environ["HTTPS_PROXY"] = proxy_address
# Esta línea es CRUCIAL: le dice a Python que NO use el proxy para localhost y 127.0.0.1.
# Esto evita que el proxy interfiera con la comunicación interna entre Selenium y Chrome (el error de "localhost" que teníamos).
os.environ["NO_PROXY"] = "localhost,127.0.0.1"

# --- Función de Utilidad ---
def esperar_y_click(driver, selector, by=By.CSS_SELECTOR, timeout=15):
    """Espera y hace clic en un elemento"""
    WebDriverWait(driver, timeout).until(
        EC.element_to_be_clickable((by, selector))
    ).click()

def subir_excel_a_php(archivo_local, periodo):
    """Sube el archivo descargado a tu servidor PHP"""
    files = {"file": open(archivo_local, "rb")}
    data = {"periodo": periodo}
    try:
        r = requests.post(PHP_UPLOAD_URL, files=files, data=data)
        print("📤 Respuesta del servidor PHP:", r.text)
    except Exception as e:
        print(f"❌ Error al subir archivo al PHP (URL: {PHP_UPLOAD_URL}): {str(e)}")
        print("Asegúrate de que tu servidor PHP esté corriendo y sea accesible desde http://localhost.")

# --- Opciones de Chrome para Selenium ---
chrome_options = Options()
chrome_options.add_argument("--start-maximized") # Inicia el navegador maximizado

# Configurar el proxy directamente en las opciones de Chrome
chrome_options.add_argument(f"--proxy-server={proxy_address}")

# Excluir localhost y 127.0.0.1 del uso del proxy por parte de Chrome
# Esto es vital para que Chrome pueda comunicarse con ChromeDriver sin que el proxy interfiera.
chrome_options.add_argument("--proxy-bypass-list=localhost,127.0.0.1")

# Ignorar errores de certificado, útil en entornos con proxy que inspeccionan SSL
chrome_options.add_argument("--ignore-certificate-errors")

# Deshabilitar gestores de contraseñas y autocompletado del navegador
chrome_options.add_argument("--disable-features=PasswordManager,AutofillSaveCardBubble,ChromePasswordCheck")
chrome_options.add_argument("--disable-save-password-bubble")

# Configurar preferencias de descarga para el navegador Chrome
chrome_options.add_experimental_option("prefs", {
    "download.default_directory": DOWNLOAD_DIR,
    "download.prompt_for_download": False, # Descarga automática sin preguntar
    "download.directory_upgrade": True,
    "safebrowsing.enabled": True, # Deshabilita la navegación segura (opcional, pero a veces interfiere)
    "credentials_enable_service": False,
    "profile.password_manager_enabled": False
})

# Mantener el navegador abierto después de la ejecución del script (útil para depuración)
chrome_options.add_experimental_option("detach", True)

# --- Ejecución Principal del Script ---
try:
    print("🚀 Iniciando navegador...")
    # Usa ChromeDriverManager para descargar y gestionar automáticamente el ChromeDriver compatible
    driver = webdriver.Chrome(
        service=Service(ChromeDriverManager().install()),
        options=chrome_options
    )

    print("🌐 Accediendo al portal...")
    driver.get("https://www.unicauca.edu.co/bancoOferentes/principal.xhtml")

    print("🔑 Ingresando credenciales...")
    WebDriverWait(driver, 15).until(
        EC.presence_of_element_located((By.ID, "frmLogin:identificacion"))
    ).send_keys(CREDENCIALES["usuario"])
    driver.find_element(By.ID, "frmLogin:contrasena").send_keys(CREDENCIALES["clave"])
    driver.find_element(By.ID, "frmLogin:j_idt29").click()
    print("🖱️ Haciendo clic en 'Ingresar'...")

    # --- Manejo del Popup de Seguridad (con PyAutoGUI) ---
    print("⚠️ Esperando popup de seguridad... (puede que necesites ajustar las coordenadas del mouse)")
    time.sleep(3) # Espera un poco para que el popup se renderice completamente
    # Mueve el mouse y haz clic en la posición del botón 'Aceptar'.
    # ¡IMPORTANTE! Ajusta '1208' (X) y '454' (Y) a las coordenadas del botón 'Aceptar'
    # en la pantalla de tu computadora. Si este paso falla, es el primero que debes revisar.
    pyautogui.moveTo(1124, 362, duration=0.5)
    pyautogui.click()
    print("✅ Popup de seguridad cerrado con clic en 'Aceptar'")

    # --- Navegación dentro del Portal ---
    print("📅 Navegando a 'Gestión de periodos'...")
    gestion = WebDriverWait(driver, 15).until(
        EC.element_to_be_clickable((By.XPATH, "//a[contains(., 'Gestión de periodos')]"))
    )
    driver.execute_script("arguments[0].click();", gestion)

    print("⏳ Esperando que cargue la tabla de periodos...")
    WebDriverWait(driver, 20).until(
        EC.presence_of_element_located((By.ID, "formListadoPeriodos"))
    )

    print("📂 Abriendo menú del periodo activo...")
    # Asegúrate de que el ID sea correcto para el botón de menú que abre las opciones
    split_btn = WebDriverWait(driver, 15).until(
    # Selector CSS: busca el botón con la clase 'ui-splitbutton-menubutton'
    # que está dentro de la primera fila (tr:nth-child(1)) de la tabla de periodos.
    EC.element_to_be_clickable((By.CSS_SELECTOR, "#formListadoPeriodos\\:listadoPeriodos tbody tr:nth-child(1) button.ui-splitbutton-menubutton"))
)

    
    driver.execute_script("arguments[0].click();", split_btn)
    print("👥 Ingresando a 'Oferentes Postulados'...")
    # Asegúrate de que el ID sea correcto para la opción "Oferentes Postulados" en el menú
    opcion = WebDriverWait(driver, 10).until(
    EC.element_to_be_clickable((By.XPATH, "//span[text()='Oferentes postulados']"))
)
    opcion.click()

    



    print("💾 Iniciando exportación a Excel...")
    # Selector CSS para el botón de exportar. Verifica que sea el correcto en el HTML.
    esperar_y_click(driver, "input[type='submit'][value='Exportar datos']", by=By.CSS_SELECTOR)



    # --- Verificación de Descarga y Subida a PHP ---
    print("⏳ Esperando descarga... (30 segundos máximo)")
    tiempo_espera = 30
    archivo_final = os.path.join(DOWNLOAD_DIR, "oferentes.xls") # Asume que el archivo se descarga como .xls

    while tiempo_espera > 0:
        # Busca cualquier archivo que empiece con "oferentes" y termine en ".xls"
        archivos_descargados = [f for f in os.listdir(DOWNLOAD_DIR) if f.startswith("oferentes") and f.endswith(".xls")]
        if archivos_descargados:
            # Si hay varios, toma el más reciente o el primero que encuentre
            descargado_temp_path = os.path.join(DOWNLOAD_DIR, archivos_descargados[0])

            # Renombra el archivo descargado a un nombre fijo (oferentes.xls)
            # Solo renombra si el nombre es diferente para evitar errores.
            if os.path.abspath(descargado_temp_path) != os.path.abspath(archivo_final):
                if os.path.exists(archivo_final):
                    os.remove(archivo_final) # Elimina la versión anterior si existe
                    print(f"🗑️ Archivo anterior eliminado: {archivo_final}")
                os.rename(descargado_temp_path, archivo_final)
                print(f"✅ Archivo final guardado como: {archivo_final}")
            else:
                print(f"✅ Archivo ya existe con nombre correcto: {archivo_final}")

            # Subir automáticamente el archivo al servidor PHP
            subir_excel_a_php(archivo_final, PERIODO)
            break # Sale del bucle una vez que el archivo se ha encontrado y procesado
        
        time.sleep(1)
        tiempo_espera -= 1
    else:
        print("❌ No se encontró el archivo descargado después de 30 segundos.")

    print("🎉 Script ejecutado exitosamente.")

except Exception as e:
    print(f"❌ ERROR CRÍTICO: {str(e)}")
    # Guarda una captura de pantalla en caso de error para depuración
    if "driver" in locals():
        driver.save_screenshot("error.png")
        print("📸 Captura de pantalla guardada como 'error.png' en la misma carpeta del script.")

finally:
    # Cierra el navegador al finalizar, a menos que se haya configurado "detach" en True
    if "driver" in locals() and not chrome_options.experimental_options.get("detach", False):
        driver.quit()
        print("🛑 Navegador cerrado.")
    elif "driver" in locals():
        print("🛑 Navegador se mantuvo abierto (detach=True). Ciérralo manualmente para finalizar.")