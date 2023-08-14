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
String userid = "24";

const char* ssid = "pato";

const char* password = "1234";
//valores de sensores
String val1; // Variable para almacenar el valor 1
String val2; // Variable para almacenar el valor 2
String val3; // Variable para almacenar el valor 3
String val4; // Variable para almacenar el valor 1
String val5; // Variable para almacenar el valor 2
String val6; // Variable para almacenar el valor 3

// Variables para almacenar la hora y el minuto anterior
int horaAnterior = 0;
int minutoAnterior = 1;

// Definición del pin del sensor DHT11 y tipo de sensor
#define DHTPIN 26
#define DHTTYPE DHT11

// Creación de objetos para trabajar con el sensor PMS y DHT11
PMS pms(Serial2);
PMS::DATA data;
DHT dht(DHTPIN, DHTTYPE);

// Definición de las dimensiones de la pantalla OLED
#define SCREEN_WIDTH 128
#define SCREEN_HEIGHT 64

// Configuración de la pantalla OLED SSD1306
#define OLED_RESET -1
Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, OLED_RESET);

// Configuración del cliente NTP para obtener la hora actual
WiFiUDP ntpUDP;
unsigned int localPort = 8888;
const char* ntpServerName = "ec.pool.ntp.org";
const int timeZone = -5; // GMT-5: Eastern Standard Time (EST)

// Configuración del sensor de gas MQ-7 conectado al GPIO32 (pin 33)
const int pinSensorMQ7 = 33;

// Creación del objeto WebServer para crear un servidor web en el puerto 80
WebServer server(80);

// Número máximo de intentos antes de dar por fallida la conexión WiFi
const int maxIntentos = 10;
int intentosmacro=0;

void setup() {
  Serial.begin(9600);
  Serial2.begin(9600);
  
  // Configuración de la pantalla OLED
  if (!display.begin(SSD1306_SWITCHCAPVCC, 0x3C)) {
    for (;;) {}
  }

  display.display();
  delay(100);
  display.clearDisplay();

  display.clearDisplay();
  display.setTextColor(WHITE);
  display.setTextSize(1);
  display.setCursor(10, 20);
  display.print("Inicializando....");
  display.display();
  delay(3000);

  int intentos = 0;

  // Intentar conectarse a la red WiFi
  while (intentos < maxIntentos) {
    WiFi.begin(ssid, password);
    delay(1000);
    Serial.print(".");
    delay(1000);
    Serial.print(".");

    if (WiFi.status() == WL_CONNECTED) {
       Serial.println("\n¡Conexión WiFi exitosa!");
       break;
    } else {
      intentos++;
    }
  }

  // Inicializar el sistema de archivos SPIFFS
  if (!SPIFFS.begin(true)) {
    return;
  }

  // Configurar las rutas del servidor web
  server.on("/", handle_OnConnect);
  server.on("/download", handle_Download);
  server.on("/downloadp", handle_DownloadP);
  server.on("/reset", handle_Reset);

  // Iniciar el servidor web
  server.begin();
  delay(2000);
  dht.begin();

  // Abrir los archivos para guardar datos de los sensores
  File dataFile = SPIFFS.open("/data.csv", FILE_APPEND);
  if (dataFile) {
    dataFile.close();
  }
  
  dataFile = SPIFFS.open("/promedio.csv", FILE_APPEND);
  if (dataFile) {
    dataFile.close();
  }

  WiFi.disconnect();
}

void handle_OnConnect()
{
  // Obtener el tamaño del archivo y el espacio disponible en SPIFFS
  File dataFile = SPIFFS.open("/data.csv", FILE_READ);
  size_t fileSize = dataFile.size();
  size_t spaceAvailable = SPIFFS.totalBytes() - SPIFFS.usedBytes();
  dataFile.close();

  File dataFile2 = SPIFFS.open("/promedio.csv", FILE_READ);
  size_t fileSize2 = dataFile2.size();
  dataFile2.close();

  server.send(200, "text/html", SendHTML(val1, now() ,val2.toFloat(), val3.toFloat(),val4.toFloat(), val5.toFloat(), fileSize, fileSize2, spaceAvailable));
}

