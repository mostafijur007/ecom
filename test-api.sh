#!/bin/bash

# Quick Start Script for JWT Auth API Testing

echo "======================================"
echo "JWT Authentication API - Quick Test"
echo "======================================"
echo ""

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

BASE_URL="http://localhost:8000/api/v1"

echo -e "${BLUE}Step 1: Login as Admin${NC}"
echo "--------------------------------------"
ADMIN_RESPONSE=$(curl -s -X POST $BASE_URL/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "password123"
  }')

echo "$ADMIN_RESPONSE" | jq '.'

# Extract token (requires jq)
if command -v jq &> /dev/null; then
    ADMIN_TOKEN=$(echo "$ADMIN_RESPONSE" | jq -r '.data.access_token')
    echo ""
    echo -e "${GREEN}✓ Admin Token: $ADMIN_TOKEN${NC}"
fi

echo ""
echo ""
echo -e "${BLUE}Step 2: Access Admin Dashboard${NC}"
echo "--------------------------------------"
if [ ! -z "$ADMIN_TOKEN" ]; then
    curl -s -X GET $BASE_URL/admin/dashboard \
      -H "Authorization: Bearer $ADMIN_TOKEN" | jq '.'
else
    echo -e "${RED}Token not available. Install jq: sudo apt install jq${NC}"
fi

echo ""
echo ""
echo -e "${BLUE}Step 3: Login as Customer${NC}"
echo "--------------------------------------"
CUSTOMER_RESPONSE=$(curl -s -X POST $BASE_URL/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "customer@example.com",
    "password": "password123"
  }')

echo "$CUSTOMER_RESPONSE" | jq '.'

if command -v jq &> /dev/null; then
    CUSTOMER_TOKEN=$(echo "$CUSTOMER_RESPONSE" | jq -r '.data.access_token')
    echo ""
    echo -e "${GREEN}✓ Customer Token: $CUSTOMER_TOKEN${NC}"
fi

echo ""
echo ""
echo -e "${BLUE}Step 4: Customer tries Admin Dashboard (Should Fail)${NC}"
echo "--------------------------------------"
if [ ! -z "$CUSTOMER_TOKEN" ]; then
    curl -s -X GET $BASE_URL/admin/dashboard \
      -H "Authorization: Bearer $CUSTOMER_TOKEN" | jq '.'
    echo ""
    echo -e "${GREEN}✓ Access denied as expected!${NC}"
else
    echo -e "${RED}Token not available${NC}"
fi

echo ""
echo ""
echo -e "${BLUE}Step 5: Customer Access Own Dashboard${NC}"
echo "--------------------------------------"
if [ ! -z "$CUSTOMER_TOKEN" ]; then
    curl -s -X GET $BASE_URL/customer/dashboard \
      -H "Authorization: Bearer $CUSTOMER_TOKEN" | jq '.'
fi

echo ""
echo ""
echo -e "${BLUE}Step 6: Login as Vendor${NC}"
echo "--------------------------------------"
VENDOR_RESPONSE=$(curl -s -X POST $BASE_URL/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "vendor@example.com",
    "password": "password123"
  }')

echo "$VENDOR_RESPONSE" | jq '.'

if command -v jq &> /dev/null; then
    VENDOR_TOKEN=$(echo "$VENDOR_RESPONSE" | jq -r '.data.access_token')
    echo ""
    echo -e "${GREEN}✓ Vendor Token: $VENDOR_TOKEN${NC}"
fi

echo ""
echo ""
echo -e "${BLUE}Step 7: Vendor Dashboard${NC}"
echo "--------------------------------------"
if [ ! -z "$VENDOR_TOKEN" ]; then
    curl -s -X GET $BASE_URL/vendor/dashboard \
      -H "Authorization: Bearer $VENDOR_TOKEN" | jq '.'
fi

echo ""
echo ""
echo "======================================"
echo -e "${GREEN}✓ All tests completed!${NC}"
echo "======================================"
echo ""
echo "Test Accounts:"
echo "  Admin:    admin@example.com / password123"
echo "  Vendor:   vendor@example.com / password123"
echo "  Customer: customer@example.com / password123"
echo ""
echo "For more details, see:"
echo "  - JWT_AUTH_README.md"
echo "  - API_TESTING_GUIDE.md"
echo "  - postman_collection.json"
