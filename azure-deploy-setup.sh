#!/bin/bash

# Azure Deployment Helper Script for Dua Insan Story
# This script automates the Azure infrastructure setup

set -e

echo "=========================================="
echo "Azure Deployment Helper"
echo "Dua Insan Story - Laravel Application"
echo "=========================================="
echo ""

# Configuration
RESOURCE_GROUP="dua-insan-rg"
LOCATION="indonesiacentral"
MYSQL_SERVER="dua-insan-mysql"
REDIS_NAME="dua-insan-redis"
KEYVAULT_NAME="dua-insan-kv$(date +%s | tail -c 5)" # Add random suffix to ensure uniqueness
STORAGE_ACCOUNT="duainsanstorage$RANDOM"
APP_SERVICE_PLAN="dua-insan-plan"
WEB_APP_NAME="dua-insan-api"
INSIGHTS_NAME="dua-insan-insights"

# Database configuration
DB_ADMIN_USER="dbadmin"
DB_NAME="dua_insan_production"

echo "Configuration:"
echo "  Resource Group: $RESOURCE_GROUP"
echo "  Location: $LOCATION"
echo "  Web App: $WEB_APP_NAME"
echo ""

# Check if resource group exists
echo "Checking if resource group exists..."
if az group show --name $RESOURCE_GROUP &> /dev/null; then
    echo "✓ Resource group '$RESOURCE_GROUP' already exists"
else
    echo "Creating resource group..."
    az group create \
        --name $RESOURCE_GROUP \
        --location $LOCATION
    echo "✓ Resource group created"
fi

echo ""
echo "=========================================="
echo "Phase 1: Creating MySQL Database"
echo "=========================================="

# Check if MySQL server exists
if az mysql flexible-server show --resource-group $RESOURCE_GROUP --name $MYSQL_SERVER &> /dev/null; then
    echo "✓ MySQL server already exists"
else
    echo "Please enter a secure password for MySQL database:"
    read -s DB_PASSWORD
    echo ""
    
    echo "Creating MySQL Flexible Server..."
    az mysql flexible-server create \
        --resource-group $RESOURCE_GROUP \
        --name $MYSQL_SERVER \
        --location $LOCATION \
        --admin-user $DB_ADMIN_USER \
        --admin-password "$DB_PASSWORD" \
        --sku-name Standard_B1ms \
        --tier Burstable \
        --version 8.0.21 \
        --storage-size 20 \
        --public-access 0.0.0.0 \
        --backup-retention 7
    echo "✓ MySQL server created"
    
    echo "Creating database..."
    az mysql flexible-server db create \
        --resource-group $RESOURCE_GROUP \
        --server-name $MYSQL_SERVER \
        --database-name $DB_NAME
    echo "✓ Database created"
    
    echo "Configuring firewall..."
    az mysql flexible-server firewall-rule create \
        --resource-group $RESOURCE_GROUP \
        --name $MYSQL_SERVER \
        --rule-name AllowAzureServices \
        --start-ip-address 0.0.0.0 \
        --end-ip-address 0.0.0.0
    echo "✓ Firewall configured"
fi

echo ""
echo "=========================================="
echo "Phase 2: Creating Redis Cache"
echo "=========================================="

if az redis show --resource-group $RESOURCE_GROUP --name $REDIS_NAME &> /dev/null; then
    echo "✓ Redis cache already exists"
else
    echo "Creating Redis cache (this may take 10-15 minutes)..."
    az redis create \
        --resource-group $RESOURCE_GROUP \
        --name $REDIS_NAME \
        --location $LOCATION \
        --sku Basic \
        --vm-size c0 \
        --enable-non-ssl-port false &
    REDIS_PID=$!
    echo "✓ Redis creation started in background"
fi

echo ""
echo "=========================================="
echo "Phase 3: Creating Key Vault"
echo "=========================================="

if az keyvault show --name $KEYVAULT_NAME &> /dev/null; then
    echo "✓ Key Vault already exists"
else
    echo "Creating Key Vault..."
    az keyvault create \
        --resource-group $RESOURCE_GROUP \
        --name $KEYVAULT_NAME \
        --location $LOCATION \
        --sku standard
    echo "✓ Key Vault created: $KEYVAULT_NAME"
fi

echo ""
echo "=========================================="
echo "Phase 4: Creating Storage Account"
echo "=========================================="

