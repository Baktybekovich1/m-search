import paramiko
import os
import secrets
import string

HOST = '45.144.222.33'
USER = 'root'
PASS = 'wmM@S_cEvK5V8o'
PROJECT_DIR = '/opt/msearch'

def run_it(ssh, cmd):
    print(f"Executing: {cmd}")
    stdin, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode('utf-8')
    err = stderr.read().decode('utf-8')
    if out: print(out)
    if err: print(f"Error: {err}")
    return out

try:
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(HOST, username=USER, password=PASS)
    
    sftp = ssh.open_sftp()
    
    # 1. Upload RedirectController.php
    local_path = '/home/akai/projects/symfony/msearch/src/Controller/RedirectController.php'
    remote_path = f'{PROJECT_DIR}/src/Controller/RedirectController.php'
    print(f"Uploading {local_path} to {remote_path}")
    sftp.put(local_path, remote_path)
    
    # 2. Update .env with APP_SECRET
    print("Updating .env")
    env_path = f'{PROJECT_DIR}/.env'
    with sftp.file(env_path, 'r') as f:
        lines = f.readlines()
    
    new_secret = ''.join(secrets.choice(string.ascii_letters + string.digits) for _ in range(32))
    new_lines = []
    found_secret = False
    for line in lines:
        if line.startswith('APP_SECRET='):
            new_lines.append(f'APP_SECRET={new_secret}\n')
            found_secret = True
        else:
            new_lines.append(line)
    
    if not found_secret:
        # Insert after APP_ENV if possible
        for i, line in enumerate(new_lines):
            if line.startswith('APP_ENV='):
                new_lines.insert(i+1, f'APP_SECRET={new_secret}\n')
                found_secret = True
                break
    
    if not found_secret:
        new_lines.append(f'APP_SECRET={new_secret}\n')
        
    with sftp.file(env_path, 'w') as f:
        f.writelines(new_lines)
    
    sftp.close()
    
    # 3. Clear cache
    print("Clearing cache...")
    run_it(ssh, f"docker exec msearch-php-prod php bin/console cache:clear --env=prod")
    run_it(ssh, f"docker exec msearch-php-prod php bin/console cache:warmup --env=prod")
    
    ssh.close()
    print("Done!")
except Exception as e:
    print(f"Error: {e}")
