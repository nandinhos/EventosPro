# Docker Troubleshooting Guide

This document outlines common Docker issues encountered in the EventosPro VPS environment and how to resolve them.

## 🚨 Critical Issue: Snap vs Apt Conflict

**Symptoms:**
- `docker ps` returns "Connection reset by peer" or "Cannot connect to the Docker daemon".
- `sudo docker ps` might work, but regular user access fails even if the user is in the `docker` group.
- `curl --unix-socket /var/run/docker.sock` fails with connection reset.

**Cause:**
Ubuntu 24.04 (and others) may come with a Snap version of Docker pre-installed or automatically installed. If the standard Apt version (from Docker's official repo) is also installed, they conflict. The Snap version often hijacks the `/var/run/docker.sock` socket or interferes with the daemon.

**Solution:**
We must remove the Snap version and rely solely on the official Apt version.

### 🛠️ Automatic Fix
Run the provided conflict resolution script:

```bash
sudo ./scripts/docker-fix-conflict.sh
```

This script will:
1. Remove the Docker Snap package.
2. Stop all Docker services.
3. Remove the socket file.
4. Restart the Apt-based Docker service.

---

## 🔧 General Troubleshooting

If Docker is still not working, follow these steps using the provided utility scripts.

### 1. Diagnose and Repair Permissions
If you have "Permission denied" errors:

```bash
sudo ./scripts/docker-repair.sh
```
*Checks service status, socket permissions, and user group membership.*

### 2. Reinstall Docker (Preserving Data)
If the installation is corrupted but you need to keep your containers/volumes:

```bash
sudo ./scripts/docker-reinstall.sh
```
*Removes Docker packages but keeps `/var/lib/docker`, then reinstalls the latest version.*

---

## 📝 Manual Verification Steps

1. **Check Service Status:**
   ```bash
   systemctl status docker
   ```
   Should be `active (running)`.

2. **Check Socket Permissions:**
   ```bash
   ls -l /var/run/docker.sock
   ```
   Should be owned by `root:docker` with permissions `srw-rw----`.

3. **Check User Group:**
   ```bash
   groups
   ```
   Output must include `docker`. If not, run `sudo usermod -aG docker $USER` and log out/in.

4. **Verify Connectivity:**
   ```bash
   docker ps
   ```
   Should list containers or empty headers without error.
