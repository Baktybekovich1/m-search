import paramiko

HOST = '45.144.222.33'
USER = 'root'
PASS = 'wmM@S_cEvK5V8o'

def run_it(cmd):
    try:
        ssh = paramiko.SSHClient()
        ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
        ssh.connect(HOST, username=USER, password=PASS, timeout=10)
        
        print(f"--- Running: {cmd} ---")
        stdin, stdout, stderr = ssh.exec_command(cmd)
        
        # Use a timeout for reading
        out = stdout.read().decode('utf-8')
        err = stderr.read().decode('utf-8')
        
        print(f"STDOUT:\n{out}")
        print(f"STDERR:\n{err}")
        
        ssh.close()
    except Exception as e:
        print(f"Error: {e}")

run_it("docker ps --format '{{.Names}}: {{.Status}}'")
run_it("docker logs msearch-nginx-prod --tail 20")
run_it("docker exec msearch-php-prod php bin/console debug:router --env=prod")
run_it("docker exec msearch-php-prod ls -la /var/www/html/public/")
run_it("docker exec msearch-php-prod cat .env")
