import paramiko

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

    run_remote(ssh, f"cd {REMOTE_PATH} && git status")
    run_remote(ssh, f"cd {REMOTE_PATH} && git log -n 3")

finally:
    ssh.close()
