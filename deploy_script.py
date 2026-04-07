#!/usr/bin/env python3
"""Диагностика через paramiko с channel-level timeout"""
import paramiko
import socket
import time

HOST = '45.144.222.33'
USER = 'root'
PASS = 'wmM@S_cEvK5V8o'
PROJECT_DIR = '/opt/msearch'

def run(ssh, cmd, timeout=20):
    """Run command with hard channel timeout"""
    print(f"\n▶ {cmd[:100]}")
    chan = ssh.get_transport().open_session()
    chan.settimeout(timeout)
    chan.set_combine_stderr(True)
    chan.exec_command(cmd)
    chan.shutdown_write()
    out = b''
    deadline = time.time() + timeout
    try:
        while time.time() < deadline:
            if chan.exit_status_ready() and not chan.recv_ready():
                break
            if chan.recv_ready():
                chunk = chan.recv(4096)
                if not chunk:
                    break
                out += chunk
            else:
                time.sleep(0.1)
        # drain
        while chan.recv_ready():
            out += chan.recv(4096)
    except (socket.timeout, Exception):
        pass
    finally:
        chan.close()
    result = out.decode('utf-8', errors='replace').strip()
    print(result[:3000] if result else '(no output)')
    return result

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(HOST, username=USER, password=PASS, timeout=15)
print("✅ Connected\n")

print("="*50)
print("1. Container status")
print("="*50)
run(ssh, "docker ps --format 'table {{.Names}}\\t{{.Status}}\\t{{.Ports}}'")

print("\n" + "="*50)
print("2. Public dir contents")
print("="*50)
run(ssh, f"ls -la {PROJECT_DIR}/public/")

print("\n" + "="*50)
print("3. Vendor exists?")
print("="*50)
run(ssh, f"ls {PROJECT_DIR}/vendor/symfony/ | head -5 2>/dev/null && echo OK || echo MISSING")

print("\n" + "="*50)
print("4. HTTP curl test /")
print("="*50)
run(ssh, "curl -sv http://localhost/ 2>&1 | head -50", timeout=15)

print("\n" + "="*50)
print("5. HTTP curl /api/products")
print("="*50)
run(ssh, "curl -s http://localhost/api/products 2>&1 | head -30", timeout=15)

print("\n" + "="*50)
print("6. Nginx error log")
print("="*50)
run(ssh, "docker exec msearch-nginx-prod tail -30 /var/log/nginx/project_error.log 2>&1")

print("\n" + "="*50)
print("7. PHP-FPM logs")
print("="*50)
run(ssh, "docker logs msearch-php-prod 2>&1 | tail -20")

print("\n" + "="*50)
print("8. var/log/prod.log in container")
print("="*50)
run(ssh, f"docker exec msearch-php-prod tail -30 {PROJECT_DIR}/var/log/prod.log 2>/dev/null || echo 'no prod.log'")

print("\n" + "="*50)
print("9. Check nginx volume - index.php")
print("="*50)
run(ssh, "docker exec msearch-nginx-prod ls -la /var/www/html/public/ 2>&1")

ssh.close()