void handle_Download()
{
    // Abrir el archivo CSV para descarga
    File dataFile = SPIFFS.open("/data.csv", FILE_READ);
    if (dataFile)
    {
      server.sendHeader("Content-Disposition", "attachment; filename=data.csv");
      server.streamFile(dataFile, "text/csv");
      dataFile.close();
    }
    else
    {
      server.send(404, "text/plain", "File not found");
    }
}

void handle_DownloadP()
{
    // Abrir el archivo CSV para descarga
    File dataFile = SPIFFS.open("/promedio.csv", FILE_READ);
    if (dataFile)
    {
      server.sendHeader("Content-Disposition", "attachment; filename=promedio.csv");
      server.streamFile(dataFile, "text/csv");
      dataFile.close();
    }else
    {
      server.send(404, "text/plain", "File not found");
    }
}


void resetCSVFiles() {
  // Resetear el archivo /data.csv
  File dataFile = SPIFFS.open("/data.csv", FILE_WRITE);
  if (dataFile) {
   
    dataFile.close();
  }

  // Resetear el archivo /promedio.csv
  File promedioFile = SPIFFS.open("/promedio.csv", FILE_WRITE);
  if (promedioFile) {

    promedioFile.close();
  }
}

void handle_Reset() {
  resetCSVFiles();
  server.send(200, "text/plain", "Reset realizado correctamente");
}



void handle_NotFound()
{
  server.send(404, "text/plain", "Not found");
}

