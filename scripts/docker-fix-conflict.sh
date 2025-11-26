#!/bin/bash

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${YELLOW}🔍 Resolving Docker Conflict (Snap vs Apt)...${NC}"

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
  echo -e "${RED}Please run as root (use sudo)${NC}"
  exit 1
fi

# 1. Identify and Remove Snap Docker
if snap list | grep -q "docker"; then
    echo -e "${YELLOW}Found Docker Snap installation. Removing...${NC}"
    snap stop docker
    snap remove docker
    echo -e "${GREEN}Docker Snap removed.${NC}"
else
    echo -e "${GREEN}No Docker Snap found.${NC}"
fi

# 2. Stop all Docker services to clear the state
echo -e "${YELLOW}Stopping Docker services...${NC}"
systemctl stop docker
systemctl stop docker.socket

# 3. Ensure Socket is gone
rm -f /var/run/docker.sock

# 4. Restart Apt Docker
echo -e "${YELLOW}Starting Docker (Apt version)...${NC}"
systemctl start docker
systemctl enable docker

# 5. Verify
echo -e "${YELLOW}Verifying...${NC}"
sleep 2
if systemctl is-active --quiet docker; then
    echo -e "${GREEN}Docker service is running.${NC}"
else
    echo -e "${RED}Docker service failed to start.${NC}"
    systemctl status docker --no-pager
    exit 1
fi

# 6. Check Socket Permissions
ls -l /var/run/docker.sock

echo -e "${GREEN}✅ Conflict resolved. Please try running 'docker ps' as your normal user now.${NC}"
