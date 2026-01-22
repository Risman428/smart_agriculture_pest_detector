#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <Servo.h>
#include <DHT.h>
#include <Wire.h>
#include <hd44780.h>
#include <hd44780ioClass/hd44780_I2Cexp.h>

// ================= PIN =================
#define IR_PIN     D5
#define SERVO_PIN  D6
#define BUZZER_PIN D8
#define DHTPIN     D7
#define DHTTYPE    DHT22

// Ultrasonic (AMAN)
#define TRIG_PIN   D0   // GPIO16
#define ECHO_PIN   D3   // GPIO0 (INPUT ONLY + PULLUP)

// ================= WIFI =================
const char* ssid = "HANIF";
const char* password = "H@n1f16_";
const char* serverUrl = "http://192.168.1.3:8000/api/sensor";

// ================= OBJECT =================
Servo myServo;
DHT dht(DHTPIN, DHTTYPE);
hd44780_I2Cexp lcd;

// ================= STATE =================
enum State { STANDBY, ACTIVE };
State currentState = STANDBY;

// ================= VARIABLE =================
float temperature = 0;
float humidity = 0;
long distanceCM = 0;

unsigned long stateStartTime = 0;
unsigned long lastServoMove = 0;
unsigned long lastDHTRead = 0;
unsigned long lastLCDUpdate = 0;
unsigned long lastSendData = 0;
unsigned long lastUltraRead = 0;

const unsigned long activeTime    = 3000;
const unsigned long servoInterval = 300;
const unsigned long dhtInterval   = 2000;
const unsigned long lcdInterval   = 1200;
const unsigned long sendInterval  = 2000;
const unsigned long ultraInterval = 1000;

bool servoPos = false;
bool lastIRState = HIGH;
bool lockEvent = false;
bool sentEvent = false;
bool lcdPage = false;

// ================= ULTRASONIC =================
long readUltrasonic() {
  digitalWrite(TRIG_PIN, LOW);
  delayMicroseconds(2);
  digitalWrite(TRIG_PIN, HIGH);
  delayMicroseconds(10);
  digitalWrite(TRIG_PIN, LOW);

  long duration = pulseIn(ECHO_PIN, HIGH, 25000); // 25ms timeout
  if (duration == 0) return -1;

  return duration * 0.034 / 2;
}

// ================= SEND TO LARAVEL =================
void sendToServer(bool includeHama = false) {
  if (WiFi.status() != WL_CONNECTED) return;

  HTTPClient http;
  WiFiClient client;

  http.begin(client, serverUrl);
  http.addHeader("Content-Type", "application/json");

  String payload = "{";
  payload += "\"temperature\":" + String(temperature, 2) + ",";
  payload += "\"humidity\":" + String(humidity, 2) + ",";
  payload += "\"distance\":" + String(distanceCM) + ",";
  payload += "\"hama\":" + String(includeHama ? "true" : "false") + ",";
  payload += "\"servo\":" + String(servoPos) + ",";
  payload += "\"buzzer\":" + String(digitalRead(BUZZER_PIN));
  payload += "}";

  int httpCode = http.POST(payload);

  if (includeHama) {
    Serial.print("📡 POST HAMA => ");
    Serial.println(httpCode);
  }

  http.end();
}

// ================= SETUP =================
void setup() {
  delay(2000); // bantu hindari boot glitch
  Serial.begin(115200);

  pinMode(IR_PIN, INPUT_PULLUP);
  pinMode(BUZZER_PIN, OUTPUT);
  pinMode(TRIG_PIN, OUTPUT);
  pinMode(ECHO_PIN, INPUT_PULLUP);

  digitalWrite(BUZZER_PIN, LOW);

  myServo.attach(SERVO_PIN);
  myServo.write(0);

  dht.begin();

  // ===== LCD =====
  Wire.begin(D1, D2); // SDA, SCL
  Wire.setClock(50000);

  lcd.begin(16, 2);
  lcd.backlight();
  lcd.clear();
  lcd.print("Smart Agriculture");
  delay(1500);
  lcd.clear();

  // ===== WIFI =====
  WiFi.begin(ssid, password);
  Serial.print("Menghubungkan WiFi");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }

  Serial.println("\n✅ WiFi Terhubung!");
  Serial.print("IP ESP: ");
  Serial.println(WiFi.localIP());
}

// ================= LOOP =================
void loop() {
  unsigned long now = millis();
  bool ir = digitalRead(IR_PIN);

  // ===== DHT =====
  if (now - lastDHTRead >= dhtInterval) {
    lastDHTRead = now;

    temperature = dht.readTemperature();
    humidity = dht.readHumidity();

    if (isnan(temperature) || isnan(humidity)) {
      Serial.println("❌ Gagal baca DHT");
    } else {
      Serial.print("🌡 ");
      Serial.print(temperature);
      Serial.print(" C | 💧 ");
      Serial.print(humidity);
      Serial.println(" %");
    }
  }

  // ===== ULTRASONIC =====
  if (now - lastUltraRead >= ultraInterval) {
    lastUltraRead = now;
    distanceCM = readUltrasonic();

    Serial.print("📏 Jarak: ");
    Serial.print(distanceCM);
    Serial.println(" cm");
  }

  // ===== LCD =====
  if (now - lastLCDUpdate >= lcdInterval) {
    lastLCDUpdate = now;
    lcd.clear();

    if (currentState == ACTIVE) {
      lcd.print("!!! HAMA !!!");
      lcd.setCursor(0, 1);
      lcd.print("Pengusir AKTIF");
    } else {
      if (!lcdPage) {
        lcd.print("T:");
        lcd.print(temperature, 1);
        lcd.print("C");
        lcd.setCursor(0, 1);
        lcd.print("H:");
        lcd.print(humidity, 1);
        lcd.print("%");
      } else {
        lcd.print("Jarak:");
        lcd.setCursor(0, 1);
        lcd.print(distanceCM);
        lcd.print(" cm");
      }
      lcdPage = !lcdPage;
    }
  }

  // ===== STATE MACHINE (IR ONLY) =====
  if (currentState == STANDBY) {
    if (lastIRState == HIGH && ir == LOW && !lockEvent) {
      lockEvent = true;
      currentState = ACTIVE;
      stateStartTime = now;
      lastServoMove = now;

      digitalWrite(BUZZER_PIN, HIGH);
      Serial.println("⚠️ HAMA TERDETEKSI");

      if (!sentEvent) {
        sendToServer(true);
        sentEvent = true;
      }
    }
  } else {
    if (now - lastServoMove >= servoInterval) {
      servoPos = !servoPos;
      myServo.write(servoPos ? 180 : 0);
      lastServoMove = now;
    }

    if (now - stateStartTime >= activeTime) {
      currentState = STANDBY;
      servoPos = false;
      myServo.write(0);
      digitalWrite(BUZZER_PIN, LOW);
      lockEvent = false;
      sentEvent = false;
      Serial.println("✅ MODE STANDBY");
    }
  }

  // ===== SEND PERIODIC =====
  if (now - lastSendData >= sendInterval) {
    lastSendData = now;
    sendToServer(false);
  }

  lastIRState = ir;
}
