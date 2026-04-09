import paramiko
import time

HOST = '45.144.222.33'
USER = 'root'
PASS = 'wmM@S_cEvK5V8o'

def run_remote(ssh, cmd):
    print(f"\n▶ {cmd}")
    stdin, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode('utf-8', errors='replace')
    err = stderr.read().decode('utf-8', errors='replace')
    if out: print(out)
    if err: print(f"ERR: {err}")
    return out, err

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())

try:
    ssh.connect(HOST, username=USER, password=PASS, timeout=15)
    print("✅ Connected to Production Server\n")

    print("-" * 40)
    print("Checking Environment Variables in Container")
    print("-" * 40)
    run_remote(ssh, "docker exec msearch-php-prod env | grep GEMINI")

    print("\n" + "-" * 40)
    print("Checking DNS resolution")
    print("-" * 40)
    run_remote(ssh, "docker exec msearch-php-prod getent hosts generativelanguage.googleapis.com")

    print("\n" + "-" * 40)
    print("Checking External Connectivity (Google API)")
    print("-" * 40)
    # Get the key first to use it in the curl command directly if needed, 
    # but we can also just use the shell variable if it's there.
    run_remote(ssh, "docker exec msearch-php-prod curl -Is https://generativelanguage.googleapis.com/ | head -n 1")

    print("\n" + "-" * 40)
    print("Testing Gemini API with Key (Heads only)")
    print("-" * 40)
    run_remote(ssh, "docker exec msearch-php-prod sh -c 'curl -Is \"https://generativelanguage.googleapis.com/v1beta/models/gemini-3-flash-preview:generateContent?key=$GEMINI_API_KEY\"' | head -n 1")

    print("\n" + "-" * 40)
    print("Checking Nginx Error Logs (Last 50 lines)")
    print("-" * 40)
    run_remote(ssh, "docker exec msearch-nginx-prod tail -n 50 /var/log/nginx/error.log")

finally:
    ssh.close()
