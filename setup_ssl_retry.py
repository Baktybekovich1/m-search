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
    
    # 1. Create host directory for challenge
    run_it(ssh, f"mkdir -p {PROJECT_DIR}/certbot-www")
    
    # 2. Update docker-compose.prod.yml to use host path for certbot-www
    # We already uploaded it, but let's fix it on the server if needed
    # Actually, I'll just change the docker run command to use host path too
    
    # 3. Temporary Nginx config
    sftp = ssh.open_sftp()
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
    
    # Update compose to use host path for challenge
    with sftp.file(f'{PROJECT_DIR}/docker-compose.prod.yml', 'r') as f:
        compose_content = f.read().decode('utf-8')
    
    compose_content = compose_content.replace('certbot-www:/var/www/certbot', f'{PROJECT_DIR}/certbot-www:/var/www/certbot')
    
    with sftp.file(f'{PROJECT_DIR}/docker-compose.prod.yml', 'w') as f:
        f.write(compose_content.encode('utf-8'))

    # 4. Restart Nginx
    run_it(ssh, f"cd {PROJECT_DIR} && docker-compose -f docker-compose.prod.yml up -d nginx")
    
    # 5. Get certificate using host path
    print("Running Certbot with host path...")
    run_it(ssh, f"docker run --rm -v /etc/letsencrypt:/etc/letsencrypt -v {PROJECT_DIR}/certbot-www:/var/www/certbot certbot/certbot certonly --webroot -w /var/www/certbot -d {DOMAIN} --email akai.kyb2005@gmail.com --agree-tos --non-interactive")
    
    # 6. Check if cert exists
    st = run_it(ssh, f"ls -la /etc/letsencrypt/live/{DOMAIN}/fullchain.pem")
    if "No such file" in st:
        print("FAILED TO OBTAIN CERTIFICATE. Check logs.")
    else:
        # 7. Restore full config and restart
        print("Restoring full Nginx config...")
        # Since I can't easily read the local file again without sftp put, I'll just put it again
        sftp.put('/home/akai/projects/symfony/msearch/docker/nginx/default.conf', f'{PROJECT_DIR}/docker/nginx/default.conf')
        run_it(ssh, f"cd {PROJECT_DIR} && docker-compose -f docker-compose.prod.yml up -d nginx certbot")
    
    sftp.close()
    ssh.close()
except Exception as e:
    print(f"Error: {e}")
