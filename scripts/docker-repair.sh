#!/bin/bash

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${YELLOW}🔍 Diagnosing and Repairing Docker...${NC}"

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
  echo -e "${RED}Please run as root (use sudo)${NC}"
  exit 1
fi

# 1. Check Service Status
echo -e "${YELLOW}Checking Docker service status...${NC}"
systemctl status docker --no-pager

# 2. Restart Service
echo -e "${YELLOW}Restarting Docker service...${NC}"
systemctl restart docker
if [ $? -eq 0 ]; then
    echo -e "${GREEN}Docker service restarted successfully.${NC}"
else
    echo -e "${RED}Failed to restart Docker service.${NC}"
    echo -e "${YELLOW}Checking logs...${NC}"
    journalctl -u docker --no-pager -n 20
fi

# 3. Fix Socket Permissions
echo -e "${YELLOW}Fixing Docker socket permissions...${NC}"
if [ -S /var/run/docker.sock ]; then
    chown root:docker /var/run/docker.sock
    chmod 660 /var/run/docker.sock
    echo -e "${GREEN}Socket permissions updated.${NC}"
else
    echo -e "${RED}Docker socket /var/run/docker.sock not found! Is the daemon running?${NC}"
fi

# 4. Verify User Group
echo -e "${YELLOW}Verifying user group membership...${NC}"
# Get the user who invoked sudo
REAL_USER=${SUDO_USER:-$USER}
if groups "$REAL_USER" | grep &>/dev/null '\bdocker\b'; then
    echo -e "${GREEN}User $REAL_USER is in the docker group.${NC}"
else
    echo -e "${YELLOW}Adding user $REAL_USER to docker group...${NC}"
    usermod -aG docker "$REAL_USER"
    echo -e "${GREEN}User added. You may need to log out and back in.${NC}"
fi

# 5. Test Docker
echo -e "${YELLOW}Testing Docker access...${NC}"
if docker ps >/dev/null 2>&1; then
    echo -e "${GREEN}✅ Docker is working correctly!${NC}"
    docker ps
else
    echo -e "${RED}❌ Docker command failed.${NC}"
    echo -e "${YELLOW}Trying with sudo...${NC}"
    if sudo docker ps >/dev/null 2>&1; then
        echo -e "${YELLOW}Docker works with sudo but not without. Please log out and log back in.${NC}"
    else
        echo -e "${RED}Docker failed even with sudo.${NC}"
    fi
fi