String SendHTML(String v1, time_t timestamp ,float v2, float v3,float v4, float v5, size_t fileSize,size_t fileSize2, size_t spaceAvailable)
{
  String tiempo= getFormattedTime(timestamp);
  String ptr = "<!DOCTYPE html> <html>\n";
  ptr += "<head><meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0, user-scalable=no\">\n";
  ptr += "<title>Wireless Weather Station</title>\n";
  ptr += "<style>html { font-family: Helvetica; display: inline-block; margin: 0px auto; text-align: center;}\n";
  ptr += "body{margin-top: 50px;} h1 {color: #444444;margin: 50px auto 30px;}\n";
  ptr += "p {font-size: 24px;color: #444444;margin-bottom: 10px;}\n";
  ptr += "</style>\n";
  ptr += "<script>\n";
  ptr += "setInterval(loadDoc,1000);\n";
  ptr += "function loadDoc() {\n";
  ptr += "var xhttp = new XMLHttpRequest();\n";
  ptr += "xhttp.onreadystatechange = function() {\n";
  ptr += "if (this.readyState == 4 && this.status == 200) {\n";
  ptr += "document.body.innerHTML =this.responseText}\n";
  ptr += "};\n";
  ptr += "xhttp.open(\"GET\", \"/\", true);\n";
  ptr += "xhttp.send();\n";
  ptr += "}\n";
  ptr += "function downloadFile() {\n";
  ptr += "window.location.href = \"/download\";\n"; // Agregar la función de descarga
  ptr += "}\n";
  ptr += "function downloadFileP() {\n";
  ptr += "window.location.href = \"/downloadp\";\n"; // Agregar la función de descarga
  ptr += "}\n";
   ptr += "function resetFile() {\n";
  ptr += "window.location.href = \"/reset\";\n"; // Agregar la función de descarga
  ptr += "}\n";
  ptr += "</script>\n";
  ptr += "</head>\n";
  ptr += "<body>\n";
  ptr += "<div id=\"webpage\">\n";
  ptr += "<h1>Particulate Matter Monitoring</h1>\n";

  ptr += "<p>IP   : ";
  ptr += val1;
  ptr += "  </p>";
  
  ptr += "<p>T: ";
  ptr += tiempo;
  ptr += "  </p>";

  ptr += "<p>PM2.5: ";
  ptr += v2;
  ptr += " ug/m3</p>";

  ptr += "<p>CO: ";
  ptr += v3;
  ptr += " ug/m3</p>";

  ptr += "<p>TEMP: ";
  ptr += v4;
  ptr += " C</p>";

  ptr += "<p>HUM: ";
  ptr += v5;
  ptr += " %</p>";

  ptr += "<p>File Size /data.csv: ";
  ptr += fileSize;
  ptr += " bytes</p>";
  
  ptr += "<p>File Size /promedio.csv: ";
  ptr += fileSize2;
  ptr += " bytes</p>";

  ptr += "<p>Space Available: ";
  ptr += spaceAvailable;
  ptr += " bytes</p>";

  ptr += "<button onclick=\"downloadFile()\">Download Data as CSV</button>\n"; // Agregar el botón de descarga
   ptr += "<button onclick=\"downloadFileP()\">Download Promedio as CSV</button>\n"; // Agregar el botón de descarga
  ptr += "<button onclick=\"resetFile()\">Restart Data.csv </button>\n"; // Agregar el botón de reset documento

  ptr += "</div>\n";
  ptr += "</body>\n";
  ptr += "</html>\n";
  return ptr;
}
//Funciones para obtemer el tiempo
time_t getNtpTime() {
  while (ntpUDP.parsePacket() > 0) ; // Descartar cualquier paquete recibido anteriormente
  Serial.println("Solicitando hora al servidor NTP...");
  if (ntpUDP.beginPacket(ntpServerName, 123) > 0) {
    byte packetBuffer[48];
    memset(packetBuffer, 0, 48);
    packetBuffer[0] = 0b11100011;   // LI, Version, Mode
    packetBuffer[1] = 0;     // Stratum, or type of clock
    packetBuffer[2] = 6;     // Polling Interval
    packetBuffer[3] = 0xEC;  // Peer Clock Precision
    packetBuffer[12]  = 49;
    packetBuffer[13]  = 0x4E;
    packetBuffer[14]  = 49;
    packetBuffer[15]  = 52;

    ntpUDP.write(packetBuffer, 48);
    ntpUDP.endPacket();
  }
  uint32_t startTime = millis();
  while (millis() - startTime < 1000) {
    int size = ntpUDP.parsePacket();
    if (size >= 48) {
      byte packetBuffer[48];
      ntpUDP.read(packetBuffer, 48); // Leer el paquete NTP
      unsigned long secsSince1900 =  (unsigned long)packetBuffer[40] << 24 |
                                    (unsigned long)packetBuffer[41] << 16 |
                                    (unsigned long)packetBuffer[42] << 8 |
                                    (unsigned long)packetBuffer[43];
      return secsSince1900 - 2208988800UL + timeZone * SECS_PER_HOUR;
    }
    delay(10);
  }
  Serial.println("Error al obtener la hora del servidor NTP");
  return 0; // Devolver 0 en caso de error
}

void displaySerial(String v1, String v2, String v3,String v4, String v5,time_t timestamp) {

  Serial.println("Air Quality Monitor");
  Serial.println("IP :" + v1);
  Serial.print("Fecha y hora: ");
  printDateTime(timestamp);
  Serial.println("PM2.5 :" + v2 + "(ug/m3)");
  Serial.println("CO  :" + v3 + "(ug/m3)");
  Serial.println("");
  Serial.print("Temperatura:" + v4 + "C");
  Serial.println("");
  Serial.print("Humedad:" + v5 + "%");
  Serial.println("");

}