if az storage account show --resource-group $RESOURCE_GROUP --name $STORAGE_ACCOUNT &> /dev/null; then
    echo "✓ Storage account already exists"
else
    echo "Creating Storage Account..."
    az storage account create \
        --resource-group $RESOURCE_GROUP \
        --name $STORAGE_ACCOUNT \
        --location $LOCATION \
        --sku Standard_LRS \
        --kind StorageV2
    echo "✓ Storage account created"
fi

echo ""
echo "=========================================="
echo "Phase 5: Creating App Service Plan"
echo "=========================================="

if az appservice plan show --resource-group $RESOURCE_GROUP --name $APP_SERVICE_PLAN &> /dev/null; then
    echo "✓ App Service Plan already exists"
else
    echo "Creating App Service Plan..."
    az appservice plan create \
        --resource-group $RESOURCE_GROUP \
        --name $APP_SERVICE_PLAN \
        --location $LOCATION \
        --is-linux \
        --sku B1 \
        --number-of-workers 1
    echo "✓ App Service Plan created"
fi

echo ""
echo "=========================================="
echo "Phase 6: Creating Web App"
echo "=========================================="

if az webapp show --resource-group $RESOURCE_GROUP --name $WEB_APP_NAME &> /dev/null; then
    echo "✓ Web App already exists"
else
    echo "Creating Web App..."
    az webapp create \
        --resource-group $RESOURCE_GROUP \
        --plan $APP_SERVICE_PLAN \
        --name $WEB_APP_NAME \
        --runtime "PHP:8.3"
    echo "✓ Web App created"
fi

echo ""
echo "=========================================="
echo "Phase 7: Creating Application Insights"
echo "=========================================="

if az monitor app-insights component show --app $INSIGHTS_NAME --resource-group $RESOURCE_GROUP &> /dev/null; then
    echo "✓ Application Insights already exists"
else
    echo "Creating Application Insights..."
    az monitor app-insights component create \
        --app $INSIGHTS_NAME \
        --location $LOCATION \
        --resource-group $RESOURCE_GROUP \
        --application-type web
    echo "✓ Application Insights created"
fi

# Wait for Redis if it's still creating
if [ ! -z "$REDIS_PID" ]; then
    echo ""
    echo "Waiting for Redis cache to finish creating..."
    wait $REDIS_PID
    echo "✓ Redis cache created"
fi

echo ""
echo "=========================================="
echo "Retrieving Connection Strings"
echo "=========================================="

# Get connection details
MYSQL_HOST=$(az mysql flexible-server show \
    --resource-group $RESOURCE_GROUP \
    --name $MYSQL_SERVER \
    --query fullyQualifiedDomainName -o tsv)

REDIS_HOST=$(az redis show \
    --resource-group $RESOURCE_GROUP \
    --name $REDIS_NAME \
    --query hostName -o tsv)

REDIS_KEY=$(az redis list-keys \
    --resource-group $RESOURCE_GROUP \
    --name $REDIS_NAME \
    --query primaryKey -o tsv)

APP_URL="https://${WEB_APP_NAME}.azurewebsites.net"

echo ""
echo "=========================================="
echo "Deployment Summary"
echo "=========================================="
echo ""
echo "✓ All Azure resources created successfully!"
echo ""
echo "Connection Details:"
echo "  Web App URL: $APP_URL"
echo "  MySQL Host: $MYSQL_HOST"
echo "  Redis Host: $REDIS_HOST"
echo "  Key Vault: $KEYVAULT_NAME"
echo ""
echo "Next Steps:"
echo "  1. Store your secrets in Key Vault using: ./store-secrets.sh"
echo "  2. Configure App Settings using the provided connection details"
echo "  3. Deploy your application using Git"
echo "  4. Run database migrations"
echo ""

# Save configuration to file
cat > azure-config.txt << EOF
RESOURCE_GROUP=$RESOURCE_GROUP
WEB_APP_NAME=$WEB_APP_NAME
MYSQL_HOST=$MYSQL_HOST
MYSQL_SERVER=$MYSQL_SERVER
DB_ADMIN_USER=$DB_ADMIN_USER
DB_NAME=$DB_NAME
REDIS_HOST=$REDIS_HOST
REDIS_NAME=$REDIS_NAME
KEYVAULT_NAME=$KEYVAULT_NAME
APP_URL=$APP_URL
EOF

echo "Configuration saved to azure-config.txt"
echo ""
echo "=========================================="
