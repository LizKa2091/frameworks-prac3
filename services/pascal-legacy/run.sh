#!/usr/bin/env bash
set -e
echo "[pascal] compiling legacy.pas"
fpc -O2 -S2 legacy.pas
echo "[pascal] running legacy CSV generator and importer"
./legacy &
LEGACY_PID=$!

# Ждем генерации CSV и конвертируем в XLSX
sleep 5
CSV_DIR=${CSV_OUT_DIR:-/data/csv}
echo "[pascal] converting CSV to XLSX"
while true; do
    sleep 10
    for csv_file in "$CSV_DIR"/*.csv; do
        if [ -f "$csv_file" ]; then
            xlsx_file="${csv_file%.csv}.xlsx"
            if [ ! -f "$xlsx_file" ] || [ "$csv_file" -nt "$xlsx_file" ]; then
                echo "[pascal] converting $csv_file to $xlsx_file"
                python3 /opt/legacy/csv_to_xlsx.py "$csv_file" "$xlsx_file" || true
            fi
        fi
    done
done &
XLSX_PID=$!

wait $LEGACY_PID