import paramiko
import time

HOST = '45.144.222.33'
USER = 'root'
PASS = 'wmM@S_cEvK5V8o'
DOMAIN = 'api.m-search.tw1.su'
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
    
    # 1. Upload updated configs
    print("Uploading configs...")
    sftp.put('/home/akai/projects/symfony/msearch/docker/nginx/default.conf', f'{PROJECT_DIR}/docker/nginx/default.conf')
    sftp.put('/home/akai/projects/symfony/msearch/docker-compose.prod.yml', f'{PROJECT_DIR}/docker-compose.prod.yml')

    # 2. Temporary Nginx config (no SSL) for certbot
    print("Creating temporary Nginx config for HTTP-01 challenge...")
    temp_nginx = f"""
server {{
    listen 80;
    server_name {DOMAIN};
    location /.well-known/acme-challenge/ {{
        root /var/www/certbot;
    }}
}}
"""
    with sftp.file(f'{PROJECT_DIR}/docker/nginx/default.conf', 'w') as f:
        f.write(temp_nginx)
    
    # 3. Start Nginx
    print("Starting Nginx in temporary mode...")
    run_it(ssh, f"cd {PROJECT_DIR} && docker-compose -f docker-compose.prod.yml up -d nginx")
    
    # 4. Get certificate
    print("Running Certbot...")
    run_it(ssh, f"docker run --rm -v /etc/letsencrypt:/etc/letsencrypt -v certbot-www:/var/www/certbot certbot/certbot certonly --webroot -w /var/www/certbot -d {DOMAIN} --email akai.kyb2005@gmail.com --agree-tos --non-interactive")
    
    # 5. Restore full Nginx config (with SSL)
    print("Restoring full Nginx config...")
    sftp.put('/home/akai/projects/symfony/msearch/docker/nginx/default.conf', f'{PROJECT_DIR}/docker/nginx/default.conf')
    
    # 6. Restart Nginx
    print("Restarting Nginx with SSL...")
    run_it(ssh, f"cd {PROJECT_DIR} && docker-compose -f docker-compose.prod.yml up -d nginx certbot")
    
    sftp.close()
    ssh.close()
    print("SSL setup complete!")
except Exception as e:
    print(f"Error: {e}")
