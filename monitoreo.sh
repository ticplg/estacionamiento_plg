#!/bin/bash
# ===============================================================
# Script de Diagnóstico para MySQL/MariaDB
# Fecha: 2025-11-10
# Uso: sudo bash monitoreo.sh
# ===============================================================

echo "==============================================================="
echo "🧠  Diagnóstico MySQL - $(date)"
echo "==============================================================="

# 1️⃣ Verificar servicio MySQL
echo -e "\n[1] Estado del servicio:"
systemctl status mysql --no-pager | grep -E "Active:|since|Main PID"

# 2️⃣ Versión de MySQL
echo -e "\n[2] Versión del motor:"
mysql -V

# 3️⃣ Uptime y conexiones
echo -e "\n[3] Uptime y conexiones:"
mysql -e "SHOW GLOBAL STATUS LIKE 'Uptime';"
mysql -e "SHOW GLOBAL STATUS LIKE 'Threads_connected';"
mysql -e "SHOW GLOBAL STATUS LIKE 'Max_used_connections';"

# 4️⃣ Uso de memoria (RAM y Swap)
echo -e "\n[4] Consumo de memoria del proceso MySQL:"
ps -eo pid,comm,%mem,%cpu --sort=-%mem | grep mysqld | head -n 3
free -h | grep -E "Mem|Swap"

# 5️⃣ Variables de configuración críticas
echo -e "\n[5] Variables InnoDB críticas:"
mysql -e "SHOW VARIABLES LIKE 'innodb_buffer_pool_size';"
mysql -e "SHOW VARIABLES LIKE 'innodb_log_file_size';"
mysql -e "SHOW VARIABLES LIKE 'innodb_redo_log_capacity';"
mysql -e "SHOW VARIABLES LIKE 'max_connections';"
mysql -e "SHOW VARIABLES LIKE 'tmp_table_size';"
mysql -e "SHOW VARIABLES LIKE 'max_heap_table_size';"

# 6️⃣ Últimos 10 errores del log de MySQL
echo -e "\n[6] Últimos 10 eventos del log de MySQL:"
LOGFILE=$(mysql -e "SHOW VARIABLES LIKE 'log_error';" | awk 'NR==2 {print $2}')
if [ -f "$LOGFILE" ]; then
    tail -n 10 "$LOGFILE"
else
    echo "⚠️ No se encontró el archivo de log en la ruta configurada."
fi

# 7️⃣ Espacio en disco
echo -e "\n[7] Uso de disco en /var/lib/mysql:"
du -sh /var/lib/mysql

# 8️⃣ Tablas grandes
echo -e "\n[8] Tablas más pesadas (Top 10):"
mysql -e "SELECT table_schema AS BD, table_name AS Tabla,
ROUND((data_length + index_length)/1024/1024,2) AS MB
FROM information_schema.tables
ORDER BY MB DESC LIMIT 10;"

echo -e "\n✅ Diagnóstico completo."
echo "==============================================================="

