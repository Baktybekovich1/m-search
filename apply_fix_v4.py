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
    
    # 1. Upload updated configs
    configs = [
        ('/home/akai/projects/symfony/msearch/docker/nginx/default.conf', f'{PROJECT_DIR}/docker/nginx/default.conf'),
        ('/home/akai/projects/symfony/msearch/config/packages/nelmio_api_doc.yaml', f'{PROJECT_DIR}/config/packages/nelmio_api_doc.yaml'),
        ('/home/akai/projects/symfony/msearch/public/test.html', f'{PROJECT_DIR}/public/test.html')
    ]
    for local, remote in configs:
        print(f"Uploading {local} to {remote}")
        sftp.put(local, remote)
    
    sftp.close()
    
    # 2. Force restart Nginx (not just up -d)
    print("Restarting Nginx...")
    run_it(ssh, f"cd {PROJECT_DIR} && docker-compose -f docker-compose.prod.yml restart nginx")
    
    # 3. Clear cache and warmup
    print("Force clearing Symfony cache...")
    run_it(ssh, f"docker exec msearch-php-prod php bin/console cache:clear --env=prod")
    run_it(ssh, f"docker exec msearch-php-prod php bin/console cache:warmup --env=prod")
    
    # 4. Verify test.html
    print("Verifying static test.html...")
    run_it(ssh, f"curl -k -I -H \"Host: api.m-search.tw1.su\" https://localhost/test.html")
    
    # 5. Verify documentation
    print("Verifying /api/doc...")
    run_it(ssh, f"curl -k -I -H \"Host: api.m-search.tw1.su\" https://localhost/api/doc")
    
    ssh.close()
    print("Deep fix applied!")
except Exception as e:
    print(f"Error: {e}")
