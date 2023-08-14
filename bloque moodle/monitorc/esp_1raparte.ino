#include <WiFi.h> // Biblioteca para la conexión WiFi
#include <WebServer.h> // Biblioteca para crear un servidor web
#include <SPIFFS.h> // Biblioteca para acceder al sistema de archivos SPIFFS
#include "PMS.h" // Biblioteca para trabajar con el sensor PMS (partículas en suspensión), escoger el by Mariusz Kacki
#include <HardwareSerial.h> // Biblioteca para acceder al puerto serie hardware
#include <Wire.h> // Biblioteca para la comunicación I2C
#include <Adafruit_GFX.h> // Biblioteca para trabajar con gráficos
#include <Adafruit_SSD1306.h> // Biblioteca para controlar la pantalla OLED SSD1306
#include <WiFiUdp.h> // Biblioteca para trabajar con el protocolo UDP a través de WiFi
#include <TimeLib.h> // by Michael Margolis
#include <DHT.h> // by Adafruits
#include <HTTPClient.h>

// Configuración del userid y de la conexión WiFi