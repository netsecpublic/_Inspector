# 🛡️_Inspector (V1.0.1)

A visitor fingerprinting tool designed for **Ubuntu** environments. This tool extracts hardware, display, and network identity data without relying on external third-party APIs.

---

##  Key Features

* **Local GeoIP:** Uses MaxMind `.mmdb` locally for instant IP-to-City lookups.
* **Hardware Fingerprinting:** Generates a unique `SHIELD-ID` via hidden Canvas rendering.
* **GPU Unmasking:** Extracts the actual graphics card model (e.g., NVIDIA/AMD) via WebGL.
* **Display Metrics:** Detects physical resolution (DPI-aware), pixel ratio, and color depth.
* **Automated Logging:** Saves all sessions to `visitor_stats.csv` via asynchronous POST.
* **Modern UI:** High-contrast 2x3 grid layout with a dark-mode aesthetic.

---

##  Requirements

### 1. PHP & MaxMind Extension
Regardless of the web server, you must install the MaxMind DB Reader extension.
```bash
sudo apt update
sudo apt install php8.3-fpm php8.3-maxminddb libmaxminddb-dev mmdb-bin -y
sudo phpenmod maxminddb
sudo systemctl restart php8.3-fpm
