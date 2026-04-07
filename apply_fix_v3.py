import paramiko
import time

HOST = '45.144.222.33'
USER = 'root'
PASS = 'wmM@S_cEvK5V8o'
PROJECT_DIR = '/opt/msearch'

def run_it(ssh, cmd):
    print(f"Executing: {cmd}")
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=30)
    out = stdout.read().decode('utf-8')
    err = stderr.read().decode('utf-8')
    if out: print(out)
    if err: print(f"Error: {err}")
    return out

try:
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(HOST, username=USER, password=PASS, timeout=10)
    
    sftp = ssh.open_sftp()
    
    # 1. Upload updated Nginx config
    local_nginx = '/home/akai/projects/symfony/msearch/docker/nginx/default.conf'
    remote_nginx = f'{PROJECT_DIR}/docker/nginx/default.conf'
    print(f"Uploading {local_nginx} to {remote_nginx}")
    sftp.put(local_nginx, remote_nginx)
    
    # 2. Upload updated Nelmio config
    local_nelmio = '/home/akai/projects/symfony/msearch/config/packages/nelmio_api_doc.yaml'
    remote_nelmio = f'{PROJECT_DIR}/config/packages/nelmio_api_doc.yaml'
    print(f"Uploading {local_nelmio} to {remote_nelmio}")
    sftp.put(local_nelmio, remote_nelmio)
    
    sftp.close()
    
    # 3. Restart Nginx
    print("Restarting Nginx...")
    run_it(ssh, f"cd {PROJECT_DIR} && docker-compose -f docker-compose.prod.yml up -d nginx")
    
    # 4. Clear cache again
    print("Force clearing Symfony cache...")
    run_it(ssh, f"docker exec msearch-php-prod php bin/console cache:clear --env=prod")
    run_it(ssh, f"docker exec msearch-php-prod php bin/console cache:warmup --env=prod")
    
    # 5. Final check
    print("Verifying...")
    run_it(ssh, f"curl -k -I -H \"Host: api.m-search.tw1.su\" https://localhost/api/doc")
    
    ssh.close()
    print("Fix applied!")
except Exception as e:
    print(f"Error: {e}")
