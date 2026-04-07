import paramiko
import time

HOST = '45.144.222.33'
USER = 'root'
PASS = 'wmM@S_cEvK5V8o'

def run_command(ssh, cmd):
    print(f"Executing: {cmd}")
    stdin, stdout, stderr = ssh.exec_command(cmd)
    
    # Read output and error
    out = stdout.read().decode('utf-8')
    err = stderr.read().decode('utf-8')
    
    if out: print(f"STDOUT:\n{out}")
    if err: print(f"STDERR:\n{err}")
    
    return out, err

try:
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(HOST, username=USER, password=PASS)
    
    print("--- 1. Routes ---")
    run_command(ssh, "docker exec msearch-php-prod php bin/console debug:router --env=prod")
    
    print("--- 2. Nginx Public Dir ---")
    run_command(ssh, "docker exec msearch-nginx-prod ls -la /var/www/html/public/")
    
    print("--- 3. Nginx Logs ---")
    run_command(ssh, "docker logs msearch-nginx-prod --tail 10")
    
    print("--- 4. PHP Logs ---")
    run_command(ssh, "docker logs msearch-php-prod --tail 10")
    
    print("--- 5. .env inside PHP ---")
    run_command(ssh, "docker exec msearch-php-prod cat .env")

    ssh.close()
except Exception as e:
    print(f"Error: {e}")
