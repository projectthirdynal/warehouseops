#!/bin/bash
#######################################################################################
# Load Balancer Testing Script
# Tests load distribution and health checks
#######################################################################################

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

LB_IP=$1

if [ -z "$LB_IP" ]; then
    echo -e "${RED}Usage: $0 <load-balancer-ip>${NC}"
    exit 1
fi

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Load Balancer Testing${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""

#######################
# Test Basic Connectivity
#######################
echo -e "${YELLOW}Test 1: Basic connectivity...${NC}"
if curl -s -o /dev/null -w "%{http_code}" http://$LB_IP/ | grep -q "200\|302"; then
    echo -e "${GREEN}✓ Load balancer is responding${NC}"
else
    echo -e "${RED}✗ Load balancer not responding${NC}"
    exit 1
fi
echo ""

#######################
# Test Health Check
#######################
echo -e "${YELLOW}Test 2: Health check endpoint...${NC}"
HEALTH_RESPONSE=$(curl -s http://$LB_IP/health)
if [ "$HEALTH_RESPONSE" = "healthy" ]; then
    echo -e "${GREEN}✓ Health check passed${NC}"
else
    echo -e "${YELLOW}⚠ Unexpected health check response: $HEALTH_RESPONSE${NC}"
fi
echo ""

#######################
# Test Load Distribution
#######################
echo -e "${YELLOW}Test 3: Load distribution (100 requests)...${NC}"
declare -A server_counts

for i in {1..100}; do
    # Make request and check which server responded
    COOKIE=$(curl -s -c - http://$LB_IP/ | grep SERVERID | awk '{print $7}')
    
    if [[ $COOKIE == *"app1"* ]]; then
        ((server_counts[app1]++))
    elif [[ $COOKIE == *"app2"* ]]; then
        ((server_counts[app2]++))
    fi
    
    # Show progress every 20 requests
    if [ $((i % 20)) -eq 0 ]; then
        echo -n "."
    fi
done
echo ""

echo "Distribution results:"
echo "  App Server 1: ${server_counts[app1]:-0} requests"
echo "  App Server 2: ${server_counts[app2]:-0} requests"

# Check if distribution is reasonably balanced (40-60% each)
total=$((${server_counts[app1]:-0} + ${server_counts[app2]:-0}))
if [ $total -gt 0 ]; then
    app1_percent=$((${server_counts[app1]:-0} * 100 / total))
    
    if [ $app1_percent -ge 40 ] && [ $app1_percent -le 60 ]; then
        echo -e "${GREEN}✓ Load distribution is balanced${NC}"
    else
        echo -e "${YELLOW}⚠ Load distribution may be unbalanced${NC}"
    fi
else
    echo -e "${RED}✗ Could not determine load distribution${NC}"
fi
echo ""

#######################
# Test Sticky Sessions
#######################
echo -e "${YELLOW}Test 4: Sticky sessions...${NC}"
COOKIE_FILE="/tmp/lb_test_cookie.txt"
rm -f $COOKIE_FILE

# Make first request and save cookie
curl -s -c $COOKIE_FILE http://$LB_IP/ > /dev/null
FIRST_SERVER=$(grep SERVERID $COOKIE_FILE | awk '{print $7}')

# Make 10 more requests with the same cookie
SAME_SERVER=true
for i in {1..10}; do
    curl -s -b $COOKIE_FILE -c $COOKIE_FILE http://$LB_IP/ > /dev/null
    CURRENT_SERVER=$(grep SERVERID $COOKIE_FILE | awk '{print $7}')
    
    if [ "$CURRENT_SERVER" != "$FIRST_SERVER" ]; then
        SAME_SERVER=false
        break
    fi
done

if $SAME_SERVER; then
    echo -e "${GREEN}✓ Sticky sessions working (all requests to same server)${NC}"
else
    echo -e "${RED}✗ Sticky sessions not working correctly${NC}"
fi

rm -f $COOKIE_FILE
echo ""

#######################
# Test Statistics Dashboard
#######################
echo -e "${YELLOW}Test 5: Statistics dashboard...${NC}"
if curl -s -o /dev/null -w "%{http_code}" http://$LB_IP:8404/stats | grep -q "200\|401"; then
    echo -e "${GREEN}✓ Statistics dashboard available at http://$LB_IP:8404/stats${NC}"
    echo "  (Default credentials: admin / admin123)"
else
    echo -e "${YELLOW}⚠ Statistics dashboard not accessible${NC}"
fi
echo ""

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Testing Complete!${NC}"
echo -e "${GREEN}========================================${NC}"