void printDateTime(time_t timestamp) {
  struct tm *dateTime = localtime(&timestamp);
  Serial.print(dateTime->tm_mday);
  Serial.print("/");
  Serial.print(dateTime->tm_mon + 1);
  Serial.print("/");
  Serial.print(dateTime->tm_year + 1900);
  Serial.print(" ");
  Serial.print(dateTime->tm_hour);
  Serial.print(":");
  Serial.print(dateTime->tm_min);
  Serial.print(":");
  Serial.println(dateTime->tm_sec);
}

void displayData(String v1, String v2, String v3,String v4, String v5,time_t timestamp) {
  display.clearDisplay();
  display.setTextSize(1);
  display.setTextColor(SSD1306_WHITE);
  display.setCursor(00, 0);
  display.print("IP: ");
  display.print(v1);

  display.setCursor(00, 10);
  display.print("T:");
  display.print(getFormattedTime(timestamp));

  display.setCursor(10, 25);
  display.print("PM2.5: ");
  display.print(v2);
  display.print(" ug/m3");
  
  display.setCursor(10, 35);
  display.print("CO   : ");
  display.print(v3);
  display.print(" ug/m3");

  display.setCursor(10, 45);
  display.print("TEMP : ");
  display.print(v4);
  display.print(" C");
  
  display.setCursor(10, 55);
  display.print("HUM  : ");
  display.print(v5);
  display.print(" %");

  display.display();
}
//para mostrar en el display
String getFormattedTime(time_t timestamp) {
  struct tm timeinfo;
  gmtime_r(&timestamp, &timeinfo);
  char buffer[20];
  snprintf(buffer, sizeof(buffer), "%02d/%02d/%04d %02d:%02d:%02d", 
           timeinfo.tm_mday, timeinfo.tm_mon + 1, timeinfo.tm_year + 1900, 
           timeinfo.tm_hour, timeinfo.tm_min, timeinfo.tm_sec);
  return String(buffer);
}
//para guardar en csv
String getFormattedTime2(time_t timestamp) {
  struct tm timeinfo;
  gmtime_r(&timestamp, &timeinfo);
  char buffer[20];
  snprintf(buffer, sizeof(buffer), "%02d/%02d/%04d,%02d:%02d:%02d", 
           timeinfo.tm_mday, timeinfo.tm_mon + 1, timeinfo.tm_year + 1900, 
           timeinfo.tm_hour, timeinfo.tm_min, timeinfo.tm_sec);
  return String(buffer);
}
//para enviar a bdd
String getFormattedTimeFecha(time_t timestamp) {
  struct tm timeinfo;
  gmtime_r(&timestamp, &timeinfo);
  char buffer[20];
  snprintf(buffer, sizeof(buffer), "%02d/%02d/%04d", 
           timeinfo.tm_mday, timeinfo.tm_mon + 1, timeinfo.tm_year + 1900);
  return String(buffer);
}
//para enviar a bdd
String getFormattedTimeHora(time_t timestamp) {
  struct tm timeinfo;
  gmtime_r(&timestamp, &timeinfo);
  char buffer[20];
  snprintf(buffer, sizeof(buffer), "%02d:%02d", 
           timeinfo.tm_hour, timeinfo.tm_min);
  return String(buffer);
}

