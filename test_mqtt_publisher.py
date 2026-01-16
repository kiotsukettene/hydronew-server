#!/usr/bin/env python3
"""
MQTT Sensor Data Publisher Test Script
Simulates 3 sensor systems sending data every 10 seconds
"""

import paho.mqtt.client as mqtt
import json
import time
import random
from datetime import datetime

# Configuration
BROKER = ""  
PORT = 8883  # Use 1883 for non-TLS
USERNAME = "Biotech"
PASSWORD = ""
DEVICE_ID = 1
USE_TLS = True

def on_connect(client, userdata, flags, rc):
    """Callback when connected to MQTT broker"""
    status = {
        0: "Connected successfully",
        1: "Connection refused - incorrect protocol version",
        2: "Connection refused - invalid client identifier",
        3: "Connection refused - server unavailable",
        4: "Connection refused - bad username or password",
        5: "Connection refused - not authorized"
    }
    print(f"[{datetime.now().strftime('%H:%M:%S')}] {status.get(rc, f'Unknown error: {rc}')}")

def on_publish(client, userdata, mid):
    """Callback when message is published"""
    pass

def generate_clean_water_data():
    """Generate simulated clean water sensor data"""
    return {
        "pH": round(7.2 + random.uniform(-0.3, 0.3), 2),
        "TDS": round(150 + random.uniform(-20, 20), 2),
        "Turbidity": round(2.5 + random.uniform(-0.5, 0.5), 2),
        "WaterLevel": round(75 + random.uniform(-10, 10), 2)
    }

def generate_dirty_water_data():
    """Generate simulated dirty water sensor data"""
    return {
        "pH": round(6.8 + random.uniform(-0.4, 0.4), 2),
        "TDS": round(350 + random.uniform(-30, 30), 2),
        "Turbidity": round(8.5 + random.uniform(-1, 1), 2),
        "WaterLevel": round(60 + random.uniform(-10, 10), 2)
    }

def generate_hydroponics_data():
    """Generate simulated hydroponics sensor data"""
    return {
        "pH": round(6.5 + random.uniform(-0.3, 0.3), 2),
        "TDS": round(800 + random.uniform(-50, 50), 2),
        "Humidity": round(65 + random.uniform(-5, 5), 2)
    }

def publish_sensor_data():
    """Main function to publish sensor data"""
    
    # Setup MQTT client
    client = mqtt.Client(client_id=f"test_publisher_{DEVICE_ID}")
    client.username_pw_set(USERNAME, PASSWORD)
    
    if USE_TLS:
        client.tls_set()
        # For self-signed certificates, uncomment:
        # client.tls_insecure_set(True)
    
    client.on_connect = on_connect
    client.on_publish = on_publish
    
    # Connect to broker
    print(f"Connecting to {BROKER}:{PORT}...")
    try:
        client.connect(BROKER, PORT, 60)
    except Exception as e:
        print(f"Error connecting to broker: {e}")
        return
    
    client.loop_start()
    
    # Give it a moment to connect
    time.sleep(2)
    
    print(f"\nStarting to publish sensor data every 10 seconds...")
    print(f"Device ID: {DEVICE_ID}")
    print(f"Press Ctrl+C to stop\n")
    print("-" * 80)
    
    try:
        iteration = 0
        while True:
            iteration += 1
            timestamp = datetime.now().strftime('%H:%M:%S')
            
            print(f"\n[{timestamp}] Iteration #{iteration}")
            
            # Publish clean water data
            clean_data = generate_clean_water_data()
            topic_clean = f"sensors/{DEVICE_ID}/clean_water"
            result_clean = client.publish(topic_clean, json.dumps(clean_data), qos=1)
            print(f"  ✓ Clean Water     -> {clean_data}")
            
            # Publish dirty water data
            dirty_data = generate_dirty_water_data()
            topic_dirty = f"sensors/{DEVICE_ID}/dirty_water"
            result_dirty = client.publish(topic_dirty, json.dumps(dirty_data), qos=1)
            print(f"  ✓ Dirty Water     -> {dirty_data}")
            
            # Publish hydroponics data
            hydro_data = generate_hydroponics_data()
            topic_hydro = f"sensors/{DEVICE_ID}/hydroponics_water"
            result_hydro = client.publish(topic_hydro, json.dumps(hydro_data), qos=1)
            print(f"  ✓ Hydroponics     -> {hydro_data}")
            
            # Check if messages were queued successfully
            if result_clean.rc != mqtt.MQTT_ERR_SUCCESS:
                print(f"  ⚠ Failed to queue clean_water message")
            if result_dirty.rc != mqtt.MQTT_ERR_SUCCESS:
                print(f"  ⚠ Failed to queue dirty_water message")
            if result_hydro.rc != mqtt.MQTT_ERR_SUCCESS:
                print(f"  ⚠ Failed to queue hydroponics_water message")
            
            print("-" * 80)
            time.sleep(10)  # Wait 10 seconds
            
    except KeyboardInterrupt:
        print("\n\nStopping publisher...")
        client.loop_stop()
        client.disconnect()
        print("Disconnected from broker. Goodbye!")

if __name__ == "__main__":
    print("""
╔══════════════════════════════════════════════════════════════╗
║          MQTT Sensor Data Publisher Test Script             ║
╚══════════════════════════════════════════════════════════════╝
    """)
    
    # Configuration summary
    print("Configuration:")
    print(f"  Broker:    {BROKER}:{PORT}")
    print(f"  Username:  {USERNAME}")
    print(f"  Device ID: {DEVICE_ID}")
    print(f"  TLS:       {'Enabled' if USE_TLS else 'Disabled'}")
    print(f"  Interval:  10 seconds")
    print()
    
    publish_sensor_data()