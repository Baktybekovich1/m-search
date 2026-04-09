import paramiko
import os

HOST = '45.144.222.33'
USER = 'root'
PASS = 'wmM@S_cEvK5V8o'
REMOTE_PATH = '/root/m-search'

def run_remote(ssh, cmd):
    print(f"\n▶ {cmd}")
    stdin, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode('utf-8', errors='replace')
    err = stderr.read().decode('utf-8', errors='replace')
    if out: print(out)
    if err: print(f"ERR: {err}")
    return out

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())

try:
    ssh.connect(HOST, username=USER, password=PASS, timeout=15)
    print("✅ Connected to Production Server\n")

    # 1. Upload updated docker-compose.prod.yml
    sftp = ssh.open_sftp()
    print("Uploading updated docker-compose.prod.yml...")
    sftp.put('docker-compose.prod.yml', f'{REMOTE_PATH}/docker-compose.prod.yml')
    
    # 2. Upload .env (make sure it contains the GEMINI_API_KEY)
    print("Uploading .env...")
    sftp.put('.env', f'{REMOTE_PATH}/.env')
    sftp.close()

    # 3. Restart containers and rebuild
    print("\nRestarting containers and rebuilding with new configuration...")
    run_remote(ssh, f"cd {REMOTE_PATH} && git pull origin master && docker-compose -f docker-compose.prod.yml up -d --build --force-recreate")

    # 4. Clear cache inside container
    print("\nClearing Symfony cache in container...")
    run_remote(ssh, "docker exec msearch-php-prod php bin/console cache:clear")

    print("\n🚀 Deployment of fix completed successfully!")

finally:
    ssh.close()