//Guardar archivos en archivo 
void appendToCSVFile(String v1, String v2, String v3, String v4, String v5, time_t timestamp, const String& nombreArchivo) {
  File dataFile = SPIFFS.open(nombreArchivo, FILE_READ);
  size_t fileSize = dataFile.size();
  dataFile.close();
 
  if (fileSize < 50000) {
    dataFile = SPIFFS.open(nombreArchivo, FILE_APPEND);
    if (dataFile) {
      dataFile.print(v1);
      dataFile.print(",");
      dataFile.print(v2);
      dataFile.print(",");
      dataFile.print(v3);
      dataFile.print(",");
      dataFile.print(v4);
      dataFile.print(",");
      dataFile.print(v5);
      dataFile.print(",");
      dataFile.println(getFormattedTime2(timestamp));
      dataFile.close();
    }
  }
  else {
    String fileContent = "";
    dataFile = SPIFFS.open(nombreArchivo, FILE_READ);
    if (dataFile) {
      bool isFirstLine = true;
      while (dataFile.available()) {
        String line = dataFile.readStringUntil('\n');
        if (!isFirstLine) {
          fileContent += line + '\n';
        }
        isFirstLine = false;
      }
      dataFile.close();
    }

    dataFile = SPIFFS.open(nombreArchivo, FILE_WRITE);
    if (dataFile) {
      dataFile.print(fileContent);
      dataFile.close();
    }

    dataFile = SPIFFS.open(nombreArchivo, FILE_APPEND);
    if (dataFile) {
      dataFile.print(v1);
      dataFile.print(",");
      dataFile.print(v2);
      dataFile.print(",");
      dataFile.print(v3);
      dataFile.print(",");
      dataFile.print(v4);
      dataFile.print(",");
      dataFile.print(v5);
      dataFile.print(",");
      dataFile.println(getFormattedTime2(timestamp));
      dataFile.close();
    }
  }
}

void sendDataToServer(const String& coordx, const String& coordy, time_t timestamp,
                      const String& pm25, const String& co, const String& temp) {
   HTTPClient http;
  String usuario = "3420da72a5407b5aba3ccaae7f64b6c3";
  // Construir la URL con los parámetros de la solicitud
  String url = "https://calidadairec.es/server/webservice/rest/server.php";
  url += "?wstoken=" + usuario;
  url += "&wsfunction=core_user_update_users";
  url += "&moodlewsrestformat=json";
  url += "&users[0][id]="+userid;//en este punto se agrega el userid del usuarioque representa al sensor
  url += "&users[0][customfields][0][value]=" + coordx;
  url += "&users[0][customfields][0][type]=Coord_x";
  url += "&users[0][customfields][1][value]=" + coordy;
  url += "&users[0][customfields][1][type]=Coord_y";
  url += "&users[0][customfields][2][value]=" + getFormattedTimeFecha(timestamp);
  url += "&users[0][customfields][2][type]=FECHA";
  url += "&users[0][customfields][3][value]=" + pm25;
  url += "&users[0][customfields][3][type]=PM25";
  url += "&users[0][customfields][4][value]=" + temp;
  url += "&users[0][customfields][4][type]=TEMP";
  url += "&users[0][customfields][5][value]=" + co;
  url += "&users[0][customfields][5][type]=CO";

String url2 = "https://calidadairec.es/server/webservice/rest/server.php";
  url2 += "?wstoken=" + usuario;
  url2 += "&wsfunction=core_user_update_users";
  url2 += "&moodlewsrestformat=json";
  url2 += "&users[0][id]="+userid;
  url2 += "&users[0][customfields][0][value]=" + coordx;
  url2 += "&users[0][customfields][0][type]=Coord_x";
  url2 += "&users[0][customfields][1][value]=" + coordy;
  url2 += "&users[0][customfields][1][type]=Coord_y";
  url2 += "&users[0][customfields][2][value]=" + getFormattedTimeFecha(timestamp);
  url2 += "&users[0][customfields][2][type]=FECHA";
  url2 += "&users[0][customfields][3][value]=" + pm25;
  url2 += "&users[0][customfields][3][type]=PM25";
  url2 += "&users[0][customfields][4][value]=" + temp;
  url2 += "&users[0][customfields][4][type]=TEMP";
  url2 += "&users[0][customfields][5][value]=" + co;
  url2 += "&users[0][customfields][5][type]=CO";
  url2 += "&users[0][customfields][6][value]=" + getFormattedTimeHora(timestamp);
  url2 += "&users[0][customfields][6][type]=HORA";
  // Realizar la solicitud POST
  http.begin(url);

  int httpResponseCode = http.POST("");

  if (httpResponseCode > 0) {
    Serial.print("HTTP Response code: ");
    Serial.println(httpResponseCode);

    String response = http.getString();
    Serial.println(response);
  } else {
    Serial.print("Error on sending HTTP request: ");
    Serial.println(httpResponseCode);
  }

  http.end();

  http.begin(url2);

  int httpResponseCode2 = http.POST("");

  if (httpResponseCode2 > 0) {
    Serial.print("HTTP Response code: ");
    Serial.println(httpResponseCode2);

    String response2 = http.getString();
    Serial.println(response2);
  } else {
    Serial.print("Error on sending HTTP request: ");
    Serial.println(httpResponseCode2);
  }

  http.end();

}

