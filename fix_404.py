import paramiko

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
    
    # 1. Upload updated nelmio_api_doc.yaml
    local_path = '/home/akai/projects/symfony/msearch/config/packages/nelmio_api_doc.yaml'
    remote_path = f'{PROJECT_DIR}/config/packages/nelmio_api_doc.yaml'
    print(f"Uploading {local_path} to {remote_path}")
    sftp.put(local_path, remote_path)
    
    sftp.close()
    
    # 2. Clear cache
    print("Force clearing Symfony cache...")
    run_it(ssh, f"docker exec msearch-php-prod php bin/console cache:clear --env=prod")
    run_it(ssh, f"docker exec msearch-php-prod php bin/console cache:warmup --env=prod")
    
    ssh.close()
    print("Done!")
except Exception as e:
    print(f"Error: {e}")
