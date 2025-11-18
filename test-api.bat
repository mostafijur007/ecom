@echo off
REM Quick Start Script for JWT Auth API Testing (Windows)

echo ======================================
echo JWT Authentication API - Quick Test
echo ======================================
echo.

set BASE_URL=http://localhost:8000/api/v1

echo Step 1: Login as Admin
echo --------------------------------------
curl -X POST %BASE_URL%/auth/login -H "Content-Type: application/json" -d "{\"email\":\"admin@example.com\",\"password\":\"password123\"}"
echo.
echo.

echo Step 2: Login as Vendor
echo --------------------------------------
curl -X POST %BASE_URL%/auth/login -H "Content-Type: application/json" -d "{\"email\":\"vendor@example.com\",\"password\":\"password123\"}"
echo.
echo.

echo Step 3: Login as Customer
echo --------------------------------------
curl -X POST %BASE_URL%/auth/login -H "Content-Type: application/json" -d "{\"email\":\"customer@example.com\",\"password\":\"password123\"}"
echo.
echo.

echo ======================================
echo Test completed!
echo ======================================
echo.
echo IMPORTANT: Copy the access_token from the response above
echo Then use it in the Authorization header:
echo   curl -H "Authorization: Bearer YOUR_TOKEN" %BASE_URL%/admin/dashboard
echo.
echo Test Accounts:
echo   Admin:    admin@example.com / password123
echo   Vendor:   vendor@example.com / password123
echo   Customer: customer@example.com / password123
echo.
echo For detailed testing, use Postman with postman_collection.json
echo.
pause