float calcularPromedio(int arreglo[], int longitud) {
    int suma = 0;
    for (int i = 0; i < longitud; i++) {
        suma += arreglo[i];
    }
    return (float)suma / longitud; // Convertimos 'suma' a float para obtener un resultado con decimales
}

void loop()
{
  // Mostrar la fecha y hora actual cada segundo
  val1 = WiFi.localIP().toString();
  if (pms.read(data))
  {
    int adc_MQ = analogRead(pinSensorMQ7); // Leemos la salida analógica del MQ-7
    float CO = adc_MQ * (5.0 / 4095.0);    // Convertimos la lectura en un valor de voltaje (ESP32 tiene resolución de 12 bits)

    val2 = data.PM_AE_UG_2_5;
    val3 = adc_MQ;

    displayData(val1, val2, val3, val4, val5, now());
    delay(1000); // Esperar 2 segundos entre mediciones
    float temperature = dht.readTemperature(); // Leer temperatura en grados Celsius
    float humidity = dht.readHumidity();       // Leer humedad relativa

    if (!isnan(temperature) && !isnan(humidity))
    {
      val4 = String(temperature, 1); // Leer temperatura en grados Celsius
      val5 = String(humidity, 1);    // Leer humedad relativa
    }

    displayData(val1, val2, val3, val4, val5, now());

    delay(1000); // Esperar 2 segundos entre mediciones
    displaySerial(val1, val2, val3, val4, val5, now());

    if (minute(now()) != minutoAnterior)
    {
      if (hour(now()) != horaAnterior)
      {
        horaAnterior = hour(now());
        minutoAnterior = minute(now());
      }
      else
      {
        appendToCSVFile(val1, val2, val3, val4, val5, now(), "/data.csv");
        
        minutoAnterior = minute(now());

        // Intentar restablecer la conexión WiFi si no está conectado
        int intentos = 0;
        while (!WiFi.isConnected() && intentos < maxIntentos) 
        {
          if( intentosmacro == 21){
            ESP.restart();
          }
          // Intentar conectarse a la red WiFi
          while (intentos < maxIntentos)
          {
            WiFi.disconnect();
            delay(2000);
            Serial.print(".");
            WiFi.begin(ssid, password);

            int tiempoEspera = 0;
            while (WiFi.status() != WL_CONNECTED && tiempoEspera < 10000)
            {
              delay(1000);
              tiempoEspera += 1000;
            }

            if (WiFi.status() == WL_CONNECTED)
            {
              Serial.println("\n¡Conexión WiFi exitosa!");
              while (timeStatus() != timeSet)
              {
                delay(1000);
                IPAddress ntpServerIP;
                WiFi.hostByName(ntpServerName, ntpServerIP);
                ntpUDP.begin(localPort);
                setSyncProvider(getNtpTime);
                setSyncInterval(300); // Intervalo de sincronización cada 5 minutos
                delay(1000);
                Serial.println("Esperando sincronización de hora...");
                delay(1000);
              }

              break;
            }
            else
            {
              Serial.println("\nFalló la conexión WiFi. Intentando de nuevo...");
              intentos++;
              intentosmacro++;
            }
          }
        }
        if(WiFi.status() == WL_CONNECTED){
              sendDataToServer("unico", val5, now(), val2, val3, val4);          
        }
      }
    }
  }
  server.handleClient();
}
