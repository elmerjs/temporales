import pyautogui
import time

print("Mueve el mouse a la posición del botón 'Aceptar' del popup...")
print("Presiona Ctrl+C en la terminal para detener.")

try:
    while True:
        x, y = pyautogui.position()  # Obtiene coordenadas actuales
        print(f"Posición actual: X={x}, Y={y}", end="\r")  # \r para sobreescribir la línea
        time.sleep(0.1)
except KeyboardInterrupt:
    print("\n¡Coordenadas capturadas! Usa estos valores en pyautogui.moveTo().")