#!/bin/bash

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${YELLOW}🔄 Reinstalling Docker (Preserving Data)...${NC}"

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
  echo -e "${RED}Please run as root (use sudo)${NC}"
  exit 1
fi

# 1. Uninstall old versions
echo -e "${YELLOW}Removing existing Docker packages...${NC}"
for pkg in docker.io docker-doc docker-compose docker-compose-v2 podman-docker containerd runc; do apt-get remove -y $pkg; done
apt-get remove -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

# 2. Setup Repository
echo -e "${YELLOW}Setting up Docker repository...${NC}"
apt-get update
apt-get install -y ca-certificates curl
install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
chmod a+r /etc/apt/keyrings/docker.asc

echo \
  "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu \
  $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | \
  tee /etc/apt/sources.list.d/docker.list > /dev/null

apt-get update

# 3. Install Docker
echo -e "${YELLOW}Installing Docker Engine...${NC}"
apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

# 4. Configure User
echo -e "${YELLOW}Configuring user permissions...${NC}"
REAL_USER=${SUDO_USER:-$USER}
usermod -aG docker "$REAL_USER"

# 5. Start and Enable
echo -e "${YELLOW}Starting Docker...${NC}"
systemctl enable docker
systemctl start docker

# 6. Verify
echo -e "${YELLOW}Verifying installation...${NC}"
if docker ps >/dev/null 2>&1; then
    echo -e "${GREEN}✅ Docker reinstalled and working!${NC}"
    docker ps
else
    echo -e "${RED}❌ Docker command failed. Try logging out and back in.${NC}"
fi
